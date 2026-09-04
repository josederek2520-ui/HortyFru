<?php

namespace App\Actions\Pedidos;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoPedido;
use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CambiarPreparacionDetallePedidoAction
{
    public function __invoke(DetallePedido $detallePedido, bool $preparado, User $usuario): Pedido
    {
        return DB::transaction(function () use ($detallePedido, $preparado, $usuario): Pedido {
            $detallePedido = DetallePedido::query()->lockForUpdate()->findOrFail($detallePedido->id);
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($detallePedido->pedido_id);

            if (! in_array($pedido->estado_pedido, [EstadoPedido::Pendiente, EstadoPedido::Preparado], true)) {
                throw ValidationException::withMessages([
                    'preparacion' => 'Este pedido ya no se puede modificar durante la preparación.',
                ]);
            }

            $estadoDetalleAnterior = $detallePedido->preparado_detalle_pedido;
            $estadoPedidoAnterior = $pedido->estado_pedido;
            $detallePedido->update([
                'preparado_detalle_pedido' => $preparado,
                'preparado_por' => $preparado ? $usuario->id : null,
                'preparado_en' => $preparado ? now() : null,
            ]);

            $pedidoCompleto = ! $pedido->detalles()
                ->where('preparado_detalle_pedido', false)
                ->exists();
            $nuevoEstadoPedido = $pedidoCompleto ? EstadoPedido::Preparado : EstadoPedido::Pendiente;

            $pedido->disableLogging();
            $pedido->update([
                'estado_pedido' => $nuevoEstadoPedido,
                'preparado_por' => $pedidoCompleto ? $usuario->id : null,
                'preparado_en' => $pedidoCompleto ? now() : null,
            ]);
            $pedido->enableLogging();

            activity(ActivityLogName::Orders->value)
                ->event(ActivityEvent::PreparationUpdated->value)
                ->performedOn($pedido)
                ->causedBy($usuario)
                ->withProperties([
                    'old' => [
                        'detalle_pedido_id' => $detallePedido->id,
                        'preparado_detalle_pedido' => $estadoDetalleAnterior,
                        'estado_pedido' => $estadoPedidoAnterior->value,
                    ],
                    'attributes' => [
                        'detalle_pedido_id' => $detallePedido->id,
                        'articulo_id' => $detallePedido->articulo_id,
                        'presentacion_articulo_id' => $detallePedido->presentacion_articulo_id,
                        'preparado_detalle_pedido' => $preparado,
                        'estado_pedido' => $nuevoEstadoPedido->value,
                    ],
                ])
                ->log($preparado ? 'Producto marcado como preparado' : 'Producto devuelto a pendiente');

            return $pedido->refresh();
        });
    }
}
