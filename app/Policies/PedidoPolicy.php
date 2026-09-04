<?php

namespace App\Policies;

use App\Enums\EstadoPedido;
use App\Enums\PermissionName;
use App\Models\Pedido;
use App\Models\User;

class PedidoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::OrdersView->value);
    }

    public function view(User $user, Pedido $pedido): bool
    {
        return $user->can(PermissionName::OrdersView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::OrdersCreate->value);
    }

    public function update(User $user, Pedido $pedido): bool
    {
        return $pedido->estaPendiente()
            && ! $pedido->preparacionIniciada()
            && $user->can(PermissionName::OrdersUpdate->value);
    }

    public function cancel(User $user, Pedido $pedido): bool
    {
        return $pedido->estaPendiente() && $user->can(PermissionName::OrdersCancel->value);
    }

    public function prepare(User $user, Pedido $pedido): bool
    {
        return in_array($pedido->estado_pedido, [EstadoPedido::Pendiente, EstadoPedido::Preparado], true)
            && $user->can(PermissionName::OrdersPrepare->value);
    }

    public function delete(User $user, Pedido $pedido): bool
    {
        return false;
    }

    public function restore(User $user, Pedido $pedido): bool
    {
        return false;
    }

    public function forceDelete(User $user, Pedido $pedido): bool
    {
        return false;
    }
}
