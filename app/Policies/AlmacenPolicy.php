<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Almacen;
use App\Models\User;

class AlmacenPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::WarehousesView->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Almacen $almacen): bool
    {
        return $user->can(PermissionName::WarehousesView->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionName::WarehousesCreate->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Almacen $almacen): bool
    {
        return $user->can(PermissionName::WarehousesUpdate->value);
    }

    public function changeStatus(User $user, Almacen $almacen): bool
    {
        return $user->can(PermissionName::WarehousesChangeStatus->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Almacen $almacen): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Almacen $almacen): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Almacen $almacen): bool
    {
        return false;
    }
}
