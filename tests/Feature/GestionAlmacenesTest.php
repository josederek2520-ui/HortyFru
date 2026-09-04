<?php

namespace Tests\Feature;

use App\Enums\ActivityLogName;
use App\Enums\PermissionName;
use App\Livewire\Panel\Almacenes\Index;
use App\Models\Activity;
use App\Models\Almacen;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GestionAlmacenesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_usuario_no_autenticado_es_redirigido_desde_gestion_de_almacenes(): void
    {
        $this->get(route('panel.almacenes.index'))->assertRedirect(route('login'));
    }

    public function test_usuario_sin_permiso_no_puede_ver_gestion_de_almacenes(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get(route('panel.almacenes.index'))->assertForbidden();
        Livewire::actingAs($usuario)->test(Index::class)->assertForbidden();
    }

    public function test_administrador_puede_ver_almacenes(): void
    {
        $administrador = $this->crearAdministrador();
        Almacen::factory()->create(['nombre_almacen' => 'Almacén central']);

        $this->actingAs($administrador)
            ->get(route('panel.almacenes.index'))
            ->assertOk()
            ->assertSee('Almacén central');
    }

    public function test_administrador_crea_almacen_con_datos_normalizados_y_auditoria(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nombre_almacen', '  Almacén   central  ')
            ->set('form.direccion_almacen', '  Av. Principal   250  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched(
                'toast',
                title: 'Almacén registrado',
                message: 'El almacén Almacén central fue registrado correctamente.',
                type: 'success',
            );

        $almacen = Almacen::query()->where('nombre_almacen', 'Almacén central')->first();

        $this->assertNotNull($almacen);
        $this->assertSame('Av. Principal 250', $almacen->direccion_almacen);
        $this->assertTrue($almacen->estado_almacen);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::Warehouses->value,
            'event' => 'created',
            'subject_type' => Almacen::class,
            'subject_id' => $almacen->id,
            'causer_id' => $administrador->id,
        ]);
    }

    public function test_almacen_requiere_nombre_y_rechaza_contenido_invalido(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.direccion_almacen', '<script>')
            ->call('save')
            ->assertHasErrors([
                'form.nombre_almacen' => ['required'],
                'form.direccion_almacen' => ['not_regex'],
            ]);

        $this->assertSame(0, Almacen::query()->count());
    }

    public function test_nombre_del_almacen_debe_ser_unico(): void
    {
        $administrador = $this->crearAdministrador();
        Almacen::factory()->create(['nombre_almacen' => 'Almacén norte']);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nombre_almacen', 'Almacén norte')
            ->call('save')
            ->assertHasErrors(['form.nombre_almacen' => ['unique']]);

        $this->assertSame(1, Almacen::query()->count());
    }

    public function test_administrador_actualiza_almacen_sin_conflicto_con_su_nombre(): void
    {
        $administrador = $this->crearAdministrador();
        $almacen = Almacen::factory()->create([
            'nombre_almacen' => 'Almacén antiguo',
            'direccion_almacen' => 'Calle Antigua 10',
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openEditModal', $almacen->id)
            ->set('form.nombre_almacen', 'Almacén renovado')
            ->set('form.direccion_almacen', 'Avenida Nueva 500')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Almacén actualizado', type: 'info');

        $almacen->refresh();
        $this->assertSame('Almacén renovado', $almacen->nombre_almacen);
        $this->assertSame('Avenida Nueva 500', $almacen->direccion_almacen);
    }

    public function test_administrador_desactiva_y_reactiva_almacen_sin_eliminarlo(): void
    {
        $administrador = $this->crearAdministrador();
        $almacen = Almacen::factory()->create(['estado_almacen' => true]);
        $componente = Livewire::actingAs($administrador)->test(Index::class);

        $componente
            ->call('openStatusModal', $almacen->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Almacén desactivado', type: 'warning');

        $this->assertFalse($almacen->fresh()->estado_almacen);
        $this->assertModelExists($almacen);

        $componente
            ->call('openStatusModal', $almacen->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Almacén activado', type: 'success');

        $this->assertTrue($almacen->fresh()->estado_almacen);
        $this->assertSame(2, Activity::query()
            ->where('log_name', ActivityLogName::Warehouses->value)
            ->where('event', 'status_changed')
            ->where('subject_id', $almacen->id)
            ->count());
    }

    public function test_busqueda_y_estado_filtran_almacenes(): void
    {
        $administrador = $this->crearAdministrador();
        Almacen::factory()->create(['nombre_almacen' => 'Almacén visible', 'direccion_almacen' => 'Zona Norte', 'estado_almacen' => false]);
        Almacen::factory()->create(['nombre_almacen' => 'Almacén oculto', 'direccion_almacen' => 'Zona Sur', 'estado_almacen' => false]);
        Almacen::factory()->create(['nombre_almacen' => 'Depósito operativo', 'direccion_almacen' => 'Zona Norte', 'estado_almacen' => true]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->set('search', 'Zona Norte')
            ->set('status', 'inactive')
            ->assertSee('Almacén visible')
            ->assertDontSee('Almacén oculto')
            ->assertDontSee('Depósito operativo');
    }

    public function test_usuario_de_solo_lectura_no_ve_acciones_de_almacenes(): void
    {
        $this->crearAdministrador();
        $usuarioConsulta = User::factory()->create();
        $almacen = Almacen::factory()->create();
        $rol = Role::create(['name' => 'Consulta de almacenes', 'guard_name' => 'web']);
        $rol->givePermissionTo(PermissionName::WarehousesView->value);
        $usuarioConsulta->assignRole($rol);

        Livewire::actingAs($usuarioConsulta)
            ->test(Index::class)
            ->assertDontSee('wire:click="openCreateModal"', false)
            ->assertDontSee('wire:click="openEditModal('.$almacen->id.')"', false)
            ->assertDontSee('wire:click="openStatusModal('.$almacen->id.')"', false);
    }

    public function test_contenido_del_almacen_se_muestra_escapado(): void
    {
        $administrador = $this->crearAdministrador();
        Almacen::factory()->create([
            'nombre_almacen' => '<script>alert("riesgo")</script>',
            'direccion_almacen' => '<img src=x onerror=alert(1)>',
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
