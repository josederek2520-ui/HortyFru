<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::UsersView->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->can(PermissionName::UsersView->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionName::UsersCreate->value)
            && $user->can(PermissionName::UsersAssignRole->value)
            && $user->can(PermissionName::EmployeesAssignUser->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return $user->can(PermissionName::UsersUpdate->value);
    }

    public function changeStatus(User $user, User $model): bool
    {
        return $user->can(PermissionName::UsersChangeStatus->value) && ! $user->is($model);
    }

    public function assignRole(User $user, User $model): bool
    {
        return $user->can(PermissionName::UsersAssignRole->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
