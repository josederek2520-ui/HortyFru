<?php

namespace Tests\Feature;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoCompra;
use App\Enums\EstadoRecepcion;
use App\Livewire\Panel\Recepciones\Index;
use App\Models\Almacen;
use App\Models\Compra;
use App\Models\Empleado;
use App\Models\Recepcion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GestionRecepcionesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_usuario_no_autenticado_es_redirigido_desde_recepciones(): void
    {
        $this->get(route('panel.recepciones.index'))->assertRedirect(route('login'));
    }

    public function test_usuario_sin_permiso_no_puede_ver_recepciones(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get(route('panel.recepciones.index'))->assertForbidden();
        Livewire::actingAs($usuario)->test(Index::class)->assertForbidden();
    }

    public function test_cabecera_aclara_que_productos_lotes_y_stock_vienen_despues(): void
    {
        [$administrador] = $this->crearAdministradorConEmpleado();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->assertSee('Recepciones')
            ->assertSee('Los productos se agregarán después en el detalle de recepción')
            ->assertDontSee('Confirmar recepción');
    }

    public function test_crea_recepcion_en_borrador_con_empleado_de_la_sesion(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-10 12:00:00', 'America/La_Paz'));
        [$administrador, $empleado] = $this->crearAdministradorConEmpleado();
        $almacen = Almacen::factory()->create(['nombre_almacen' => 'Almacén central']);
        $compra = Compra::factory()->registrada()->create([
            'fecha_compra' => '2026-09-10 15:00:00',
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.compra_id', $compra->id)
            ->set('form.almacen_id', $almacen->id)
            ->set('form.fecha_recepcion', '2026-09-10T11:30')
            ->set('form.observaciones_recepcion', '  Mercadería   descargada. ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched('toast', title: 'Recepción creada', type: 'success');

        $recepcion = Recepcion::query()->sole();
        $this->assertSame('REC-000001', $recepcion->codigo_recepcion);
        $this->assertSame($compra->id, $recepcion->compra_id);
        $this->assertSame($almacen->id, $recepcion->almacen_id);
        $this->assertSame($empleado->id, $recepcion->empleado_id);
        $this->assertSame($administrador->id, $recepcion->registrado_por);
        $this->assertSame(EstadoRecepcion::Borrador, $recepcion->estado_recepcion);
        $this->assertSame('Mercadería descargada.', $recepcion->observaciones_recepcion);
        $this->assertNull($recepcion->confirmado_por);
        $this->assertNull($recepcion->confirmado_en);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::Receptions->value,
            'event' => ActivityEvent::Created->value,
            'subject_type' => Recepcion::class,
            'subject_id' => $recepcion->id,
            'causer_id' => $administrador->id,
        ]);
    }

    public function test_permite_recepcion_excepcional_sin_compra_asociada(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-10 12:00:00', 'America/La_Paz'));
        [$administrador] = $this->crearAdministradorConEmpleado();
        $almacen = Almacen::factory()->create();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.compra_id', null)
            ->set('form.almacen_id', $almacen->id)
            ->set('form.fecha_recepcion', '2026-09-10T10:00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(Recepcion::query()->sole()->compra_id);
    }

    public function test_no_permite_recibir_una_compra_que_sigue_en_borrador(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-10 12:00:00', 'America/La_Paz'));
        [$administrador] = $this->crearAdministradorConEmpleado();
        $almacen = Almacen::factory()->create();
        $compra = Compra::factory()->create([
            'estado_compra' => EstadoCompra::Borrador,
            'fecha_compra' => '2026-09-10 13:00:00',
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.compra_id', $compra->id)
            ->set('form.almacen_id', $almacen->id)
            ->set('form.fecha_recepcion', '2026-09-10T10:00')
            ->call('save')
            ->assertHasErrors(['form.compra_id']);

        $this->assertDatabaseCount('recepciones', 0);
    }

    public function test_no_permite_fecha_anterior_a_la_compra_ni_fecha_futura(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-10 12:00:00', 'America/La_Paz'));
        [$administrador] = $this->crearAdministradorConEmpleado();
        $almacen = Almacen::factory()->create();
        $compra = Compra::factory()->registrada()->create([
            'fecha_compra' => '2026-09-10 14:00:00',
        ]);

        $componente = Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.compra_id', $compra->id)
            ->set('form.almacen_id', $almacen->id)
            ->set('form.fecha_recepcion', '2026-09-10T09:59')
            ->call('save')
            ->assertHasErrors(['form.fecha_recepcion']);

        $componente
            ->set('form.fecha_recepcion', '2026-09-10T12:01')
            ->call('save')
            ->assertHasErrors(['form.fecha_recepcion']);

        $this->assertDatabaseCount('recepciones', 0);
    }

    public function test_edicion_conserva_codigo_empleado_y_usuario_original(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-10 12:00:00', 'America/La_Paz'));
        [$administrador, $empleado] = $this->crearAdministradorConEmpleado();
        $almacenOriginal = Almacen::factory()->create();
        $almacenNuevo = Almacen::factory()->create();
        $recepcion = Recepcion::factory()->create([
            'codigo_recepcion' => 'REC-ORIGINAL',
            'almacen_id' => $almacenOriginal->id,
            'empleado_id' => $empleado->id,
            'registrado_por' => $administrador->id,
            'fecha_recepcion' => '2026-09-10 10:00:00',
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openEditModal', $recepcion->id)
            ->set('form.almacen_id', $almacenNuevo->id)
            ->set('form.fecha_recepcion', '2026-09-10T11:00')
            ->set('form.observaciones_recepcion', 'Segundo sector del almacén.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Recepción actualizada', type: 'info');

        $recepcion->refresh();
        $this->assertSame('REC-ORIGINAL', $recepcion->codigo_recepcion);
        $this->assertSame($almacenNuevo->id, $recepcion->almacen_id);
        $this->assertSame($empleado->id, $recepcion->empleado_id);
        $this->assertSame($administrador->id, $recepcion->registrado_por);
    }

    public function test_cancelar_exige_motivo_y_registra_auditoria(): void
    {
        [$administrador, $empleado] = $this->crearAdministradorConEmpleado();
        $recepcion = Recepcion::factory()->create([
            'empleado_id' => $empleado->id,
            'registrado_por' => $administrador->id,
        ]);

        $componente = Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCancelModal', $recepcion->id)
            ->call('cancelReception')
            ->assertHasErrors(['cancellationReason']);

        $componente
            ->set('cancellationReason', '  Llegada   registrada por error. ')
            ->call('cancelReception')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Recepción cancelada', type: 'warning');

        $recepcion->refresh();
        $this->assertSame(EstadoRecepcion::Cancelada, $recepcion->estado_recepcion);
        $this->assertSame('Llegada registrada por error.', $recepcion->motivo_cancelacion_recepcion);
        $this->assertSame($administrador->id, $recepcion->cancelado_por);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::Receptions->value,
            'event' => ActivityEvent::StatusChanged->value,
            'subject_type' => Recepcion::class,
            'subject_id' => $recepcion->id,
        ]);
    }

    public function test_recepcion_confirmada_no_se_puede_editar_ni_cancelar_desde_la_cabecera(): void
    {
        [$administrador, $empleado] = $this->crearAdministradorConEmpleado();
        $recepcion = Recepcion::factory()->confirmada()->create([
            'empleado_id' => $empleado->id,
            'registrado_por' => $administrador->id,
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->assertDontSee('wire:click="openEditModal('.$recepcion->id.')"', false)
            ->assertDontSee('wire:click="openCancelModal('.$recepcion->id.')"', false)
            ->call('openEditModal', $recepcion->id)
            ->assertForbidden();
    }

    public function test_busqueda_estado_y_fecha_filtran_recepciones(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-10 12:00:00', 'America/La_Paz'));
        [$administrador, $empleado] = $this->crearAdministradorConEmpleado();
        $central = Almacen::factory()->create(['nombre_almacen' => 'Almacén Central']);
        $norte = Almacen::factory()->create(['nombre_almacen' => 'Depósito Norte']);
        Recepcion::factory()->create([
            'codigo_recepcion' => 'REC-CENTRAL',
            'almacen_id' => $central->id,
            'empleado_id' => $empleado->id,
            'registrado_por' => $administrador->id,
            'fecha_recepcion' => '2026-09-10 14:00:00',
            'estado_recepcion' => EstadoRecepcion::Borrador,
        ]);
        Recepcion::factory()->cancelada()->create([
            'codigo_recepcion' => 'REC-NORTE',
            'almacen_id' => $norte->id,
            'empleado_id' => $empleado->id,
            'registrado_por' => $administrador->id,
            'fecha_recepcion' => '2026-09-09 14:00:00',
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->set('search', 'Central')
            ->set('status', EstadoRecepcion::Borrador->value)
            ->set('receptionDate', '2026-09-10')
            ->assertSee('REC-CENTRAL')
            ->assertDontSee('REC-NORTE');
    }

    /** @return array{User, Empleado} */
    private function crearAdministradorConEmpleado(): array
    {
        $administrador = User::factory()->create();
        $empleado = Empleado::factory()->create([
            'user_id' => $administrador->id,
            'activo_empleado' => true,
        ]);
        $this->seed(RoleAndPermissionSeeder::class);

        return [$administrador->refresh(), $empleado];
    }
}
