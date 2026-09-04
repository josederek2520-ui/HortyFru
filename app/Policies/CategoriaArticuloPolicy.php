<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\CategoriaArticulo;
use App\Models\User;

class CategoriaArticuloPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ArticleCategoriesView->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CategoriaArticulo $categoriaArticulo): bool
    {
        return $user->can(PermissionName::ArticleCategoriesView->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionName::ArticleCategoriesCreate->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CategoriaArticulo $categoriaArticulo): bool
    {
        return $user->can(PermissionName::ArticleCategoriesUpdate->value);
    }

    public function changeStatus(User $user, CategoriaArticulo $categoriaArticulo): bool
    {
        return $user->can(PermissionName::ArticleCategoriesChangeStatus->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CategoriaArticulo $categoriaArticulo): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CategoriaArticulo $categoriaArticulo): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CategoriaArticulo $categoriaArticulo): bool
    {
        return false;
    }
}
