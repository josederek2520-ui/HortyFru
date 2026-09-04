<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Livewire\Panel\Users\Index;
use App\Models\Empleado;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_user_is_redirected_from_user_management(): void
    {
        $response = $this->get(route('panel.users.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_administrator_can_view_user_management(): void
    {
        $administrator = $this->createAdministrator(['name' => 'Administrador principal']);

        $response = $this->actingAs($administrator)->get(route('panel.users.index'));

        $response->assertOk();
        $response->assertSee('Cuentas del sistema');
        $response->assertSee('Administrador principal');
    }

    public function test_regular_user_cannot_view_user_management(): void
    {
        $this->createAdministrator();
        $regularUser = User::factory()->create();

        $response = $this->actingAs($regularUser)->get(route('panel.users.index'));

        $response->assertForbidden();
    }

    public function test_unauthorized_user_actions_are_not_rendered(): void
    {
        $this->createAdministrator();
        $viewer = User::factory()->create();
        $managedUser = User::factory()->create();
        $viewerRole = Role::create(['name' => 'Consulta de usuarios', 'guard_name' => 'web']);
        $viewerRole->givePermissionTo(Permission::findByName(PermissionName::UsersView->value, 'web'));
        $viewer->assignRole($viewerRole);

        $component = Livewire::actingAs($viewer)->test(Index::class);

        $component
            ->assertDontSee('wire:click="openCreateModal"', false)
            ->assertDontSee('wire:click="openEditModal('.$managedUser->id.')"', false)
            ->assertDontSee('wire:click="openStatusModal('.$managedUser->id.')"', false);

        $component
            ->call('openEditModal', $managedUser->id)
            ->assertForbidden();
    }

    public function test_administrator_creates_user_from_modal_form(): void
    {
        $administrator = $this->createAdministrator();
        $role = Role::findByName(RoleName::Administrator->value, 'web');
        $employee = Empleado::factory()->create([
            'nombre_empleado' => 'Ana',
            'apellido_empleado' => 'Pérez',
        ]);

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openCreateModal')
            ->assertSet('showFormModal', true)
            ->set('form.employeeId', $employee->id)
            ->set('form.email', 'ANA.PEREZ@EXAMPLE.COM')
            ->set('form.password', 'Segura123')
            ->set('form.password_confirmation', 'Segura123')
            ->set('form.activo_usuario', true)
            ->set('form.roleId', $role->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched(
                'toast',
                title: 'Cuenta creada',
                message: 'La cuenta de Ana Pérez fue creada correctamente.',
                type: 'success',
            );

        $createdUser = User::query()->where('email', 'ana.perez@example.com')->first();

        $this->assertNotNull($createdUser);
        $this->assertNull($createdUser->name);
        $this->assertTrue($createdUser->empleado->is($employee));
        $this->assertSame('Ana Pérez', $createdUser->display_name);
        $this->assertTrue($createdUser->activo_usuario);
        $this->assertTrue(Hash::check('Segura123', $createdUser->password));
        $this->assertTrue($createdUser->hasRole(RoleName::Administrator->value));
    }

    public function test_create_user_rejects_missing_and_duplicate_data(): void
    {
        $administrator = $this->createAdministrator(['email' => 'admin@example.com']);

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.email', 'admin@example.com')
            ->call('save')
            ->assertHasErrors([
                'form.employeeId' => ['required'],
                'form.email' => ['unique'],
                'form.password' => ['required'],
                'form.password_confirmation' => ['required'],
                'form.roleId' => ['required'],
            ]);

        $this->assertSame(1, User::query()->count());
    }

    public function test_administrator_updates_user_without_replacing_existing_password(): void
    {
        $administrator = $this->createAdministrator();
        $managedUser = User::factory()->create([
            'email' => 'anterior@example.com',
        ]);
        Empleado::factory()->for($managedUser, 'user')->create([
            'nombre_empleado' => 'Nombre',
            'apellido_empleado' => 'Anterior',
        ]);
        $managedUser->assignRole(RoleName::Administrator->value);
        $originalPassword = $managedUser->password;

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openEditModal', $managedUser->id)
            ->set('form.email', 'actualizado@example.com')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched(
                'toast',
                title: 'Cuenta actualizada',
                message: 'Los datos de Nombre Anterior fueron actualizados.',
                type: 'info',
            );

        $managedUser->refresh();

        $this->assertSame('Nombre Anterior', $managedUser->load('empleado')->display_name);
        $this->assertSame('actualizado@example.com', $managedUser->email);
        $this->assertSame($originalPassword, $managedUser->password);
    }

    public function test_user_with_update_permission_can_edit_without_assigning_roles(): void
    {
        $this->createAdministrator();
        $editor = User::factory()->create();
        $managedUser = User::factory()->create(['email' => 'anterior@example.com']);
        Empleado::factory()->for($managedUser, 'user')->create();
        $editorRole = Role::create(['name' => 'Editor de usuarios', 'guard_name' => 'web']);
        $editorRole->givePermissionTo([
            PermissionName::UsersView->value,
            PermissionName::UsersUpdate->value,
        ]);
        $editor->assignRole($editorRole);
        $managedUser->assignRole(RoleName::Administrator->value);

        Livewire::actingAs($editor)
            ->test(Index::class)
            ->call('openEditModal', $managedUser->id)
            ->set('form.email', 'actualizado@example.com')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false);

        $this->assertSame('actualizado@example.com', $managedUser->fresh()->email);
        $this->assertTrue($managedUser->fresh()->hasRole(RoleName::Administrator->value));
    }

    public function test_administrator_can_deactivate_and_reactivate_another_user(): void
    {
        $administrator = $this->createAdministrator();
        $managedUser = User::factory()->create();
        $component = Livewire::actingAs($administrator)->test(Index::class);

        $component
            ->call('openStatusModal', $managedUser->id)
            ->call('changeStatus')
            ->assertSet('showStatusModal', false)
            ->assertDispatched('toast', title: 'Cuenta desactivada', type: 'warning');

        $this->assertFalse($managedUser->fresh()->activo_usuario);

        $component
            ->call('openStatusModal', $managedUser->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Cuenta activada', type: 'success');

        $this->assertTrue($managedUser->fresh()->activo_usuario);
    }

    public function test_administrator_cannot_deactivate_own_account(): void
    {
        $administrator = $this->createAdministrator();

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openStatusModal', $administrator->id)
            ->assertForbidden();

        $this->assertTrue($administrator->fresh()->activo_usuario);
    }

    public function test_regular_user_cannot_open_user_management_component(): void
    {
        $this->createAdministrator();
        $regularUser = User::factory()->create();

        Livewire::actingAs($regularUser)->test(Index::class)->assertForbidden();
    }

    /** @param array<string, mixed> $attributes */
    private function createAdministrator(array $attributes = []): User
    {
        $administrator = User::factory()->create($attributes);
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrator->refresh();
    }
}
