<?php

namespace App\Actions\Compras;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EliminarDetalleCompraAction
{
    public function __invoke(Compra $compra, DetalleCompra $detalle, User $usuario): Compra
    {
        return DB::transaction(function () use ($compra, $detalle, $usuario): Compra {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);
            $detalle = DetalleCompra::query()->lockForUpdate()->findOrFail($detalle->id);

            if ($detalle->compra_id !== $compra->id) {
                abort(404);
            }

            if (! $compra->estaEnBorrador()) {
                throw ValidationException::withMessages(['detalle' => 'Solo se pueden eliminar productos de una compra en borrador.']);
            }

            $datosDetalle = [
                'detalle_compra_id' => $detalle->id,
                ...$detalle->only([
                    'articulo_id',
                    'presentacion_articulo_id',
                    'cantidad_presentaciones_detalle_compra',
                    'cantidad_real_detalle_compra',
                    'precio_unitario_detalle_compra',
                    'precio_por_detalle_compra',
                    'subtotal_detalle_compra',
                ]),
            ];
            $detalle->delete();
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

            activity(ActivityLogName::Purchases->value)
                ->event(ActivityEvent::Deleted->value)
                ->performedOn($compra)
                ->causedBy($usuario)
                ->withProperties([
                    'old' => $datosDetalle,
                    'attributes' => ['total_compra' => $total],
                ])
                ->log('Producto eliminado de la compra');

            return $compra->refresh();
        });
    }
}
