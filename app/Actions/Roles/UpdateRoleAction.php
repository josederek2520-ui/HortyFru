<?php

namespace App\Actions\Roles;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UpdateRoleAction
{
    /** @param array{name: string, permissions: list<int>} $data */
    public function __invoke(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data): Role {
            $previousName = $role->name;
            $previousPermissions = $role->permissions()->pluck('name')->sort()->values()->all();

            $role->update(['name' => $data['name']]);
            $role->syncPermissions(Permission::query()->whereKey($data['permissions'])->get());
            $currentPermissions = $role->permissions()->pluck('name')->sort()->values()->all();

            if ($previousName !== $role->name || $previousPermissions !== $currentPermissions) {
                $event = $previousName === $role->name
                    ? ActivityEvent::PermissionsUpdated
                    : ActivityEvent::Updated;

                activity(ActivityLogName::Roles->value)
                    ->event($event->value)
                    ->performedOn($role)
                    ->withProperties([
                        'old' => [
                            'name' => $previousName,
                            'permissions' => $previousPermissions,
                        ],
                        'attributes' => [
                            'name' => $role->name,
                            'permissions' => $currentPermissions,
                        ],
                    ])
                    ->log('Rol y permisos actualizados');
            }

            return $role->refresh()->load('permissions:id,name');
        });
    }
}
