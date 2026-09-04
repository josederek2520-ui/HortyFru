<?php

namespace App\Actions\Pedidos;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelarPedidoAction
{
    public function __invoke(Pedido $pedido, string $motivo, User $usuario): Pedido
    {
        return DB::transaction(function () use ($pedido, $motivo, $usuario): Pedido {
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($pedido->id);

            if (! $pedido->estaPendiente()) {
                throw ValidationException::withMessages(['cancelacion' => 'Solo se pueden cancelar pedidos pendientes.']);
            }

            $pedido->disableLogging();
            $pedido->update([
                'estado_pedido' => EstadoPedido::Cancelado,
                'cancelado_por' => $usuario->id,
                'cancelado_en' => now(),
                'motivo_cancelacion_pedido' => $motivo,
            ]);
            $pedido->enableLogging();

            activity(ActivityLogName::Orders->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($pedido)
                ->causedBy($usuario)
                ->withProperties([
                    'old' => ['estado_pedido' => EstadoPedido::Pendiente->value],
                    'attributes' => [
                        'estado_pedido' => EstadoPedido::Cancelado->value,
                        'cancelado_por' => $usuario->id,
                        'cancelado_en' => $pedido->cancelado_en?->toDateTimeString(),
                        'motivo_cancelacion_pedido' => $motivo,
                    ],
                ])
                ->log('Pedido cancelado');

            return $pedido->refresh();
        });
    }
}
