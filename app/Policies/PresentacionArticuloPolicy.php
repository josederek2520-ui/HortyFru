<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\PresentacionArticulo;
use App\Models\User;

class PresentacionArticuloPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ArticlePresentationsView->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PresentacionArticulo $presentacionArticulo): bool
    {
        return $user->can(PermissionName::ArticlePresentationsView->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionName::ArticlePresentationsCreate->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PresentacionArticulo $presentacionArticulo): bool
    {
        return $user->can(PermissionName::ArticlePresentationsUpdate->value);
    }

    public function changeStatus(User $user, PresentacionArticulo $presentacionArticulo): bool
    {
        return $user->can(PermissionName::ArticlePresentationsChangeStatus->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PresentacionArticulo $presentacionArticulo): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PresentacionArticulo $presentacionArticulo): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PresentacionArticulo $presentacionArticulo): bool
    {
        return false;
    }
}
