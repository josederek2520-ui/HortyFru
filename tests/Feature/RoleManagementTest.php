<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Livewire\Panel\Roles\Index;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_user_is_redirected_from_role_management(): void
    {
        $this->get(route('panel.roles.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_role_management(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('panel.roles.index'))->assertForbidden();
        Livewire::actingAs($user)->test(Index::class)->assertForbidden();
    }

    public function test_super_administrator_can_view_roles_and_permissions(): void
    {
        $administrator = $this->createSuperAdministrator();

        $this->actingAs($administrator)
            ->get(route('panel.roles.index'))
            ->assertOk()
            ->assertSee('Roles del sistema')
            ->assertSee(RoleName::SuperAdministrator->value);
    }

    public function test_administrator_can_create_role_with_selected_permissions(): void
    {
        $administrator = $this->createSuperAdministrator();
        $permissionIds = Permission::query()
            ->whereIn('name', [PermissionName::UsersView->value, PermissionName::UsersCreate->value])
            ->pluck('id')
            ->all();

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.name', '  Encargado   de almacén ')
            ->set('form.permissions', $permissionIds)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched(
                'toast',
                title: 'Rol creado',
                message: 'El rol Encargado de almacén fue creado correctamente.',
                type: 'success',
            );

        $role = Role::findByName('Encargado de almacén', 'web');

        $this->assertCount(2, $role->permissions);
        $this->assertTrue($role->hasPermissionTo(PermissionName::UsersView->value));
    }

    public function test_role_requires_unique_name_and_at_least_one_permission(): void
    {
        $administrator = $this->createSuperAdministrator();

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.name', RoleName::Administrator->value)
            ->set('form.permissions', [])
            ->call('save')
            ->assertHasErrors([
                'form.name' => ['unique'],
                'form.permissions' => ['required'],
            ]);
    }

    public function test_administrator_can_update_an_unprotected_role(): void
    {
        $administrator = $this->createSuperAdministrator();
        $role = Role::create(['name' => 'Vendedor', 'guard_name' => 'web']);
        $initialPermission = Permission::findByName(PermissionName::UsersView->value, 'web');
        $newPermission = Permission::findByName(PermissionName::UsersUpdate->value, 'web');
        $role->givePermissionTo($initialPermission);

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openEditModal', $role->id)
            ->set('form.name', 'Supervisor de ventas')
            ->set('form.permissions', [$newPermission->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched(
                'toast',
                title: 'Rol actualizado',
                message: 'El rol Supervisor de ventas fue actualizado.',
                type: 'info',
            );

        $role->refresh();
        $this->assertSame('Supervisor de ventas', $role->name);
        $this->assertTrue($role->hasPermissionTo($newPermission));
        $this->assertFalse($role->hasPermissionTo($initialPermission));
    }

    public function test_super_administrator_role_cannot_be_edited_or_deleted(): void
    {
        $administrator = $this->createSuperAdministrator();
        $protectedRole = Role::findByName(RoleName::SuperAdministrator->value, 'web');

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openEditModal', $protectedRole->id)
            ->assertForbidden();

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openDeleteModal', $protectedRole->id)
            ->assertForbidden();
    }

    public function test_role_with_assigned_users_cannot_be_deleted(): void
    {
        $administrator = $this->createSuperAdministrator();
        $role = Role::create(['name' => 'Comprador', 'guard_name' => 'web']);
        User::factory()->create()->assignRole($role);

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openDeleteModal', $role->id)
            ->call('delete')
            ->assertHasErrors(['role']);

        $this->assertNotNull($role->fresh());
    }

    public function test_unused_role_can_be_deleted(): void
    {
        $administrator = $this->createSuperAdministrator();
        $role = Role::create(['name' => 'Temporal', 'guard_name' => 'web']);

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openDeleteModal', $role->id)
            ->call('delete')
            ->assertHasNoErrors()
            ->assertSet('showDeleteModal', false)
            ->assertDispatched(
                'toast',
                title: 'Rol eliminado',
                message: 'El rol Temporal fue eliminado.',
                type: 'error',
            );

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    private function createSuperAdministrator(): User
    {
        $administrator = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrator->refresh();
    }
}
