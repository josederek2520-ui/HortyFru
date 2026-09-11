<?php

namespace Tests\Feature;

use App\Enums\ActivityLogName;
use App\Enums\PermissionName;
use App\Livewire\Panel\Branches\Index;
use App\Models\Activity;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_user_is_redirected_from_branch_management(): void
    {
        $this->get(route('panel.branches.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_branch_management(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('panel.branches.index'))->assertForbidden();
        Livewire::actingAs($user)->test(Index::class)->assertForbidden();
    }

    public function test_administrator_can_view_branches_with_their_clients(): void
    {
        $administrator = $this->createAdministrator();
        $cliente = Cliente::factory()->create(['razon_social' => 'Mercado Los Pinos S.A.']);
        Sucursal::factory()->for($cliente)->create(['nombre_sucursal' => 'Sucursal Norte']);

        $this->actingAs($administrator)
            ->get(route('panel.branches.index'))
            ->assertOk()
            ->assertSee('Sucursal Norte')
            ->assertSee('Mercado Los Pinos S.A.');
    }

    public function test_existing_clients_enable_branch_creation_and_remain_available_after_closing_form(): void
    {
        $administrator = $this->createAdministrator();
        $cliente = Cliente::factory()->create(['razon_social' => 'Cliente disponible sin abrir formulario']);

        $component = Livewire::actingAs($administrator)->test(Index::class);

        $component->assertDontSee('Primero necesitas un cliente')
            ->assertSee('Registrar una nueva sucursal')
            ->assertSee($cliente->razon_social)
            ->call('openCreateModal')
            ->call('closeFormModal')
            ->assertDontSee('Primero necesitas un cliente')
            ->assertSee('Registrar una nueva sucursal')
            ->assertSee($cliente->razon_social);

        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$component->html());
        $xpath = new \DOMXPath($document);

        $this->assertSame(0, $xpath->query('//button[@*[name()="wire:click"]="openCreateModal"]/@disabled')->length);
        $this->assertSame(1, $xpath->query('//select[@*[name()="wire:model.live"]="client"]/option[@value="'.$cliente->id.'"]')->length);
    }

    public function test_branch_creation_warns_when_no_clients_exist(): void
    {
        Livewire::actingAs($this->createAdministrator())
            ->test(Index::class)
            ->assertSee('Primero necesitas un cliente')
            ->assertSee('Primero registra un cliente');
    }

    public function test_administrator_creates_branch_with_normalized_data_and_activity_log(): void
    {
        $administrator = $this->createAdministrator();
        $cliente = Cliente::factory()->create();

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.cliente_id', $cliente->id)
            ->set('form.nombre_sucursal', '  Sucursal   Central  ')
            ->set('form.direccion_sucursal', '  Av. Principal   123  ')
            ->set('form.telefono_sucursal', ' 71234567 ')
            ->set('form.referencia_sucursal', '  Frente   a la plaza  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched(
                'toast',
                title: 'Sucursal registrada',
                message: 'La sucursal Sucursal Central fue registrada correctamente.',
                type: 'success',
            );

        $sucursal = Sucursal::query()->where('nombre_sucursal', 'Sucursal Central')->first();

        $this->assertNotNull($sucursal);
        $this->assertSame($cliente->id, $sucursal->cliente_id);
        $this->assertSame('Av. Principal 123', $sucursal->direccion_sucursal);
        $this->assertSame('Frente a la plaza', $sucursal->referencia_sucursal);
        $this->assertTrue($sucursal->activo_sucursal);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::Branches->value,
            'event' => 'created',
            'subject_type' => Sucursal::class,
            'subject_id' => $sucursal->id,
            'causer_id' => $administrator->id,
        ]);
    }

    public function test_branch_requires_client_name_and_address_and_rejects_invalid_phone(): void
    {
        $administrator = $this->createAdministrator();

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.telefono_sucursal', 'teléfono inválido')
            ->call('save')
            ->assertHasErrors([
                'form.cliente_id' => ['required'],
                'form.nombre_sucursal' => ['required'],
                'form.direccion_sucursal' => ['required'],
                'form.telefono_sucursal' => ['regex'],
            ]);

        $this->assertSame(0, Sucursal::query()->count());
    }

    public function test_branch_name_is_unique_within_each_client(): void
    {
        $administrator = $this->createAdministrator();
        $firstClient = Cliente::factory()->create();
        $secondClient = Cliente::factory()->create();
        Sucursal::factory()->for($firstClient)->create(['nombre_sucursal' => 'Sucursal Centro']);

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.cliente_id', $firstClient->id)
            ->set('form.nombre_sucursal', 'Sucursal Centro')
            ->set('form.direccion_sucursal', 'Calle Uno 123')
            ->call('save')
            ->assertHasErrors(['form.nombre_sucursal' => ['unique']])
            ->set('form.cliente_id', $secondClient->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, Sucursal::query()->where('nombre_sucursal', 'Sucursal Centro')->count());
    }

    public function test_administrator_updates_branch_without_conflicting_with_its_own_name(): void
    {
        $administrator = $this->createAdministrator();
        $sucursal = Sucursal::factory()->create([
            'nombre_sucursal' => 'Sucursal Antigua',
            'direccion_sucursal' => 'Calle Antigua 10',
        ]);

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openEditModal', $sucursal->id)
            ->set('form.nombre_sucursal', 'Sucursal Renovada')
            ->set('form.direccion_sucursal', 'Avenida Nueva 500')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Sucursal actualizada', type: 'info');

        $sucursal->refresh();
        $this->assertSame('Sucursal Renovada', $sucursal->nombre_sucursal);
        $this->assertSame('Avenida Nueva 500', $sucursal->direccion_sucursal);
    }

    public function test_administrator_deactivates_and_reactivates_branch_without_deleting_it(): void
    {
        $administrator = $this->createAdministrator();
        $sucursal = Sucursal::factory()->create(['activo_sucursal' => true]);
        $component = Livewire::actingAs($administrator)->test(Index::class);

        $component
            ->call('openStatusModal', $sucursal->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Sucursal desactivada', type: 'warning');

        $this->assertFalse($sucursal->fresh()->activo_sucursal);
        $this->assertModelExists($sucursal);

        $component
            ->call('openStatusModal', $sucursal->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Sucursal activada', type: 'success');

        $this->assertTrue($sucursal->fresh()->activo_sucursal);
        $this->assertSame(2, Activity::query()
            ->where('log_name', ActivityLogName::Branches->value)
            ->where('event', 'status_changed')
            ->where('subject_id', $sucursal->id)
            ->count());
    }

    public function test_only_active_branches_of_active_clients_are_available_for_operations(): void
    {
        $activeClient = Cliente::factory()->create(['activo_cliente' => true]);
        $inactiveClient = Cliente::factory()->create(['activo_cliente' => false]);
        $availableBranch = Sucursal::factory()->for($activeClient)->create(['activo_sucursal' => true]);
        Sucursal::factory()->for($activeClient)->create(['activo_sucursal' => false]);
        Sucursal::factory()->for($inactiveClient)->create(['activo_sucursal' => true]);

        $availableBranches = Sucursal::query()->availableForOperations()->get();

        $this->assertCount(1, $availableBranches);
        $this->assertTrue($availableBranches->first()->is($availableBranch));
    }

    public function test_filters_branches_by_client_and_status(): void
    {
        $administrator = $this->createAdministrator();
        $selectedClient = Cliente::factory()->create();
        $otherClient = Cliente::factory()->create();
        Sucursal::factory()->for($selectedClient)->create(['nombre_sucursal' => 'Sucursal Seleccionada', 'activo_sucursal' => false]);
        Sucursal::factory()->for($otherClient)->create(['nombre_sucursal' => 'Sucursal Oculta', 'activo_sucursal' => false]);
        Sucursal::factory()->for($selectedClient)->create(['nombre_sucursal' => 'Sucursal Activa', 'activo_sucursal' => true]);

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->set('client', (string) $selectedClient->id)
            ->set('status', 'inactive')
            ->assertSee('Sucursal Seleccionada')
            ->assertDontSee('Sucursal Oculta')
            ->assertDontSee('Sucursal Activa');
    }

    public function test_view_only_user_does_not_see_branch_actions(): void
    {
        $this->createAdministrator();
        $viewer = User::factory()->create();
        $sucursal = Sucursal::factory()->create();
        $role = Role::create(['name' => 'Consulta de sucursales', 'guard_name' => 'web']);
        $role->givePermissionTo(PermissionName::BranchesView->value);
        $viewer->assignRole($role);

        Livewire::actingAs($viewer)
            ->test(Index::class)
            ->assertDontSee('wire:click="openCreateModal"', false)
            ->assertDontSee('wire:click="openEditModal('.$sucursal->id.')"', false)
            ->assertDontSee('wire:click="openStatusModal('.$sucursal->id.')"', false);
    }

    public function test_user_content_is_escaped_in_branch_table(): void
    {
        $administrator = $this->createAdministrator();
        Sucursal::factory()->create([
            'nombre_sucursal' => '<script>alert("riesgo")</script>',
            'direccion_sucursal' => '<img src=x onerror=alert(1)>',
        ]);

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("riesgo")</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    private function createAdministrator(): User
    {
        $administrator = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrator->refresh();
    }
}
