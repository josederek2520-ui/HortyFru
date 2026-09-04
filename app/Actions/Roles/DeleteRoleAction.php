<?php

namespace App\Actions\Roles;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class DeleteRoleAction
{
    public function __invoke(Role $role): void
    {
        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => 'No puedes eliminar un rol que todavía tiene usuarios asignados.',
            ]);
        }

        DB::transaction(function () use ($role): void {
            $properties = [
                'old' => [
                    'name' => $role->name,
                    'permissions' => $role->permissions()->pluck('name')->sort()->values()->all(),
                ],
            ];

            $role->delete();

            activity(ActivityLogName::Roles->value)
                ->event(ActivityEvent::Deleted->value)
                ->performedOn($role)
                ->withProperties($properties)
                ->log('Rol eliminado');
        });
    }
}
