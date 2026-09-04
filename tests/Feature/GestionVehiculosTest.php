<?php

namespace Tests\Feature;

use App\Enums\ActivityLogName;
use App\Enums\PermissionName;
use App\Livewire\Panel\Vehiculos\Index;
use App\Models\Activity;
use App\Models\User;
use App\Models\Vehiculo;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GestionVehiculosTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_usuario_no_autenticado_es_redirigido_desde_gestion_de_vehiculos(): void
    {
        $this->get(route('panel.vehiculos.index'))->assertRedirect(route('login'));
    }

    public function test_usuario_sin_permiso_no_puede_ver_gestion_de_vehiculos(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get(route('panel.vehiculos.index'))->assertForbidden();
        Livewire::actingAs($usuario)->test(Index::class)->assertForbidden();
    }

    public function test_administrador_puede_ver_vehiculos(): void
    {
        $administrador = $this->crearAdministrador();
        Vehiculo::factory()->create(['placa_vehiculo' => 'ABC-123']);

        $this->actingAs($administrador)
            ->get(route('panel.vehiculos.index'))
            ->assertOk()
            ->assertSee('ABC-123');
    }

    public function test_administrador_crea_vehiculo_con_datos_normalizados_y_auditoria(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.placa_vehiculo', ' ab c-123 ')
            ->set('form.marca_vehiculo', '  Toyota   Motors  ')
            ->set('form.tipo_vehiculo', '  Camioneta   frigorífica  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched(
                'toast',
                title: 'Vehículo registrado',
                message: 'El vehículo ABC-123 fue registrado correctamente.',
                type: 'success',
            );

        $vehiculo = Vehiculo::query()->where('placa_vehiculo', 'ABC-123')->first();

        $this->assertNotNull($vehiculo);
        $this->assertSame('Toyota Motors', $vehiculo->marca_vehiculo);
        $this->assertSame('Camioneta frigorífica', $vehiculo->tipo_vehiculo);
        $this->assertTrue($vehiculo->estado_vehiculo);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::Vehicles->value,
            'event' => 'created',
            'subject_type' => Vehiculo::class,
            'subject_id' => $vehiculo->id,
            'causer_id' => $administrador->id,
        ]);
    }

    public function test_vehiculo_requiere_placa_y_tipo_y_rechaza_placa_invalida(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.placa_vehiculo', 'ABC@123')
            ->call('save')
            ->assertHasErrors([
                'form.placa_vehiculo' => ['regex'],
                'form.tipo_vehiculo' => ['required'],
            ]);

        $this->assertSame(0, Vehiculo::query()->count());
    }

    public function test_placa_del_vehiculo_debe_ser_unica(): void
    {
        $administrador = $this->crearAdministrador();
        Vehiculo::factory()->create(['placa_vehiculo' => 'XYZ-789']);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.placa_vehiculo', 'xyz-789')
            ->set('form.tipo_vehiculo', 'Camión')
            ->call('save')
            ->assertHasErrors(['form.placa_vehiculo' => ['unique']]);

        $this->assertSame(1, Vehiculo::query()->count());
    }

    public function test_administrador_actualiza_vehiculo_sin_conflicto_con_su_placa(): void
    {
        $administrador = $this->crearAdministrador();
        $vehiculo = Vehiculo::factory()->create([
            'placa_vehiculo' => 'ABC-123',
            'marca_vehiculo' => 'Toyota',
            'tipo_vehiculo' => 'Camioneta',
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openEditModal', $vehiculo->id)
            ->set('form.marca_vehiculo', 'Nissan')
            ->set('form.tipo_vehiculo', 'Camión')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Vehículo actualizado', type: 'info');

        $vehiculo->refresh();
        $this->assertSame('ABC-123', $vehiculo->placa_vehiculo);
        $this->assertSame('Nissan', $vehiculo->marca_vehiculo);
        $this->assertSame('Camión', $vehiculo->tipo_vehiculo);
    }

    public function test_administrador_desactiva_y_reactiva_vehiculo_sin_eliminarlo(): void
    {
        $administrador = $this->crearAdministrador();
        $vehiculo = Vehiculo::factory()->create(['estado_vehiculo' => true]);
        $componente = Livewire::actingAs($administrador)->test(Index::class);

        $componente
            ->call('openStatusModal', $vehiculo->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Vehículo desactivado', type: 'warning');

        $this->assertFalse($vehiculo->fresh()->estado_vehiculo);
        $this->assertModelExists($vehiculo);

        $componente
            ->call('openStatusModal', $vehiculo->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Vehículo activado', type: 'success');

        $this->assertTrue($vehiculo->fresh()->estado_vehiculo);
        $this->assertSame(2, Activity::query()
            ->where('log_name', ActivityLogName::Vehicles->value)
            ->where('event', 'status_changed')
            ->where('subject_id', $vehiculo->id)
            ->count());
    }

    public function test_busqueda_y_estado_filtran_vehiculos(): void
    {
        $administrador = $this->crearAdministrador();
        Vehiculo::factory()->create(['placa_vehiculo' => 'VISIBLE-1', 'marca_vehiculo' => 'Toyota', 'estado_vehiculo' => false]);
        Vehiculo::factory()->create(['placa_vehiculo' => 'OCULTO-1', 'marca_vehiculo' => 'Nissan', 'estado_vehiculo' => false]);
        Vehiculo::factory()->create(['placa_vehiculo' => 'ACTIVO-1', 'marca_vehiculo' => 'Toyota', 'estado_vehiculo' => true]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->set('search', 'Toyota')
            ->set('status', 'inactive')
            ->assertSee('VISIBLE-1')
            ->assertDontSee('OCULTO-1')
            ->assertDontSee('ACTIVO-1');
    }

    public function test_usuario_de_solo_lectura_no_ve_acciones_de_vehiculos(): void
    {
        $this->crearAdministrador();
        $usuarioConsulta = User::factory()->create();
        $vehiculo = Vehiculo::factory()->create();
        $rol = Role::create(['name' => 'Consulta de vehículos', 'guard_name' => 'web']);
        $rol->givePermissionTo(PermissionName::VehiclesView->value);
        $usuarioConsulta->assignRole($rol);

        Livewire::actingAs($usuarioConsulta)
            ->test(Index::class)
            ->assertDontSee('wire:click="openCreateModal"', false)
            ->assertDontSee('wire:click="openEditModal('.$vehiculo->id.')"', false)
            ->assertDontSee('wire:click="openStatusModal('.$vehiculo->id.')"', false);
    }

    public function test_contenido_del_vehiculo_se_muestra_escapado(): void
    {
        $administrador = $this->crearAdministrador();
        Vehiculo::factory()->create([
            'placa_vehiculo' => 'SAFE-123',
            'marca_vehiculo' => '<script>alert("riesgo")</script>',
            'tipo_vehiculo' => '<img src=x onerror=alert(1)>',
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("riesgo")</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    private function crearAdministrador(): User
    {
        $administrador = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrador->refresh();
    }
}
