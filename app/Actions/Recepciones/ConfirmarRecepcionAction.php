<?php

namespace App\Actions\Recepciones;

use App\Actions\Inventario\RegistrarMovimientoInventarioAction;
use App\Actions\Lotes\CrearLoteAction;
use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoRecepcion;
use App\Enums\TipoMovimientoInventario;
use App\Enums\TipoOrigenLote;
use App\Enums\TipoReferenciaMovimientoInventario;
use App\Models\Recepcion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmarRecepcionAction
{
    public function __construct(
        private CrearLoteAction $crearLote,
        private RegistrarMovimientoInventarioAction $registrarMovimiento,
    ) {}

    public function __invoke(Recepcion $recepcion, User $usuario): Recepcion
    {
        return DB::transaction(function () use ($recepcion, $usuario): Recepcion {
            $recepcion = Recepcion::query()
                ->with('almacen')
                ->lockForUpdate()
                ->findOrFail($recepcion->id);

            if (! $recepcion->estaEnBorrador()) {
                throw ValidationException::withMessages([
                    'confirmacion' => 'Esta recepción ya no está en borrador.',
                ]);
            }

            $detalles = $recepcion->detalles()
                ->with('articulo.unidadMedida')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($detalles->isEmpty()) {
                throw ValidationException::withMessages([
                    'confirmacion' => 'Agrega al menos un producto antes de confirmar la recepción.',
                ]);
            }

            if (! $recepcion->almacen->estado_almacen) {
                throw ValidationException::withMessages([
                    'confirmacion' => 'El almacén de la recepción está inactivo.',
                ]);
            }

            $detallesMovimiento = [];

            foreach ($detalles as $detalle) {
                if ($detalle->lote_id !== null) {
                    throw ValidationException::withMessages([
                        'confirmacion' => 'Uno de los productos ya tiene un lote asignado.',
                    ]);
                }

                $vencimiento = $detalle->fecha_vencimiento_detalle_recepcion === null
                    ? null
                    : CarbonImmutable::createFromFormat(
                        '!Y-m-d',
                        $detalle->fecha_vencimiento_detalle_recepcion->toDateString(),
                        (string) config('app.display_timezone', 'America/La_Paz'),
                    );
                $observaciones = collect([
                    "Origen: {$recepcion->codigo_recepcion}",
                    $detalle->observaciones_detalle_recepcion,
                ])->filter()->implode('. ');
                $lote = ($this->crearLote)(
                    $detalle->articulo,
                    TipoOrigenLote::Recepcion,
                    $recepcion->fecha_recepcion,
                    $vencimiento,
                    $detalle->calidad_detalle_recepcion,
                    $observaciones,
                    $usuario,
                );
                $detalle->update(['lote_id' => $lote->id]);
                $detallesMovimiento[] = [
                    'lote' => $lote,
                    'cantidad' => $detalle->cantidad_base_detalle_recepcion,
                ];
            }

            ($this->registrarMovimiento)(
                TipoMovimientoInventario::EntradaRecepcion,
                $recepcion->almacen,
                $recepcion->fecha_recepcion,
                $detallesMovimiento,
                $usuario,
                TipoReferenciaMovimientoInventario::Recepcion,
                $recepcion->id,
                "Entrada generada al confirmar {$recepcion->codigo_recepcion}",
            );

            $recepcion->disableLogging();
            $recepcion->update([
                'estado_recepcion' => EstadoRecepcion::Confirmada,
                'confirmado_por' => $usuario->id,
                'confirmado_en' => now(),
            ]);
            $recepcion->enableLogging();

            activity(ActivityLogName::Receptions->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($recepcion)
                ->causedBy($usuario)
                ->withProperties([
                    'old' => ['estado_recepcion' => EstadoRecepcion::Borrador->value],
                    'attributes' => [
                        'estado_recepcion' => EstadoRecepcion::Confirmada->value,
                        'confirmado_por' => $usuario->id,
                        'confirmado_en' => $recepcion->confirmado_en?->toDateTimeString(),
                        'cantidad_items_recepcion' => $detalles->count(),
                    ],
                ])
                ->log('Recepción confirmada');

            return $recepcion->refresh()->load([
                'almacen',
                'detalles.articulo.unidadMedida',
                'detalles.lote',
            ]);
        }, attempts: 3);
    }
}
