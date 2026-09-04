<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permissionName) {
            Permission::findOrCreate($permissionName->value, 'web');
        }

        $allPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->get();

        $superAdministrator = Role::findOrCreate(RoleName::SuperAdministrator->value, 'web');
        $superAdministrator->syncPermissions($allPermissions);

        $administrator = Role::findOrCreate(RoleName::Administrator->value, 'web');
        $administrator->syncPermissions($allPermissions);

        if (! User::role($superAdministrator)->exists()) {
            User::query()
                ->where('activo_usuario', true)
                ->oldest('id')
                ->first()
                ?->assignRole($superAdministrator);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
