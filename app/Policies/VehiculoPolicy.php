<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;
use App\Models\Vehiculo;

class VehiculoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::VehiclesView->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Vehiculo $vehiculo): bool
    {
        return $user->can(PermissionName::VehiclesView->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionName::VehiclesCreate->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vehiculo $vehiculo): bool
    {
        return $user->can(PermissionName::VehiclesUpdate->value);
    }

    public function changeStatus(User $user, Vehiculo $vehiculo): bool
    {
        return $user->can(PermissionName::VehiclesChangeStatus->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vehiculo $vehiculo): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Vehiculo $vehiculo): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Vehiculo $vehiculo): bool
    {
        return false;
    }
}
