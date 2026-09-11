<?php

namespace Tests\Feature;

use App\Actions\Compras\CrearCompraAction;
use App\Enums\ActivityLogName;
use App\Enums\EstadoCompra;
use App\Enums\EstadoRecepcion;
use App\Enums\PermissionName;
use App\Livewire\Panel\Compras\Index;
use App\Models\Activity;
use App\Models\Compra;
use App\Models\Empleado;
use App\Models\Proveedor;
use App\Models\Recepcion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GestionComprasTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_usuario_no_autenticado_es_redirigido_desde_compras(): void
    {
        $this->get(route('panel.compras.index'))->assertRedirect(route('login'));
    }

    public function test_usuario_sin_permiso_no_puede_ver_compras(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get(route('panel.compras.index'))->assertForbidden();
        Livewire::actingAs($usuario)->test(Index::class)->assertForbidden();
    }

    public function test_administrador_crea_compra_en_borrador_con_codigo_total_y_auditoria(): void
    {
        $this->travelTo('2026-09-08 15:00:00');
        $administrador = $this->crearAdministrador();
        $proveedor = Proveedor::factory()->create(['nombre_proveedor' => 'Proveedor Central']);
        $empleado = $administrador->empleado;
        $empleado->update([
            'nombre_empleado' => 'Juana',
            'apellido_empleado' => 'Pérez',
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.proveedor_id', $proveedor->id)
            ->set('form.fecha_compra', '2026-09-08T09:30')
            ->set('form.observaciones_compra', '  Compra   del mercado central  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched('toast', title: 'Compra creada', type: 'success');

        $compra = Compra::query()->first();

        $this->assertNotNull($compra);
        $this->assertSame('COM-000001', $compra->codigo_compra);
        $this->assertSame($proveedor->id, $compra->proveedor_id);
        $this->assertSame($empleado->id, $compra->empleado_id);
        $this->assertSame($administrador->id, $compra->registrado_por);
        $this->assertSame(EstadoCompra::Borrador, $compra->estado_compra);
        $this->assertSame('0.00', $compra->total_compra);
        $this->assertSame('Compra del mercado central', $compra->observaciones_compra);
        $this->assertSame('2026-09-08 13:30:00', $compra->fecha_compra->toDateTimeString());
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::Purchases->value,
            'event' => 'created',
            'subject_type' => Compra::class,
            'subject_id' => $compra->id,
            'causer_id' => $administrador->id,
        ]);
    }

    public function test_compra_requiere_responsables_activos_y_fecha_no_futura(): void
    {
        $this->travelTo('2026-09-08 15:00:00');
        $administrador = $this->crearAdministrador();
        $proveedor = Proveedor::factory()->create(['estado_proveedor' => false]);
        $administrador->empleado->update(['activo_empleado' => false]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.proveedor_id', $proveedor->id)
            ->set('form.fecha_compra', '2026-09-08T12:00')
            ->call('save')
            ->assertHasErrors([
                'form.proveedor_id',
                'form.empleado_id',
                'form.fecha_compra',
            ]);

        $this->assertSame(0, Compra::query()->count());
    }

    public function test_observaciones_de_compra_rechazan_contenido_html(): void
    {
        $administrador = $this->crearAdministrador();
        $proveedor = Proveedor::factory()->create();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.proveedor_id', $proveedor->id)
            ->set('form.observaciones_compra', '<script>alert(1)</script>')
            ->call('save')
            ->assertHasErrors(['form.observaciones_compra' => ['not_regex']]);

        $this->assertSame(0, Compra::query()->count());
    }

    public function test_administrador_actualiza_una_compra_en_borrador_sin_alterar_codigo_estado_o_total(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->for($administrador, 'registradoPor')->create();
        $empleadoOriginal = $compra->empleado;
        $nuevoProveedor = Proveedor::factory()->create(['nombre_proveedor' => 'Nuevo proveedor']);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openEditModal', $compra->id)
            ->set('form.proveedor_id', $nuevoProveedor->id)
            ->set('form.observaciones_compra', 'Compra actualizada')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Compra actualizada', type: 'info');

        $compra->refresh();
        $this->assertSame($nuevoProveedor->id, $compra->proveedor_id);
        $this->assertSame($empleadoOriginal->id, $compra->empleado_id);
        $this->assertSame(EstadoCompra::Borrador, $compra->estado_compra);
        $this->assertSame('0.00', $compra->total_compra);
        $this->assertStringStartsWith('COM-TEST-', $compra->codigo_compra);
    }

    public function test_compra_registrada_no_puede_editarse(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->registrada()->create();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openEditModal', $compra->id)
            ->assertForbidden();
    }

    public function test_cuenta_sin_empleado_activo_no_puede_registrar_compras(): void
    {
        $usuario = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);
        $proveedor = Proveedor::factory()->create();

        Livewire::actingAs($usuario)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.proveedor_id', $proveedor->id)
            ->call('save')
            ->assertHasErrors(['form.empleado_id']);

        $this->assertSame(0, Compra::query()->count());
    }

    public function test_formulario_muestra_comprador_sin_selector_y_selector_flatpickr(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->assertSee($administrador->empleado->nombre_completo)
            ->assertDontSee('Asignado automáticamente desde tu cuenta')
            ->assertSee('purchase-date-sync', false)
            ->assertDontSee('wire:model="form.empleado_id"', false)
            ->assertDontSee('type="datetime-local"', false);
    }

    public function test_backend_ignora_un_empleado_ajeno_enviado_al_crear_compra(): void
    {
        $administrador = $this->crearAdministrador();
        $proveedor = Proveedor::factory()->create();
        $empleadoAjeno = Empleado::factory()->create();

        $compra = app(CrearCompraAction::class)([
            'proveedor_id' => $proveedor->id,
            'empleado_id' => $empleadoAjeno->id,
            'fecha_compra' => now((string) config('app.display_timezone', 'America/La_Paz'))->format('Y-m-d\TH:i'),
            'observaciones_compra' => null,
        ], $administrador);

        $this->assertSame($administrador->empleado->id, $compra->empleado_id);
        $this->assertNotSame($empleadoAjeno->id, $compra->empleado_id);
    }

    public function test_modal_permanece_abierto_al_cambiar_fecha_y_hora(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.fecha_compra', '2026-09-09T16:55')
            ->assertSet('showFormModal', true);
    }

    public function test_administrador_cancela_compra_y_conserva_motivo_y_auditoria(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->for($administrador, 'registradoPor')->create();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCancelModal', $compra->id)
            ->set('cancellationReason', '  El proveedor no tenía   mercadería  ')
            ->call('cancelPurchase')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Compra cancelada', type: 'warning');

        $compra->refresh();
        $this->assertSame(EstadoCompra::Cancelada, $compra->estado_compra);
        $this->assertSame('El proveedor no tenía mercadería', $compra->motivo_cancelacion_compra);
        $this->assertSame($administrador->id, $compra->cancelado_por);
        $this->assertNotNull($compra->cancelado_en);
        $this->assertSame(1, Activity::query()
            ->where('log_name', ActivityLogName::Purchases->value)
            ->where('event', 'status_changed')
            ->where('subject_id', $compra->id)
            ->count());
    }

    public function test_compra_con_recepcion_activa_no_puede_cancelarse(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->registrada()->create();
        Recepcion::factory()->for($compra, 'compra')->create([
            'estado_recepcion' => EstadoRecepcion::Borrador,
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCancelModal', $compra->id)
            ->set('cancellationReason', 'La compra fue anulada')
            ->call('cancelPurchase')
            ->assertHasErrors(['cancelacion']);

        $this->assertSame(EstadoCompra::Registrada, $compra->fresh()->estado_compra);
    }

    public function test_busqueda_estado_y_fecha_filtran_compras(): void
    {
        $administrador = $this->crearAdministrador();
        $proveedorVisible = Proveedor::factory()->create(['nombre_proveedor' => 'Mercado Abasto']);
        $proveedorOculto = Proveedor::factory()->create(['nombre_proveedor' => 'Mercado Norte']);
        Compra::factory()->for($proveedorVisible, 'proveedor')->create([
            'codigo_compra' => 'COM-VISIBLE',
            'estado_compra' => EstadoCompra::Borrador,
            'fecha_compra' => CarbonImmutable::parse('2026-09-08 14:00:00', 'UTC'),
        ]);
        Compra::factory()->for($proveedorOculto, 'proveedor')->registrada()->create([
            'codigo_compra' => 'COM-OCULTA',
            'fecha_compra' => CarbonImmutable::parse('2026-09-09 14:00:00', 'UTC'),
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->set('search', 'Abasto')
            ->set('status', EstadoCompra::Borrador->value)
            ->set('purchaseDate', '2026-09-08')
            ->assertSee('COM-VISIBLE')
            ->assertDontSee('COM-OCULTA');
    }

    public function test_usuario_de_solo_lectura_no_ve_acciones_de_compras(): void
    {
        $this->crearAdministrador();
        $usuarioConsulta = User::factory()->create();
        $compra = Compra::factory()->create();
        $rol = Role::create(['name' => 'Consulta de compras', 'guard_name' => 'web']);
        $rol->givePermissionTo(PermissionName::PurchasesView->value);
        $usuarioConsulta->assignRole($rol);

        Livewire::actingAs($usuarioConsulta)
            ->test(Index::class)
            ->assertDontSee('wire:click="openCreateModal"', false)
            ->assertDontSee('wire:click="openEditModal('.$compra->id.')"', false)
            ->assertDontSee('wire:click="openCancelModal('.$compra->id.')"', false);
    }

    private function crearAdministrador(): User
    {
        $administrador = User::factory()->create();
        Empleado::factory()->for($administrador, 'user')->create();
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrador->refresh();
    }
}
