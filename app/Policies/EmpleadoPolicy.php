<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Empleado;
use App\Models\User;

class EmpleadoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::EmployeesView->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Empleado $empleado): bool
    {
        return $user->can(PermissionName::EmployeesView->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionName::EmployeesCreate->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Empleado $empleado): bool
    {
        return $user->can(PermissionName::EmployeesUpdate->value);
    }

    public function changeStatus(User $user, Empleado $empleado): bool
    {
        return $user->can(PermissionName::EmployeesChangeStatus->value)
            && (int) $empleado->user_id !== (int) $user->id;
    }

    public function assignUser(User $user, Empleado $empleado): bool
    {
        return $user->can(PermissionName::EmployeesAssignUser->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Empleado $empleado): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Empleado $empleado): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Empleado $empleado): bool
    {
        return false;
    }
}
