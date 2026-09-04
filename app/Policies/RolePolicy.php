<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::RolesView->value);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can(PermissionName::RolesView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::RolesCreate->value)
            && $user->can(PermissionName::RolesAssignPermissions->value);
    }

    public function update(User $user, Role $role): bool
    {
        return $role->name !== RoleName::SuperAdministrator->value
            && $user->can(PermissionName::RolesUpdate->value)
            && $user->can(PermissionName::RolesAssignPermissions->value);
    }

    public function delete(User $user, Role $role): bool
    {
        return $role->name !== RoleName::SuperAdministrator->value
            && $user->can(PermissionName::RolesDelete->value);
    }
}
