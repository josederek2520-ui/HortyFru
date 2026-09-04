<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Articulo;
use App\Models\User;

class ArticuloPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ArticlesView->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Articulo $articulo): bool
    {
        return $user->can(PermissionName::ArticlesView->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionName::ArticlesCreate->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Articulo $articulo): bool
    {
        return $user->can(PermissionName::ArticlesUpdate->value);
    }

    public function changeStatus(User $user, Articulo $articulo): bool
    {
        return $user->can(PermissionName::ArticlesChangeStatus->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Articulo $articulo): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Articulo $articulo): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Articulo $articulo): bool
    {
        return false;
    }
}
