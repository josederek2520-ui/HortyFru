<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Livewire\Panel\Employees\Index;
use App\Models\Empleado;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_user_is_redirected_from_employee_management(): void
    {
        $this->get(route('panel.employees.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_employee_management(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('panel.employees.index'))->assertForbidden();
        Livewire::actingAs($user)->test(Index::class)->assertForbidden();
    }

    public function test_administrator_can_view_employees(): void
    {
        $administrator = $this->createAdministrator();
        Empleado::factory()->create([
            'nombre_empleado' => 'Pedro',
            'apellido_empleado' => 'Quispe',
        ]);

        $this->actingAs($administrator)
            ->get(route('panel.employees.index'))
            ->assertOk()
            ->assertSee('Pedro Quispe');
    }

    public function test_administrator_creates_employee_with_optional_account(): void
    {
        $administrator = $this->createAdministrator();
        $account = User::factory()->create(['email' => 'pedro@example.com']);

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nombre_empleado', '  Pedro ')
            ->set('form.apellido_empleado', ' Quispe ')
            ->set('form.ci_empleado', ' 8456321 ')
            ->set('form.telefono_empleado', '71234567')
            ->set('form.direccion_empleado', ' Zona Central ')
            ->set('form.cargo_empleado', ' Preparador ')
            ->set('form.fecha_ingreso_empleado', '2026-01-15')
            ->set('form.user_id', $account->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched(
                'toast',
                title: 'Empleado registrado',
                message: 'El empleado Pedro Quispe fue registrado correctamente.',
                type: 'success',
            );

        $employee = Empleado::query()->where('ci_empleado', '8456321')->first();

        $this->assertNotNull($employee);
        $this->assertSame('Pedro', $employee->nombre_empleado);
        $this->assertSame('Zona Central', $employee->direccion_empleado);
        $this->assertTrue($employee->user->is($account));
    }

    public function test_employee_requires_identity_and_rejects_duplicate_ci(): void
    {
        $administrator = $this->createAdministrator();
        Empleado::factory()->create(['ci_empleado' => '1234567']);

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.ci_empleado', '1234567')
            ->call('save')
            ->assertHasErrors([
                'form.nombre_empleado' => ['required'],
                'form.apellido_empleado' => ['required'],
                'form.ci_empleado' => ['unique'],
                'form.cargo_empleado' => ['required'],
            ]);

        $this->assertSame(1, Empleado::query()->count());
    }

    public function test_administrator_updates_employee_without_losing_account(): void
    {
        $administrator = $this->createAdministrator();
        $account = User::factory()->create();
        $employee = Empleado::factory()->for($account, 'user')->create([
            'cargo_empleado' => 'Preparador',
        ]);

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openEditModal', $employee->id)
            ->set('form.cargo_empleado', 'Supervisor de preparación')
            ->set('form.direccion_empleado', 'Nueva dirección')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Empleado actualizado', type: 'info');

        $employee->refresh();
        $this->assertSame('Supervisor de preparación', $employee->cargo_empleado);
        $this->assertSame('Nueva dirección', $employee->direccion_empleado);
        $this->assertTrue($employee->user->is($account));
    }

    public function test_retiring_employee_blocks_account_and_reincorporating_does_not_enable_it(): void
    {
        $administrator = $this->createAdministrator();
        $account = User::factory()->create(['activo_usuario' => true]);
        $employee = Empleado::factory()->for($account, 'user')->create();
        $component = Livewire::actingAs($administrator)->test(Index::class);

        $component
            ->call('openStatusModal', $employee->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Empleado retirado', type: 'warning');

        $this->assertFalse($employee->fresh()->activo_empleado);
        $this->assertFalse($account->fresh()->activo_usuario);

        $component
            ->call('openStatusModal', $employee->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Empleado reincorporado', type: 'success');

        $this->assertTrue($employee->fresh()->activo_empleado);
        $this->assertFalse($account->fresh()->activo_usuario);
    }

    public function test_administrator_cannot_retire_employee_linked_to_own_account(): void
    {
        $administrator = $this->createAdministrator();
        $employee = Empleado::factory()->for($administrator, 'user')->create();

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openStatusModal', $employee->id)
            ->assertForbidden();

        $this->assertTrue($employee->fresh()->activo_empleado);
        $this->assertTrue($administrator->fresh()->activo_usuario);
    }

    public function test_view_only_user_does_not_see_employee_actions(): void
    {
        $this->createAdministrator();
        $viewer = User::factory()->create();
        $employee = Empleado::factory()->create();
        $role = Role::create(['name' => 'Consulta de empleados', 'guard_name' => 'web']);
        $role->givePermissionTo(PermissionName::EmployeesView->value);
        $viewer->assignRole($role);

        Livewire::actingAs($viewer)
            ->test(Index::class)
            ->assertDontSee('wire:click="openCreateModal"', false)
            ->assertDontSee('wire:click="openEditModal('.$employee->id.')"', false)
            ->assertDontSee('wire:click="openStatusModal('.$employee->id.')"', false);
    }

    private function createAdministrator(): User
    {
        $administrator = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrator->refresh();
    }
}
