<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ClientsView->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Cliente $cliente): bool
    {
        return $user->can(PermissionName::ClientsView->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionName::ClientsCreate->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Cliente $cliente): bool
    {
        return $user->can(PermissionName::ClientsUpdate->value);
    }

    public function changeStatus(User $user, Cliente $cliente): bool
    {
        return $user->can(PermissionName::ClientsChangeStatus->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Cliente $cliente): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Cliente $cliente): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Cliente $cliente): bool
    {
        return false;
    }
}
