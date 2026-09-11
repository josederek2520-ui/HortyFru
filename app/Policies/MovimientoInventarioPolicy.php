<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\MovimientoInventario;
use App\Models\User;

class MovimientoInventarioPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::InventoryMovementsView->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MovimientoInventario $movimientoInventario): bool
    {
        return $user->can(PermissionName::InventoryMovementsView->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MovimientoInventario $movimientoInventario): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MovimientoInventario $movimientoInventario): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MovimientoInventario $movimientoInventario): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MovimientoInventario $movimientoInventario): bool
    {
        return false;
    }
}
