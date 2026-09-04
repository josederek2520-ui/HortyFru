<?php

namespace App\Actions\Roles;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateRoleAction
{
    /** @param array{name: string, permissions: list<int>} $data */
    public function __invoke(array $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            $role = Role::query()->create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);

            $role->syncPermissions(Permission::query()->whereKey($data['permissions'])->get());

            activity(ActivityLogName::Roles->value)
                ->event(ActivityEvent::Created->value)
                ->performedOn($role)
                ->withProperties([
                    'attributes' => [
                        'name' => $role->name,
                        'permissions' => $role->permissions()->pluck('name')->sort()->values()->all(),
                    ],
                ])
                ->log('Rol creado');

            return $role->load('permissions:id,name');
        });
    }
}
