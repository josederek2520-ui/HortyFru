<?php

namespace App\Actions\Recepciones;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\DetalleRecepcion;
use App\Models\Recepcion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EliminarDetalleRecepcionAction
{
    public function __invoke(Recepcion $recepcion, DetalleRecepcion $detalle, User $usuario): void
    {
        DB::transaction(function () use ($recepcion, $detalle, $usuario): void {
            $recepcion = Recepcion::query()->lockForUpdate()->findOrFail($recepcion->id);
            $detalle = DetalleRecepcion::query()->lockForUpdate()->findOrFail($detalle->id);

            if ($detalle->recepcion_id !== $recepcion->id) {
                abort(404);
            }

            if (! $recepcion->estaEnBorrador()) {
                throw ValidationException::withMessages([
                    'detalle' => 'Solo se pueden eliminar productos de una recepción en borrador.',
                ]);
            }

            $datosDetalle = [
                'detalle_recepcion_id' => $detalle->id,
                ...$detalle->only([
                    'detalle_compra_id',
                    'articulo_id',
                    'presentacion_articulo_id',
                    'cantidad_presentaciones_detalle_recepcion',
                    'cantidad_base_detalle_recepcion',
                    'unidad_medida_id',
                    'fecha_vencimiento_detalle_recepcion',
                    'calidad_detalle_recepcion',
                ]),
            ];
            $detalle->delete();

            activity(ActivityLogName::Receptions->value)
                ->event(ActivityEvent::Deleted->value)
                ->performedOn($recepcion)
                ->causedBy($usuario)
                ->withProperties(['old' => $datosDetalle])
                ->log('Producto eliminado de la recepción');
        });
    }
}
