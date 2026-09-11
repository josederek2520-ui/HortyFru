<?php

namespace App\Actions\Inventario;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoLote;
use App\Enums\EstadoMovimientoInventario;
use App\Enums\TipoMovimientoInventario;
use App\Enums\TipoReferenciaMovimientoInventario;
use App\Models\Almacen;
use App\Models\DetalleMovimientoInventario;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrarMovimientoInventarioAction
{
    public function __construct(private CalcularSaldoLoteAction $calcularSaldoLote) {}

    /**
     * @param  list<array{lote: Lote|int, cantidad: string|int|float}>  $detalles
     */
    public function __invoke(
        TipoMovimientoInventario $tipo,
        Almacen $almacen,
        CarbonInterface $fecha,
        array $detalles,
        User $usuario,
        ?TipoReferenciaMovimientoInventario $tipoReferencia = null,
        ?int $referenciaId = null,
        ?string $observaciones = null,
    ): MovimientoInventario {
        return DB::transaction(function () use ($tipo, $almacen, $fecha, $detalles, $usuario, $tipoReferencia, $referenciaId, $observaciones): MovimientoInventario {
            $almacen = Almacen::query()->lockForUpdate()->findOrFail($almacen->id);

            if (! $almacen->estado_almacen) {
                throw ValidationException::withMessages([
                    'almacen_id' => 'El almacén seleccionado está inactivo.',
                ]);
            }

            $this->validarReferencia($tipo, $tipoReferencia, $referenciaId);

            if ($tipoReferencia !== null && $referenciaId !== null) {
                $existente = MovimientoInventario::query()
                    ->where('tipo_movimiento_inventario', $tipo->value)
                    ->where('tipo_referencia_movimiento_inventario', $tipoReferencia->value)
                    ->where('referencia_id_movimiento_inventario', $referenciaId)
                    ->where('almacen_id', $almacen->id)
                    ->lockForUpdate()
                    ->first();

                if ($existente !== null) {
                    return $existente->load([
                        'almacen',
                        'registradoPor.empleado',
                        'detalles.articulo.unidadMedida',
                        'detalles.lote',
                    ]);
                }
            }

            $cantidadesPorLote = $this->normalizarDetalles($detalles);
            $lotes = Lote::query()
                ->with('articulo.unidadMedida')
                ->whereKey(array_keys($cantidadesPorLote))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($lotes->count() !== count($cantidadesPorLote)) {
                throw ValidationException::withMessages([
                    'detalles' => 'Uno de los lotes seleccionados ya no existe.',
                ]);
            }

            foreach ($cantidadesPorLote as $loteId => $cantidad) {
                /** @var Lote $lote */
                $lote = $lotes->get($loteId);
                $this->validarLote($lote, $almacen, $tipo, $cantidad);
            }

            $fechaUtc = $fecha->toImmutable()->utc();
            $fechaLocal = $fechaUtc->setTimezone(
                (string) config('app.display_timezone', 'America/La_Paz'),
            );

            $movimiento = new MovimientoInventario;
            $movimiento->disableLogging();
            $movimiento->fill([
                'codigo_movimiento_inventario' => 'TEMP-'.Str::uuid(),
                'almacen_id' => $almacen->id,
                'tipo_movimiento_inventario' => $tipo,
                'fecha_movimiento_inventario' => $fechaUtc,
                'estado_movimiento_inventario' => EstadoMovimientoInventario::Registrado,
                'tipo_referencia_movimiento_inventario' => $tipoReferencia,
                'referencia_id_movimiento_inventario' => $referenciaId,
                'observaciones_movimiento_inventario' => $this->normalizarOpcional($observaciones),
                'registrado_por' => $usuario->id,
            ]);
            $movimiento->save();
            $movimiento->update([
                'codigo_movimiento_inventario' => sprintf(
                    'MOV-%s-%03d',
                    $fechaLocal->format('dmY'),
                    $movimiento->id,
                ),
            ]);
            $movimiento->enableLogging();

            $detallesAuditoria = [];

            foreach ($cantidadesPorLote as $loteId => $cantidad) {
                /** @var Lote $lote */
                $lote = $lotes->get($loteId);
                $detalle = new DetalleMovimientoInventario;
                $detalle->disableLogging();
                $detalle->fill([
                    'movimiento_inventario_id' => $movimiento->id,
                    'articulo_id' => $lote->articulo_id,
                    'lote_id' => $lote->id,
                    'unidad_medida_id' => $lote->articulo->unidad_medida_id,
                    'cantidad_movimiento_inventario' => $cantidad->toScale(3, RoundingMode::Unnecessary)->__toString(),
                ]);
                $detalle->save();
                $detalle->enableLogging();

                $saldoGlobal = ($this->calcularSaldoLote)($lote);
                $nuevoEstado = $saldoGlobal->isZero() ? EstadoLote::Agotado : EstadoLote::Disponible;

                if ($lote->estado_lote !== $nuevoEstado && $lote->estado_lote !== EstadoLote::Bloqueado) {
                    $lote->disableLogging();
                    $lote->update(['estado_lote' => $nuevoEstado]);
                    $lote->enableLogging();
                }

                $detallesAuditoria[] = [
                    'articulo_id' => $lote->articulo_id,
                    'lote_id' => $lote->id,
                    'cantidad_movimiento_inventario' => $detalle->cantidad_movimiento_inventario,
                    'unidad_medida_id' => $detalle->unidad_medida_id,
                ];
            }

            activity(ActivityLogName::InventoryMovements->value)
                ->event(ActivityEvent::Created->value)
                ->performedOn($movimiento)
                ->causedBy($usuario)
                ->withProperties([
                    'attributes' => [
                        'codigo_movimiento_inventario' => $movimiento->codigo_movimiento_inventario,
                        'almacen_id' => $movimiento->almacen_id,
                        'tipo_movimiento_inventario' => $tipo->value,
                        'fecha_movimiento_inventario' => $fechaUtc->toDateTimeString(),
                        'tipo_referencia_movimiento_inventario' => $tipoReferencia?->value,
                        'referencia_id_movimiento_inventario' => $referenciaId,
                        'detalles_movimiento_inventario' => $detallesAuditoria,
                    ],
                ])
                ->log('Movimiento de inventario registrado');

            return $movimiento->load([
                'almacen',
                'registradoPor.empleado',
                'detalles.articulo.unidadMedida',
                'detalles.lote',
            ]);
        });
    }

    private function validarReferencia(
        TipoMovimientoInventario $tipo,
        ?TipoReferenciaMovimientoInventario $tipoReferencia,
        ?int $referenciaId,
    ): void {
        if (($tipoReferencia === null) !== ($referenciaId === null)) {
            throw ValidationException::withMessages([
                'referencia' => 'El tipo y el número de referencia deben registrarse juntos.',
            ]);
        }

        $esAjuste = in_array($tipo, [
            TipoMovimientoInventario::AjustePositivo,
            TipoMovimientoInventario::AjusteNegativo,
        ], true);

        if (! $esAjuste && ($tipoReferencia === null || $referenciaId === null)) {
            throw ValidationException::withMessages([
                'referencia' => 'Este movimiento requiere una operación de origen.',
            ]);
        }

        if ($tipoReferencia !== null && $tipoReferencia !== $tipo->referenciaEsperada()) {
            throw ValidationException::withMessages([
                'referencia' => 'La referencia no corresponde al tipo de movimiento.',
            ]);
        }

        if ($referenciaId !== null && $referenciaId < 1) {
            throw ValidationException::withMessages([
                'referencia' => 'La referencia del movimiento no es válida.',
            ]);
        }
    }

    /**
     * @param  list<array{lote: Lote|int, cantidad: string|int|float}>  $detalles
     * @return array<int, BigDecimal>
     */
    private function normalizarDetalles(array $detalles): array
    {
        if ($detalles === []) {
            throw ValidationException::withMessages([
                'detalles' => 'Agrega al menos un lote al movimiento.',
            ]);
        }

        $cantidadesPorLote = [];

        foreach ($detalles as $detalle) {
            $loteId = $detalle['lote'] instanceof Lote
                ? $detalle['lote']->getKey()
                : (int) $detalle['lote'];
            $cantidadNormalizada = str_replace(',', '.', trim((string) $detalle['cantidad']));

            if (! is_int($loteId) || $loteId < 1 || preg_match('/^\d{1,11}(?:\.\d{1,3})?$/', $cantidadNormalizada) !== 1) {
                throw ValidationException::withMessages([
                    'detalles' => 'Revisa los lotes y las cantidades del movimiento.',
                ]);
            }

            $cantidad = BigDecimal::of($cantidadNormalizada);

            if ($cantidad->compareTo(0) <= 0) {
                throw ValidationException::withMessages([
                    'detalles' => 'Todas las cantidades deben ser mayores que cero.',
                ]);
            }

            $cantidadesPorLote[$loteId] = isset($cantidadesPorLote[$loteId])
                ? $cantidadesPorLote[$loteId]->plus($cantidad)
                : $cantidad;

            if ($cantidadesPorLote[$loteId]->compareTo('99999999999.999') > 0) {
                throw ValidationException::withMessages([
                    'detalles' => 'Una cantidad supera el máximo permitido.',
                ]);
            }
        }

        ksort($cantidadesPorLote);

        return $cantidadesPorLote;
    }

    private function validarLote(
        Lote $lote,
        Almacen $almacen,
        TipoMovimientoInventario $tipo,
        BigDecimal $cantidad,
    ): void {
        if ($lote->estado_lote === EstadoLote::Bloqueado) {
            throw ValidationException::withMessages([
                'detalles' => "El lote {$lote->codigo_lote} está bloqueado.",
            ]);
        }

        if (! $tipo->esEntrada() && $lote->estaVencido()) {
            throw ValidationException::withMessages([
                'detalles' => "El lote {$lote->codigo_lote} está vencido y no puede utilizarse.",
            ]);
        }

        if (! $tipo->esEntrada()) {
            $saldo = ($this->calcularSaldoLote)($lote, $almacen);

            if ($cantidad->compareTo($saldo) > 0) {
                throw ValidationException::withMessages([
                    'detalles' => "El lote {$lote->codigo_lote} no tiene saldo suficiente en {$almacen->nombre_almacen}.",
                ]);
            }
        }
    }

    private function normalizarOpcional(?string $valor): ?string
    {
        $valor = Str::squish($valor ?? '');

        return $valor === '' ? null : $valor;
    }
}
