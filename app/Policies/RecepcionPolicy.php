<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Recepcion;
use App\Models\User;

class RecepcionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ReceptionsView->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Recepcion $recepcion): bool
    {
        return $user->can(PermissionName::ReceptionsView->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionName::ReceptionsCreate->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Recepcion $recepcion): bool
    {
        return $recepcion->estaEnBorrador()
            && $user->can(PermissionName::ReceptionsUpdate->value);
    }

    public function cancel(User $user, Recepcion $recepcion): bool
    {
        return $recepcion->sePuedeCancelar()
            && $user->can(PermissionName::ReceptionsCancel->value);
    }

    public function confirm(User $user, Recepcion $recepcion): bool
    {
        return $recepcion->estaEnBorrador()
            && $user->can(PermissionName::ReceptionsConfirm->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Recepcion $recepcion): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Recepcion $recepcion): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Recepcion $recepcion): bool
    {
        return false;
    }
}
