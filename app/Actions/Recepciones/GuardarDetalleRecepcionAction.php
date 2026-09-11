<?php

namespace App\Actions\Recepciones;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\TipoEquivalenciaPresentacionArticulo;
use App\Models\Articulo;
use App\Models\DetalleCompra;
use App\Models\DetalleRecepcion;
use App\Models\PresentacionArticulo;
use App\Models\Recepcion;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuardarDetalleRecepcionAction
{
    /**
     * @param  array{
     *     detalle_compra_id: ?int,
     *     articulo_id: int,
     *     presentacion_articulo_id: ?int,
     *     cantidad_presentaciones_detalle_recepcion: ?string,
     *     cantidad_base_detalle_recepcion: ?string,
     *     fecha_vencimiento_detalle_recepcion: ?string,
     *     calidad_detalle_recepcion: ?string,
     *     observaciones_detalle_recepcion: ?string
     * }  $datos
     */
    public function __invoke(
        Recepcion $recepcion,
        ?DetalleRecepcion $detalle,
        array $datos,
        User $usuario,
    ): DetalleRecepcion {
        return DB::transaction(function () use ($recepcion, $detalle, $datos, $usuario): DetalleRecepcion {
            $recepcion = Recepcion::query()->lockForUpdate()->findOrFail($recepcion->id);

            if (! $recepcion->estaEnBorrador()) {
                throw ValidationException::withMessages([
                    'form.detalle' => 'Solo se pueden modificar productos de una recepción en borrador.',
                ]);
            }

            if ($detalle !== null) {
                $detalle = DetalleRecepcion::query()->lockForUpdate()->findOrFail($detalle->id);

                if ($detalle->recepcion_id !== $recepcion->id) {
                    abort(404);
                }
            }

            [$articulo, $presentacion, $detalleCompra] = $this->resolverProducto($recepcion, $detalle, $datos);
            $this->validarCombinacion($presentacion, $datos);
            $this->validarDuplicado($recepcion, $detalle, $articulo->id, $presentacion?->id, $detalleCompra?->id);

            $cantidadPresentaciones = $presentacion === null
                ? null
                : $this->decimal($datos['cantidad_presentaciones_detalle_recepcion']);
            $equivalencia = $presentacion?->tipo_equivalencia_presentacion_articulo === TipoEquivalenciaPresentacionArticulo::Fija
                ? $presentacion->equivalencia_base_presentacion_articulo
                : null;
            $cantidadBase = $this->cantidadBase($presentacion, $cantidadPresentaciones, $datos['cantidad_base_detalle_recepcion']);
            $fechaVencimiento = $this->fechaVencimiento($recepcion, $datos['fecha_vencimiento_detalle_recepcion']);
            $atributos = [
                'detalle_compra_id' => $detalleCompra?->id,
                'articulo_id' => $articulo->id,
                'presentacion_articulo_id' => $presentacion?->id,
                'cantidad_presentaciones_detalle_recepcion' => $cantidadPresentaciones,
                'equivalencia_base_aplicada_detalle_recepcion' => $equivalencia,
                'cantidad_base_detalle_recepcion' => $cantidadBase,
                'unidad_medida_id' => $articulo->unidad_medida_id,
                'fecha_vencimiento_detalle_recepcion' => $fechaVencimiento,
                'calidad_detalle_recepcion' => $datos['calidad_detalle_recepcion'],
                'observaciones_detalle_recepcion' => $datos['observaciones_detalle_recepcion'],
            ];

            $evento = $detalle === null ? ActivityEvent::Created : ActivityEvent::Updated;
            $descripcion = $detalle === null
                ? 'Producto agregado a la recepción'
                : 'Producto de la recepción actualizado';
            $valoresAnteriores = $detalle?->only(array_keys($atributos));
            $detalle ??= new DetalleRecepcion;
            $detalle->fill($atributos);
            $recepcion->detalles()->save($detalle);
            $propiedades = [
                'attributes' => [
                    'detalle_recepcion_id' => $detalle->id,
                    ...$detalle->only(array_keys($atributos)),
                ],
            ];

            if ($valoresAnteriores !== null) {
                $propiedades['old'] = $valoresAnteriores;
            }

            activity(ActivityLogName::Receptions->value)
                ->event($evento->value)
                ->performedOn($recepcion)
                ->causedBy($usuario)
                ->withProperties($propiedades)
                ->log($descripcion);

            return $detalle->load([
                'articulo.unidadMedida',
                'presentacionArticulo',
                'detalleCompra',
                'unidadMedida',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array{Articulo, ?PresentacionArticulo, ?DetalleCompra}
     */
    private function resolverProducto(Recepcion $recepcion, ?DetalleRecepcion $detalle, array $datos): array
    {
        $detalleCompra = null;

        if ($recepcion->compra_id !== null) {
            if ($datos['detalle_compra_id'] === null) {
                throw ValidationException::withMessages([
                    'form.detalle_compra_id' => 'Selecciona un producto de la compra relacionada.',
                ]);
            }

            $detalleCompra = DetalleCompra::query()->lockForUpdate()->findOrFail($datos['detalle_compra_id']);

            if ($detalleCompra->compra_id !== $recepcion->compra_id) {
                throw ValidationException::withMessages([
                    'form.detalle_compra_id' => 'El producto no pertenece a la compra relacionada.',
                ]);
            }

            $datos['articulo_id'] = $detalleCompra->articulo_id;
            $datos['presentacion_articulo_id'] = $detalleCompra->presentacion_articulo_id;
        } elseif ($datos['detalle_compra_id'] !== null) {
            throw ValidationException::withMessages([
                'form.detalle_compra_id' => 'Esta recepción no tiene una compra relacionada.',
            ]);
        }

        $articulo = Articulo::query()
            ->with('unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida')
            ->lockForUpdate()
            ->findOrFail($datos['articulo_id']);

        if (! $articulo->estado_articulo
            && $detalle?->articulo_id !== $articulo->id
            && $detalleCompra === null) {
            throw ValidationException::withMessages(['form.articulo_id' => 'Selecciona un producto activo.']);
        }

        $presentacion = $this->obtenerPresentacion(
            $articulo,
            $detalle,
            $datos['presentacion_articulo_id'],
            $detalleCompra !== null,
        );

        return [$articulo, $presentacion, $detalleCompra];
    }

    private function obtenerPresentacion(
        Articulo $articulo,
        ?DetalleRecepcion $detalle,
        ?int $presentacionId,
        bool $provieneDeCompra,
    ): ?PresentacionArticulo {
        if ($presentacionId === null) {
            return null;
        }

        $presentacion = PresentacionArticulo::query()->lockForUpdate()->findOrFail($presentacionId);
        $esPresentacionActual = $detalle?->presentacion_articulo_id === $presentacion->id;

        if ($presentacion->articulo_id !== $articulo->id) {
            throw ValidationException::withMessages([
                'form.presentacion_articulo_id' => 'La presentación no pertenece al producto seleccionado.',
            ]);
        }

        if (! $provieneDeCompra
            && (! $presentacion->estado_presentacion_articulo || ! $presentacion->uso_presentacion_articulo->permiteCompra())
            && ! $esPresentacionActual) {
            throw ValidationException::withMessages([
                'form.presentacion_articulo_id' => 'Selecciona una presentación activa y disponible para compras.',
            ]);
        }

        return $presentacion;
    }

    /** @param array<string, mixed> $datos */
    private function validarCombinacion(?PresentacionArticulo $presentacion, array $datos): void
    {
        if ($presentacion === null) {
            if ($datos['cantidad_base_detalle_recepcion'] === null) {
                throw ValidationException::withMessages([
                    'form.cantidad_base_detalle_recepcion' => 'Ingresa la cantidad física recibida.',
                ]);
            }

            return;
        }

        if ($datos['cantidad_presentaciones_detalle_recepcion'] === null) {
            throw ValidationException::withMessages([
                'form.cantidad_presentaciones_detalle_recepcion' => 'Ingresa la cantidad de presentaciones recibidas.',
            ]);
        }

        if ($presentacion->tipo_equivalencia_presentacion_articulo === TipoEquivalenciaPresentacionArticulo::Fija
            && $presentacion->equivalencia_base_presentacion_articulo === null) {
            throw ValidationException::withMessages([
                'form.presentacion_articulo_id' => 'La presentación fija no tiene una equivalencia configurada.',
            ]);
        }

        if (! $presentacion->permite_fraccion_presentacion_articulo
            && BigDecimal::of($datos['cantidad_presentaciones_detalle_recepcion'])->hasNonZeroFractionalPart()) {
            throw ValidationException::withMessages([
                'form.cantidad_presentaciones_detalle_recepcion' => 'Esta presentación solo permite cantidades enteras.',
            ]);
        }

        if ($presentacion->tipo_equivalencia_presentacion_articulo === TipoEquivalenciaPresentacionArticulo::Variable
            && $datos['cantidad_base_detalle_recepcion'] === null) {
            throw ValidationException::withMessages([
                'form.cantidad_base_detalle_recepcion' => 'Ingresa la cantidad real contenida en las presentaciones.',
            ]);
        }
    }

    private function validarDuplicado(
        Recepcion $recepcion,
        ?DetalleRecepcion $detalle,
        int $articuloId,
        ?int $presentacionId,
        ?int $detalleCompraId,
    ): void {
        $duplicado = $recepcion->detalles()
            ->where('articulo_id', $articuloId)
            ->when(
                $presentacionId === null,
                fn ($consulta) => $consulta->whereNull('presentacion_articulo_id'),
                fn ($consulta) => $consulta->where('presentacion_articulo_id', $presentacionId),
            )
            ->when($detalle !== null, fn ($consulta) => $consulta->whereKeyNot($detalle->id))
            ->exists();

        if ($duplicado) {
            throw ValidationException::withMessages([
                'form.articulo_id' => 'Este producto y presentación ya están incluidos en la recepción.',
            ]);
        }

        if ($detalleCompraId !== null && $recepcion->detalles()
            ->where('detalle_compra_id', $detalleCompraId)
            ->when($detalle !== null, fn ($consulta) => $consulta->whereKeyNot($detalle->id))
            ->exists()) {
            throw ValidationException::withMessages([
                'form.detalle_compra_id' => 'Este producto de la compra ya fue agregado a la recepción.',
            ]);
        }
    }

    private function cantidadBase(
        ?PresentacionArticulo $presentacion,
        ?string $cantidadPresentaciones,
        ?string $cantidadBase,
    ): string {
        if ($presentacion?->tipo_equivalencia_presentacion_articulo === TipoEquivalenciaPresentacionArticulo::Fija) {
            $cantidadCalculada = BigDecimal::of($cantidadPresentaciones)
                ->multipliedBy($presentacion->equivalencia_base_presentacion_articulo)
                ->toScale(3, RoundingMode::HalfUp);

            if ($cantidadCalculada->compareTo('99999999999.999') > 0) {
                throw ValidationException::withMessages([
                    'form.cantidad_presentaciones_detalle_recepcion' => 'La cantidad total calculada supera el máximo permitido.',
                ]);
            }

            return $cantidadCalculada->__toString();
        }

        return BigDecimal::of($cantidadBase)
            ->toScale(3, RoundingMode::Unnecessary)
            ->__toString();
    }

    private function fechaVencimiento(Recepcion $recepcion, ?string $fecha): ?string
    {
        if ($fecha === null) {
            return null;
        }

        $zonaHoraria = (string) config('app.display_timezone', 'America/La_Paz');
        $vencimiento = CarbonImmutable::createFromFormat('!Y-m-d', $fecha, $zonaHoraria);
        $ingreso = $recepcion->fecha_recepcion->copy()->setTimezone($zonaHoraria)->startOfDay();

        if ($vencimiento->lessThan($ingreso)) {
            throw ValidationException::withMessages([
                'form.fecha_vencimiento_detalle_recepcion' => 'El vencimiento no puede ser anterior a la recepción.',
            ]);
        }

        return $vencimiento->toDateString();
    }

    private function decimal(?string $valor): string
    {
        return BigDecimal::of($valor)->__toString();
    }
}
