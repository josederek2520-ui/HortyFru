<?php

namespace Tests\Feature;

use App\Enums\ActivityLogName;
use App\Enums\PermissionName;
use App\Livewire\Panel\UnidadesMedida\Index;
use App\Models\Activity;
use App\Models\UnidadMedida;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GestionUnidadesMedidaTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_usuario_no_autenticado_es_redirigido_desde_gestion_de_unidades(): void
    {
        $this->get(route('panel.unidades-medida.index'))->assertRedirect(route('login'));
    }

    public function test_usuario_sin_permiso_no_puede_ver_gestion_de_unidades(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get(route('panel.unidades-medida.index'))->assertForbidden();
        Livewire::actingAs($usuario)->test(Index::class)->assertForbidden();
    }

    public function test_administrador_puede_ver_unidades_de_medida(): void
    {
        $administrador = $this->crearAdministrador();
        UnidadMedida::factory()->create([
            'nombre_unidad_medida' => 'Kilogramo',
            'abreviatura_unidad_medida' => 'kg',
        ]);

        $this->actingAs($administrador)
            ->get(route('panel.unidades-medida.index'))
            ->assertOk()
            ->assertSee('Kilogramo')
            ->assertSee('kg');
    }

    public function test_administrador_crea_unidad_con_datos_normalizados_y_auditoria(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nombre_unidad_medida', '  Kilogramo   neto  ')
            ->set('form.abreviatura_unidad_medida', '  kg  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched(
                'toast',
                title: 'Unidad registrada',
                message: 'La unidad de medida Kilogramo neto fue registrada correctamente.',
                type: 'success',
            );

        $unidadMedida = UnidadMedida::query()->where('nombre_unidad_medida', 'Kilogramo neto')->first();

        $this->assertNotNull($unidadMedida);
        $this->assertSame('kg', $unidadMedida->abreviatura_unidad_medida);
        $this->assertTrue($unidadMedida->estado_unidad_medida);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::MeasurementUnits->value,
            'event' => 'created',
            'subject_type' => UnidadMedida::class,
            'subject_id' => $unidadMedida->id,
            'causer_id' => $administrador->id,
        ]);
    }

    public function test_unidad_requiere_nombre_y_abreviatura(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->call('save')
            ->assertHasErrors([
                'form.nombre_unidad_medida' => ['required'],
                'form.abreviatura_unidad_medida' => ['required'],
            ]);

        $this->assertSame(0, UnidadMedida::query()->count());
    }

    public function test_nombre_y_abreviatura_de_unidad_deben_ser_unicos(): void
    {
        $administrador = $this->crearAdministrador();
        UnidadMedida::factory()->create([
            'nombre_unidad_medida' => 'Litro',
            'abreviatura_unidad_medida' => 'L',
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nombre_unidad_medida', 'Litro')
            ->set('form.abreviatura_unidad_medida', 'L')
            ->call('save')
            ->assertHasErrors([
                'form.nombre_unidad_medida' => ['unique'],
                'form.abreviatura_unidad_medida' => ['unique'],
            ]);

        $this->assertSame(1, UnidadMedida::query()->count());
    }

    public function test_administrador_actualiza_unidad_sin_conflicto_con_sus_datos(): void
    {
        $administrador = $this->crearAdministrador();
        $unidadMedida = UnidadMedida::factory()->create([
            'nombre_unidad_medida' => 'Unidad',
            'abreviatura_unidad_medida' => 'und',
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openEditModal', $unidadMedida->id)
            ->set('form.nombre_unidad_medida', 'Pieza')
            ->set('form.abreviatura_unidad_medida', 'pza')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Unidad actualizada', type: 'info');

        $unidadMedida->refresh();
        $this->assertSame('Pieza', $unidadMedida->nombre_unidad_medida);
        $this->assertSame('pza', $unidadMedida->abreviatura_unidad_medida);
    }

    public function test_administrador_desactiva_y_reactiva_unidad_sin_eliminarla(): void
    {
        $administrador = $this->crearAdministrador();
        $unidadMedida = UnidadMedida::factory()->create(['estado_unidad_medida' => true]);
        $componente = Livewire::actingAs($administrador)->test(Index::class);

        $componente
            ->call('openStatusModal', $unidadMedida->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Unidad desactivada', type: 'warning');

        $this->assertFalse($unidadMedida->fresh()->estado_unidad_medida);
        $this->assertModelExists($unidadMedida);

        $componente
            ->call('openStatusModal', $unidadMedida->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Unidad activada', type: 'success');

        $this->assertTrue($unidadMedida->fresh()->estado_unidad_medida);
        $this->assertSame(2, Activity::query()
            ->where('log_name', ActivityLogName::MeasurementUnits->value)
            ->where('event', 'status_changed')
            ->where('subject_id', $unidadMedida->id)
            ->count());
    }

    public function test_busqueda_y_estado_filtran_unidades(): void
    {
        $administrador = $this->crearAdministrador();
        UnidadMedida::factory()->create(['nombre_unidad_medida' => 'Kilogramo', 'abreviatura_unidad_medida' => 'kg', 'estado_unidad_medida' => false]);
        UnidadMedida::factory()->create(['nombre_unidad_medida' => 'Gramo', 'abreviatura_unidad_medida' => 'g', 'estado_unidad_medida' => true]);
        UnidadMedida::factory()->create(['nombre_unidad_medida' => 'Litro', 'abreviatura_unidad_medida' => 'L', 'estado_unidad_medida' => false]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->set('search', 'kg')
            ->set('status', 'inactive')
            ->assertSee('Kilogramo')
            ->assertDontSee('Gramo')
            ->assertDontSee('Litro');
    }

    public function test_usuario_de_solo_lectura_no_ve_acciones_de_unidades(): void
    {
        $this->crearAdministrador();
        $usuarioConsulta = User::factory()->create();
        $unidadMedida = UnidadMedida::factory()->create();
        $rol = Role::create(['name' => 'Consulta de unidades', 'guard_name' => 'web']);
        $rol->givePermissionTo(PermissionName::MeasurementUnitsView->value);
        $usuarioConsulta->assignRole($rol);

        Livewire::actingAs($usuarioConsulta)
            ->test(Index::class)
            ->assertDontSee('wire:click="openCreateModal"', false)
            ->assertDontSee('wire:click="openEditModal('.$unidadMedida->id.')"', false)
            ->assertDontSee('wire:click="openStatusModal('.$unidadMedida->id.')"', false);
    }

    public function test_contenido_de_unidad_se_muestra_escapado(): void
    {
        $administrador = $this->crearAdministrador();
        UnidadMedida::factory()->create([
            'nombre_unidad_medida' => '<script>alert("riesgo")</script>',
            'abreviatura_unidad_medida' => '<b>x</b>',
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->assertSee('&lt;script&gt;', false)
            ->assertSee('&lt;b&gt;', false)
            ->assertDontSee('<script>alert("riesgo")</script>', false)
            ->assertDontSee('<b>x</b>', false);
    }

    private function crearAdministrador(): User
    {
        $administrador = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrador->refresh();
    }
}
