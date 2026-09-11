<?php

namespace App\Actions\Compras;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\PrecioPorDetalleCompra;
use App\Enums\TipoEquivalenciaPresentacionArticulo;
use App\Models\Articulo;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\PresentacionArticulo;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuardarDetalleCompraAction
{
    /**
     * @param  array{
     *     articulo_id: int,
     *     presentacion_articulo_id: ?int,
     *     cantidad_presentaciones_detalle_compra: ?string,
     *     cantidad_real_detalle_compra: ?string,
     *     total_pagado_detalle_compra: string,
     *     observaciones_detalle_compra: ?string
     * }  $datos
     */
    public function __invoke(Compra $compra, ?DetalleCompra $detalle, array $datos, User $usuario): DetalleCompra
    {
        return DB::transaction(function () use ($compra, $detalle, $datos, $usuario): DetalleCompra {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);

            if (! $compra->estaEnBorrador()) {
                throw ValidationException::withMessages(['form.detalle' => 'Solo se pueden modificar productos de una compra en borrador.']);
            }

            if ($detalle !== null) {
                $detalle = DetalleCompra::query()->lockForUpdate()->findOrFail($detalle->id);

                if ($detalle->compra_id !== $compra->id) {
                    abort(404);
                }
            }

            $articulo = Articulo::query()
                ->with('unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida')
                ->lockForUpdate()
                ->findOrFail($datos['articulo_id']);

            if (! $articulo->estado_articulo && $detalle?->articulo_id !== $articulo->id) {
                throw ValidationException::withMessages(['form.articulo_id' => 'Selecciona un producto activo.']);
            }

            $presentacion = $this->obtenerPresentacion($articulo, $detalle, $datos['presentacion_articulo_id']);
            $this->validarCombinacion($presentacion, $datos);
            $this->validarDuplicado($compra, $detalle, $articulo->id, $presentacion?->id);

            $cantidadPresentaciones = $presentacion === null
                ? null
                : $this->decimal($datos['cantidad_presentaciones_detalle_compra']);
            $equivalencia = $presentacion?->equivalencia_base_presentacion_articulo;
            $cantidadReal = $this->cantidadReal($presentacion, $cantidadPresentaciones, $datos['cantidad_real_detalle_compra']);
            $totalPagado = BigDecimal::of($datos['total_pagado_detalle_compra'])
                ->toScale(2, RoundingMode::HalfUp)
                ->__toString();
            $basePrecio = $presentacion === null
                ? $cantidadReal
                : $cantidadPresentaciones;
            $precioUnitario = BigDecimal::of($totalPagado)
                ->dividedBy($basePrecio, 2, RoundingMode::HalfUp)
                ->__toString();
            $precioPor = $presentacion === null
                ? PrecioPorDetalleCompra::UnidadBase
                : PrecioPorDetalleCompra::Presentacion;
            $subtotal = $totalPagado;

            $atributos = [
                'articulo_id' => $articulo->id,
                'presentacion_articulo_id' => $presentacion?->id,
                'cantidad_presentaciones_detalle_compra' => $cantidadPresentaciones,
                'equivalencia_base_aplicada_detalle_compra' => $equivalencia,
                'cantidad_real_detalle_compra' => $cantidadReal,
                'unidad_medida_id' => $cantidadReal === null ? null : $articulo->unidad_medida_id,
                'precio_unitario_detalle_compra' => $precioUnitario,
                'precio_por_detalle_compra' => $precioPor,
                'subtotal_detalle_compra' => $subtotal,
                'observaciones_detalle_compra' => $datos['observaciones_detalle_compra'],
            ];

            $evento = $detalle === null ? ActivityEvent::Created : ActivityEvent::Updated;
            $descripcion = $detalle === null ? 'Producto agregado a la compra' : 'Producto de la compra actualizado';
            $valoresAnteriores = $detalle?->only(array_keys($atributos));
            $detalle ??= new DetalleCompra;
            $detalle->fill($atributos);
            $compra->detalles()->save($detalle);

            $total = $this->recalcularTotal($compra);
            $propiedades = [
                'attributes' => [
                    'detalle_compra_id' => $detalle->id,
                    ...$detalle->only(array_keys($atributos)),
                    'total_compra' => $total,
                ],
            ];

            if ($valoresAnteriores !== null) {
                $propiedades['old'] = $valoresAnteriores;
            }

            activity(ActivityLogName::Purchases->value)
                ->event($evento->value)
                ->performedOn($compra)
                ->causedBy($usuario)
                ->withProperties($propiedades)
                ->log($descripcion);

            return $detalle->load(['articulo.unidadMedida', 'presentacionArticulo', 'unidadMedida']);
        });
    }

    private function obtenerPresentacion(Articulo $articulo, ?DetalleCompra $detalle, ?int $presentacionId): ?PresentacionArticulo
    {
        if ($presentacionId === null) {
            return null;
        }

        $presentacion = PresentacionArticulo::query()->lockForUpdate()->findOrFail($presentacionId);
        $esPresentacionActual = $detalle?->presentacion_articulo_id === $presentacion->id;

        if ($presentacion->articulo_id !== $articulo->id) {
            throw ValidationException::withMessages(['form.presentacion_articulo_id' => 'La presentación no pertenece al producto seleccionado.']);
        }

        if ((! $presentacion->estado_presentacion_articulo || ! $presentacion->uso_presentacion_articulo->permiteCompra()) && ! $esPresentacionActual) {
            throw ValidationException::withMessages(['form.presentacion_articulo_id' => 'Selecciona una presentación activa y disponible para compras.']);
        }

        return $presentacion;
    }

    /** @param array<string, mixed> $datos */
    private function validarCombinacion(?PresentacionArticulo $presentacion, array $datos): void
    {
        if ($presentacion === null) {
            if ($datos['cantidad_real_detalle_compra'] === null) {
                throw ValidationException::withMessages(['form.cantidad_real_detalle_compra' => 'Ingresa la cantidad real comprada.']);
            }

            return;
        }

        if ($datos['cantidad_presentaciones_detalle_compra'] === null) {
            throw ValidationException::withMessages(['form.cantidad_presentaciones_detalle_compra' => 'Ingresa la cantidad de presentaciones.']);
        }

        if ($presentacion->tipo_equivalencia_presentacion_articulo === TipoEquivalenciaPresentacionArticulo::Fija
            && $presentacion->equivalencia_base_presentacion_articulo === null) {
            throw ValidationException::withMessages(['form.presentacion_articulo_id' => 'La presentación fija no tiene una equivalencia configurada.']);
        }

        if (! $presentacion->permite_fraccion_presentacion_articulo
            && BigDecimal::of($datos['cantidad_presentaciones_detalle_compra'])->hasNonZeroFractionalPart()) {
            throw ValidationException::withMessages(['form.cantidad_presentaciones_detalle_compra' => 'Esta presentación solo permite cantidades enteras.']);
        }

    }

    private function validarDuplicado(Compra $compra, ?DetalleCompra $detalle, int $articuloId, ?int $presentacionId): void
    {
        $duplicado = $compra->detalles()
            ->where('articulo_id', $articuloId)
            ->when(
                $presentacionId === null,
                fn ($consulta) => $consulta->whereNull('presentacion_articulo_id'),
                fn ($consulta) => $consulta->where('presentacion_articulo_id', $presentacionId),
            )
            ->when($detalle !== null, fn ($consulta) => $consulta->whereKeyNot($detalle->id))
            ->exists();

        if ($duplicado) {
            throw ValidationException::withMessages(['form.articulo_id' => 'Este producto y presentación ya están incluidos en la compra.']);
        }
    }

    private function cantidadReal(?PresentacionArticulo $presentacion, ?string $cantidadPresentaciones, ?string $cantidadReal): ?string
    {
        if ($presentacion?->tipo_equivalencia_presentacion_articulo === TipoEquivalenciaPresentacionArticulo::Fija) {
            return BigDecimal::of($cantidadPresentaciones)
                ->multipliedBy($presentacion->equivalencia_base_presentacion_articulo)
                ->toScale(3, RoundingMode::HalfUp)
                ->__toString();
        }

        return $cantidadReal === null
            ? null
            : BigDecimal::of($cantidadReal)->toScale(3, RoundingMode::Unnecessary)->__toString();
    }

    private function decimal(?string $valor): string
    {
        return BigDecimal::of($valor)->__toString();
    }

    private function recalcularTotal(Compra $compra): string
    {
        $total = $compra->detalles()
            ->pluck('subtotal_detalle_compra')
            ->reduce(
                fn (BigDecimal $acumulado, mixed $subtotal): BigDecimal => $acumulado->plus((string) $subtotal),
                BigDecimal::of(0),
            )
            ->toScale(2, RoundingMode::HalfUp)
            ->__toString();
        $compra->disableLogging();
        $compra->update(['total_compra' => $total]);
        $compra->enableLogging();

        return $total;
    }
}
