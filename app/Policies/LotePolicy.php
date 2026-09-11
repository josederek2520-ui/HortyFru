<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Lote;
use App\Models\User;

class LotePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::LotsView->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Lote $lote): bool
    {
        return $user->can(PermissionName::LotsView->value);
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
    public function update(User $user, Lote $lote): bool
    {
        return $user->can(PermissionName::LotsUpdate->value);
    }

    public function changeStatus(User $user, Lote $lote): bool
    {
        return $lote->estado_lote->sePuedeCambiarManualmente()
            && $user->can(PermissionName::LotsChangeStatus->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Lote $lote): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Lote $lote): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Lote $lote): bool
    {
        return false;
    }
}
