<?php

namespace Tests\Feature;

use App\Enums\ActivityLogName;
use App\Enums\PermissionName;
use App\Livewire\Panel\Proveedores\Index;
use App\Models\Activity;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GestionProveedoresTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_usuario_no_autenticado_es_redirigido_desde_gestion_de_proveedores(): void
    {
        $this->get(route('panel.proveedores.index'))->assertRedirect(route('login'));
    }

    public function test_usuario_sin_permiso_no_puede_ver_gestion_de_proveedores(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get(route('panel.proveedores.index'))->assertForbidden();
        Livewire::actingAs($usuario)->test(Index::class)->assertForbidden();
    }

    public function test_administrador_puede_ver_proveedores(): void
    {
        $administrador = $this->crearAdministrador();
        Proveedor::factory()->create([
            'nombre_proveedor' => 'Productor Valle Verde',
            'created_at' => '2026-09-08 12:00:00',
        ]);

        $this->actingAs($administrador)
            ->get(route('panel.proveedores.index'))
            ->assertOk()
            ->assertSee('Productor Valle Verde')
            ->assertSee('Fecha de registro')
            ->assertSee('08/09/2026');
    }

    public function test_administrador_crea_proveedor_con_datos_normalizados_y_auditoria(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nombre_proveedor', '  Juan   Pérez  ')
            ->set('form.telefono_proveedor', ' 71234567 ')
            ->set('form.mercado_proveedor', '  Mercado   Abasto  ')
            ->set('form.direccion_proveedor', '  Puesto   15  ')
            ->set('form.observacion_proveedor', ' Entrega los martes. ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched(
                'toast',
                title: 'Proveedor registrado',
                message: 'El proveedor Juan Pérez fue registrado correctamente.',
                type: 'success',
            );

        $proveedor = Proveedor::query()->where('nombre_proveedor', 'Juan Pérez')->first();

        $this->assertNotNull($proveedor);
        $this->assertSame('71234567', $proveedor->telefono_proveedor);
        $this->assertSame('Mercado Abasto', $proveedor->mercado_proveedor);
        $this->assertSame('Puesto 15', $proveedor->direccion_proveedor);
        $this->assertTrue($proveedor->estado_proveedor);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::Providers->value,
            'event' => 'created',
            'subject_type' => Proveedor::class,
            'subject_id' => $proveedor->id,
            'causer_id' => $administrador->id,
        ]);
    }

    public function test_proveedor_requiere_nombre_y_rechaza_campos_invalidos(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.telefono_proveedor', 'teléfono inválido')
            ->set('form.mercado_proveedor', '<script>')
            ->call('save')
            ->assertHasErrors([
                'form.nombre_proveedor' => ['required'],
                'form.telefono_proveedor' => ['regex'],
                'form.mercado_proveedor' => ['not_regex'],
            ]);

        $this->assertSame(0, Proveedor::query()->count());
    }

    public function test_administrador_actualiza_proveedor(): void
    {
        $administrador = $this->crearAdministrador();
        $proveedor = Proveedor::factory()->create([
            'nombre_proveedor' => 'Proveedor Antiguo',
            'mercado_proveedor' => 'Mercado Viejo',
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openEditModal', $proveedor->id)
            ->set('form.nombre_proveedor', 'Proveedor Renovado')
            ->set('form.mercado_proveedor', 'Mercado Nuevo')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Proveedor actualizado', type: 'info');

        $proveedor->refresh();
        $this->assertSame('Proveedor Renovado', $proveedor->nombre_proveedor);
        $this->assertSame('Mercado Nuevo', $proveedor->mercado_proveedor);
    }

    public function test_administrador_desactiva_y_reactiva_proveedor_sin_eliminarlo(): void
    {
        $administrador = $this->crearAdministrador();
        $proveedor = Proveedor::factory()->create(['estado_proveedor' => true]);
        $componente = Livewire::actingAs($administrador)->test(Index::class);

        $componente
            ->call('openStatusModal', $proveedor->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Proveedor desactivado', type: 'warning');

        $this->assertFalse($proveedor->fresh()->estado_proveedor);
        $this->assertModelExists($proveedor);

        $componente
            ->call('openStatusModal', $proveedor->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Proveedor activado', type: 'success');

        $this->assertTrue($proveedor->fresh()->estado_proveedor);
        $this->assertSame(2, Activity::query()
            ->where('log_name', ActivityLogName::Providers->value)
            ->where('event', 'status_changed')
            ->where('subject_id', $proveedor->id)
            ->count());
    }

    public function test_busqueda_y_estado_filtran_proveedores(): void
    {
        $administrador = $this->crearAdministrador();
        Proveedor::factory()->create(['nombre_proveedor' => 'Proveedor Visible', 'mercado_proveedor' => 'Mercado Central', 'estado_proveedor' => false]);
        Proveedor::factory()->create(['nombre_proveedor' => 'Proveedor Oculto', 'mercado_proveedor' => 'Mercado Norte', 'estado_proveedor' => false]);
        Proveedor::factory()->create(['nombre_proveedor' => 'Proveedor Activo', 'mercado_proveedor' => 'Mercado Central', 'estado_proveedor' => true]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->set('search', 'Central')
            ->set('status', 'inactive')
            ->assertSee('Proveedor Visible')
            ->assertDontSee('Proveedor Oculto')
            ->assertDontSee('Proveedor Activo');
    }

    public function test_usuario_de_solo_lectura_no_ve_acciones_de_proveedores(): void
    {
        $this->crearAdministrador();
        $usuarioConsulta = User::factory()->create();
        $proveedor = Proveedor::factory()->create();
        $rol = Role::create(['name' => 'Consulta de proveedores', 'guard_name' => 'web']);
        $rol->givePermissionTo(PermissionName::ProvidersView->value);
        $usuarioConsulta->assignRole($rol);

        Livewire::actingAs($usuarioConsulta)
            ->test(Index::class)
            ->assertDontSee('wire:click="openCreateModal"', false)
            ->assertDontSee('wire:click="openEditModal('.$proveedor->id.')"', false)
            ->assertDontSee('wire:click="openStatusModal('.$proveedor->id.')"', false);
    }

    public function test_contenido_del_proveedor_se_muestra_escapado(): void
    {
        $administrador = $this->crearAdministrador();
        Proveedor::factory()->create([
            'nombre_proveedor' => '<script>alert("riesgo")</script>',
            'direccion_proveedor' => '<img src=x onerror=alert(1)>',
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
