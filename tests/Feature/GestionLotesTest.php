<?php

namespace Tests\Feature;

use App\Actions\Lotes\CrearLoteAction;
use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoLote;
use App\Enums\PermissionName;
use App\Enums\TipoOrigenLote;
use App\Livewire\Panel\Lotes\Index;
use App\Models\Articulo;
use App\Models\Lote;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GestionLotesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_usuario_no_autenticado_es_redirigido_desde_lotes(): void
    {
        $this->get(route('panel.lotes.index'))->assertRedirect(route('login'));
    }

    public function test_usuario_sin_permiso_no_puede_ver_lotes(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get(route('panel.lotes.index'))->assertForbidden();
        Livewire::actingAs($usuario)->test(Index::class)->assertForbidden();
    }

    public function test_modulo_indica_la_generacion_automatica_y_no_ofrece_alta_manual(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->assertSee('Generación automática desde recepción')
            ->assertDontSee('¿Cuándo se crea un lote?')
            ->assertDontSee('¿Dónde estará el saldo?')
            ->assertDontSee('Nuevo lote');
    }

    public function test_accion_de_recepcion_genera_codigos_unicos_para_dos_lotes_de_papaya(): void
    {
        $administrador = $this->crearAdministrador();
        $papaya = Articulo::factory()->create(['nombre_articulo' => 'Papaya']);
        $crearLote = app(CrearLoteAction::class);

        $primerLote = $crearLote(
            $papaya,
            TipoOrigenLote::Recepcion,
            CarbonImmutable::parse('2026-09-10 08:15', 'America/La_Paz'),
            CarbonImmutable::parse('2026-09-13', 'America/La_Paz'),
            '  Buena  ',
            '  Tres   cajas recibidas. ',
            $administrador,
        );
        $segundoLote = $crearLote(
            $papaya,
            TipoOrigenLote::Recepcion,
            CarbonImmutable::parse('2026-09-11 07:30', 'America/La_Paz'),
            CarbonImmutable::parse('2026-09-14', 'America/La_Paz'),
            null,
            null,
            $administrador,
        );

        $this->assertSame('LOT-10092026-001', $primerLote->codigo_lote);
        $this->assertSame('LOT-11092026-002', $segundoLote->codigo_lote);
        $this->assertNotSame($primerLote->codigo_lote, $segundoLote->codigo_lote);
        $this->assertSame($papaya->id, $primerLote->articulo_id);
        $this->assertSame(TipoOrigenLote::Recepcion, $primerLote->tipo_origen_lote);
        $this->assertSame(EstadoLote::Disponible, $primerLote->estado_lote);
        $this->assertSame('Buena', $primerLote->calidad_lote);
        $this->assertSame('Tres cajas recibidas.', $primerLote->observaciones_lote);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::Lots->value,
            'event' => ActivityEvent::Created->value,
            'subject_type' => Lote::class,
            'subject_id' => $primerLote->id,
            'causer_id' => $administrador->id,
        ]);
    }

    public function test_fecha_de_vencimiento_no_puede_ser_anterior_al_ingreso(): void
    {
        $administrador = $this->crearAdministrador();
        $lote = Lote::factory()->create([
            'fecha_ingreso_lote' => '2026-09-10 12:00:00',
            'fecha_vencimiento_lote' => null,
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openEditModal', $lote->id)
            ->set('form.fecha_vencimiento_lote', '2026-09-09')
            ->call('save')
            ->assertHasErrors(['form.fecha_vencimiento_lote']);

        $this->assertNull($lote->refresh()->fecha_vencimiento_lote);
    }

    public function test_administrador_actualiza_solo_informacion_descriptiva_del_lote(): void
    {
        $administrador = $this->crearAdministrador();
        $lote = Lote::factory()->create([
            'codigo_lote' => 'LOT-ORIGINAL',
            'fecha_ingreso_lote' => '2026-09-10 12:00:00',
            'fecha_vencimiento_lote' => null,
        ]);
        $articuloOriginal = $lote->articulo_id;

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openEditModal', $lote->id)
            ->set('form.fecha_vencimiento_lote', '2026-09-15')
            ->set('form.calidad_lote', '  Buena   y madura ')
            ->set('form.observaciones_lote', ' Separar para el pedido de mañana. ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false)
            ->assertDispatched('toast', title: 'Lote actualizado', type: 'info');

        $lote->refresh();
        $this->assertSame('LOT-ORIGINAL', $lote->codigo_lote);
        $this->assertSame($articuloOriginal, $lote->articulo_id);
        $this->assertSame('2026-09-15', $lote->fecha_vencimiento_lote->format('Y-m-d'));
        $this->assertSame('Buena y madura', $lote->calidad_lote);
        $this->assertSame('Separar para el pedido de mañana.', $lote->observaciones_lote);
    }

    public function test_bloquear_lote_exige_motivo_y_registra_auditoria(): void
    {
        $administrador = $this->crearAdministrador();
        $lote = Lote::factory()->create([
            'estado_lote' => EstadoLote::Disponible,
            'motivo_bloqueo_lote' => null,
        ]);

        $componente = Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openStatusModal', $lote->id)
            ->call('changeStatus')
            ->assertHasErrors(['motivoBloqueo']);

        $this->assertSame(EstadoLote::Disponible, $lote->refresh()->estado_lote);

        $componente
            ->set('motivoBloqueo', '  Zanahorias   separadas por revisión. ')
            ->call('changeStatus')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Lote bloqueado', type: 'warning');

        $this->assertSame(EstadoLote::Bloqueado, $lote->refresh()->estado_lote);
        $this->assertSame('Zanahorias separadas por revisión.', $lote->motivo_bloqueo_lote);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::Lots->value,
            'event' => ActivityEvent::StatusChanged->value,
            'subject_type' => Lote::class,
            'subject_id' => $lote->id,
            'causer_id' => $administrador->id,
        ]);
    }

    public function test_lote_vencido_bloqueado_no_puede_habilitarse(): void
    {
        $this->travelTo('2026-09-15 12:00:00');
        $administrador = $this->crearAdministrador();
        $lote = Lote::factory()->bloqueado()->create([
            'fecha_vencimiento_lote' => '2026-09-14',
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openStatusModal', $lote->id)
            ->call('changeStatus')
            ->assertHasErrors(['estado']);

        $this->assertSame(EstadoLote::Bloqueado, $lote->refresh()->estado_lote);
    }

    public function test_listado_aplica_prioridad_fefo_y_deja_sin_fecha_al_final(): void
    {
        $administrador = $this->crearAdministrador();
        Lote::factory()->create(['codigo_lote' => 'LOT-SIN-FECHA', 'fecha_vencimiento_lote' => null]);
        Lote::factory()->create(['codigo_lote' => 'LOT-VENCE-DESPUES', 'fecha_vencimiento_lote' => '2026-09-15']);
        Lote::factory()->create(['codigo_lote' => 'LOT-VENCE-PRIMERO', 'fecha_vencimiento_lote' => '2026-09-12']);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->assertSeeInOrder([
                'LOT-VENCE-PRIMERO',
                'LOT-VENCE-DESPUES',
                'LOT-SIN-FECHA',
            ]);
    }

    public function test_solo_lote_disponible_y_no_vencido_es_elegible_para_fefo(): void
    {
        $this->travelTo('2026-09-15 12:00:00');
        $disponible = Lote::factory()->create([
            'estado_lote' => EstadoLote::Disponible,
            'fecha_vencimiento_lote' => '2026-09-16',
        ]);
        $vencido = Lote::factory()->create([
            'estado_lote' => EstadoLote::Disponible,
            'fecha_vencimiento_lote' => '2026-09-14',
        ]);
        $bloqueado = Lote::factory()->bloqueado()->create([
            'fecha_vencimiento_lote' => '2026-09-16',
        ]);

        $this->assertTrue($disponible->esElegibleParaFefo());
        $this->assertFalse($vencido->esElegibleParaFefo());
        $this->assertFalse($bloqueado->esElegibleParaFefo());
    }

    public function test_busqueda_y_estado_filtran_lotes(): void
    {
        $administrador = $this->crearAdministrador();
        $papaya = Articulo::factory()->create(['nombre_articulo' => 'Papaya']);
        $zanahoria = Articulo::factory()->create(['nombre_articulo' => 'Zanahoria']);
        Lote::factory()->for($papaya)->create(['codigo_lote' => 'LOT-PAPAYA', 'estado_lote' => EstadoLote::Bloqueado]);
        Lote::factory()->for($zanahoria)->create(['codigo_lote' => 'LOT-ZANAHORIA', 'estado_lote' => EstadoLote::Bloqueado]);
        Lote::factory()->for($papaya)->create(['codigo_lote' => 'LOT-PAPAYA-DISPONIBLE', 'estado_lote' => EstadoLote::Disponible]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->set('search', 'Papaya')
            ->set('status', EstadoLote::Bloqueado->value)
            ->assertSee('LOT-PAPAYA')
            ->assertDontSee('LOT-ZANAHORIA')
            ->assertDontSee('LOT-PAPAYA-DISPONIBLE');
    }

    public function test_usuario_de_solo_lectura_no_puede_editar_ni_bloquear_lotes(): void
    {
        $this->crearAdministrador();
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'Consulta de lotes', 'guard_name' => 'web']);
        $rol->givePermissionTo(PermissionName::LotsView->value);
        $usuario->assignRole($rol);
        $lote = Lote::factory()->create();

        Livewire::actingAs($usuario)
            ->test(Index::class)
            ->assertDontSee('wire:click="openEditModal('.$lote->id.')"', false)
            ->assertDontSee('wire:click="openStatusModal('.$lote->id.')"', false)
            ->call('openEditModal', $lote->id)
            ->assertForbidden();
    }

    private function crearAdministrador(): User
    {
        $administrador = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrador->refresh();
    }
}
