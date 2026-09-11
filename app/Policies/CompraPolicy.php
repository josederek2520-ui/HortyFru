<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Compra;
use App\Models\User;

class CompraPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::PurchasesView->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Compra $compra): bool
    {
        return $user->can(PermissionName::PurchasesView->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionName::PurchasesCreate->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Compra $compra): bool
    {
        return $compra->estaEnBorrador()
            && $user->can(PermissionName::PurchasesUpdate->value);
    }

    public function cancel(User $user, Compra $compra): bool
    {
        return $compra->sePuedeCancelar()
            && $user->can(PermissionName::PurchasesCancel->value);
    }

    public function register(User $user, Compra $compra): bool
    {
        return $compra->estaEnBorrador()
            && $user->can(PermissionName::PurchasesRegister->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Compra $compra): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Compra $compra): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Compra $compra): bool
    {
        return false;
    }
}
