<?php

namespace Tests\Feature;

use App\Enums\ActivityLogName;
use App\Enums\PermissionName;
use App\Livewire\Panel\Clients\Index;
use App\Models\Activity;
use App\Models\Cliente;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_user_is_redirected_from_client_management(): void
    {
        $this->get(route('panel.clients.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_client_management(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('panel.clients.index'))->assertForbidden();
        Livewire::actingAs($user)->test(Index::class)->assertForbidden();
    }

    public function test_administrator_can_view_clients(): void
    {
        $administrator = $this->createAdministrator();
        Cliente::factory()->create(['razon_social' => 'Supermercados del Oriente S.A.']);

        $this->actingAs($administrator)
            ->get(route('panel.clients.index'))
            ->assertOk()
            ->assertSee('Supermercados del Oriente S.A.');
    }

    public function test_administrator_creates_client_with_normalized_data_and_activity_log(): void
    {
        $administrator = $this->createAdministrator();

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.razon_social', '  Supermercados   Ejemplo S.A. ')
            ->set('form.nit', ' 10-203-040-50 ')
            ->set('form.telefono_cliente', '71234567')
            ->set('form.email_cliente', ' COMPRAS@EJEMPLO.COM ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched(
                'toast',
                title: 'Cliente registrado',
                message: 'El cliente Supermercados Ejemplo S.A. fue registrado correctamente.',
                type: 'success',
            );

        $cliente = Cliente::query()->where('nit', '1020304050')->first();

        $this->assertNotNull($cliente);
        $this->assertSame('Supermercados Ejemplo S.A.', $cliente->razon_social);
        $this->assertSame('compras@ejemplo.com', $cliente->email_cliente);
        $this->assertTrue($cliente->activo_cliente);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::Clients->value,
            'event' => 'created',
            'subject_type' => Cliente::class,
            'subject_id' => $cliente->id,
            'causer_id' => $administrator->id,
        ]);
    }

    public function test_client_requires_business_name_and_unique_valid_nit(): void
    {
        $administrator = $this->createAdministrator();
        Cliente::factory()->create(['nit' => '123456789']);

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nit', '123456789')
            ->set('form.telefono_cliente', 'teléfono inválido')
            ->set('form.email_cliente', 'correo-invalido')
            ->call('save')
            ->assertHasErrors([
                'form.razon_social' => ['required'],
                'form.nit' => ['unique'],
                'form.telefono_cliente' => ['regex'],
                'form.email_cliente' => ['email'],
            ]);

        $this->assertSame(1, Cliente::query()->count());
    }

    public function test_administrator_updates_client_without_changing_its_nit_uniqueness(): void
    {
        $administrator = $this->createAdministrator();
        $cliente = Cliente::factory()->create([
            'razon_social' => 'Mercado Antiguo S.A.',
            'nit' => '1020304050',
        ]);

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openEditModal', $cliente->id)
            ->set('form.razon_social', 'Mercado Actual S.A.')
            ->set('form.email_cliente', 'contacto@mercado.bo')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Cliente actualizado', type: 'info');

        $cliente->refresh();
        $this->assertSame('Mercado Actual S.A.', $cliente->razon_social);
        $this->assertSame('1020304050', $cliente->nit);
        $this->assertSame('contacto@mercado.bo', $cliente->email_cliente);
    }

    public function test_administrator_deactivates_and_reactivates_client_without_deleting_it(): void
    {
        $administrator = $this->createAdministrator();
        $cliente = Cliente::factory()->create(['activo_cliente' => true]);
        $component = Livewire::actingAs($administrator)->test(Index::class);

        $component
            ->call('openStatusModal', $cliente->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Cliente desactivado', type: 'warning');

        $this->assertFalse($cliente->fresh()->activo_cliente);
        $this->assertModelExists($cliente);

        $component
            ->call('openStatusModal', $cliente->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Cliente activado', type: 'success');

        $this->assertTrue($cliente->fresh()->activo_cliente);
        $this->assertSame(2, Activity::query()
            ->where('log_name', ActivityLogName::Clients->value)
            ->where('event', 'status_changed')
            ->where('subject_id', $cliente->id)
            ->count());
    }

    public function test_view_only_user_does_not_see_client_actions(): void
    {
        $this->createAdministrator();
        $viewer = User::factory()->create();
        $cliente = Cliente::factory()->create();
        $role = Role::create(['name' => 'Consulta de clientes', 'guard_name' => 'web']);
        $role->givePermissionTo(PermissionName::ClientsView->value);
        $viewer->assignRole($role);

        Livewire::actingAs($viewer)
            ->test(Index::class)
            ->assertDontSee('wire:click="openCreateModal"', false)
            ->assertDontSee('wire:click="openEditModal('.$cliente->id.')"', false)
            ->assertDontSee('wire:click="openStatusModal('.$cliente->id.')"', false);
    }

    public function test_client_business_name_is_escaped_in_the_table(): void
    {
        $administrator = $this->createAdministrator();
        Cliente::factory()->create([
            'razon_social' => '<script>alert("riesgo")</script>',
        ]);

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("riesgo")</script>', false);
    }

    private function createAdministrator(): User
    {
        $administrator = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrator->refresh();
    }
}
