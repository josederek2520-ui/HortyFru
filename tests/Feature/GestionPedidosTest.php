<?php

namespace Tests\Feature;

use App\Enums\ActivityLogName;
use App\Enums\EstadoPedido;
use App\Enums\PermissionName;
use App\Enums\TipoEquivalenciaPresentacionArticulo;
use App\Enums\UsoPresentacionArticulo;
use App\Livewire\Panel\Pedidos\Create as CreatePedido;
use App\Livewire\Panel\Pedidos\Edit as EditPedido;
use App\Livewire\Panel\Pedidos\Index;
use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\PresentacionArticulo;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GestionPedidosTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_usuario_no_autenticado_es_redirigido_desde_pedidos(): void
    {
        $this->get(route('panel.pedidos.index'))->assertRedirect(route('login'));
        $this->get(route('panel.pedidos.create'))->assertRedirect(route('login'));
    }

    public function test_usuario_sin_permiso_no_puede_ver_pedidos(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $usuario = User::factory()->create();
        $pedido = Pedido::factory()->create();

        $this->actingAs($usuario)->get(route('panel.pedidos.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('panel.pedidos.create'))->assertForbidden();
        $this->actingAs($usuario)->get(route('panel.pedidos.edit', $pedido))->assertForbidden();
        Livewire::actingAs($usuario)->test(Index::class)->assertForbidden();
        Livewire::actingAs($usuario)->test(CreatePedido::class)->assertForbidden();
        Livewire::actingAs($usuario)->test(EditPedido::class, ['pedidoId' => $pedido->id])->assertForbidden();
    }

    public function test_administrador_registra_pedido_con_codigo_detalle_equivalencia_y_auditoria(): void
    {
        $administrador = $this->crearAdministrador();
        $sucursal = Sucursal::factory()->create();
        $articulo = Articulo::factory()->create(['nombre_articulo' => 'Tomate']);
        $presentacion = PresentacionArticulo::factory()->for($articulo, 'articulo')->create([
            'nombre_presentacion_articulo' => 'Caja',
            'uso_presentacion_articulo' => UsoPresentacionArticulo::Pedido,
            'tipo_equivalencia_presentacion_articulo' => TipoEquivalenciaPresentacionArticulo::Fija,
            'equivalencia_base_presentacion_articulo' => '20.500',
            'permite_fraccion_presentacion_articulo' => true,
            'predeterminada_pedido_presentacion_articulo' => true,
        ]);

        Livewire::actingAs($administrador)
            ->test(CreatePedido::class)
            ->set('form.sucursal_id', $sucursal->id)
            ->set('form.fecha_requerida_pedido', today()->addDay()->toDateString())
            ->set('form.detalles.0.articulo_id', $articulo->id)
            ->set('form.detalles.0.presentacion_articulo_id', $presentacion->id)
            ->set('form.detalles.0.cantidad_solicitada_detalle_pedido', '2.500')
            ->set('form.detalles.0.observaciones_detalle_pedido', '  Maduro   firme ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('panel.pedidos.index'))
            ->assertSessionHas('toast.title', 'Pedido registrado');

        $pedido = Pedido::query()->with('detalles')->sole();
        $detalle = $pedido->detalles->sole();

        $this->assertSame('PED-'.str_pad((string) $pedido->id, 6, '0', STR_PAD_LEFT), $pedido->codigo_pedido);
        $this->assertSame($sucursal->id, $pedido->sucursal_id);
        $this->assertSame(EstadoPedido::Pendiente, $pedido->estado_pedido);
        $this->assertSame($administrador->id, $pedido->registrado_por);
        $this->assertSame('2.500', $detalle->cantidad_solicitada_detalle_pedido);
        $this->assertSame('20.500', $detalle->equivalencia_base_aplicada_detalle_pedido);
        $this->assertSame('Maduro firme', $detalle->observaciones_detalle_pedido);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::Orders->value,
            'event' => 'created',
            'subject_type' => Pedido::class,
            'subject_id' => $pedido->id,
            'causer_id' => $administrador->id,
        ]);
    }

    public function test_presentacion_predeterminada_de_pedido_se_selecciona_al_elegir_articulo(): void
    {
        $administrador = $this->crearAdministrador();
        $articulo = Articulo::factory()->create();
        PresentacionArticulo::factory()->for($articulo, 'articulo')->create(['nombre_presentacion_articulo' => 'Unidad']);
        $predeterminada = PresentacionArticulo::factory()->for($articulo, 'articulo')->create([
            'nombre_presentacion_articulo' => 'Caja',
            'predeterminada_pedido_presentacion_articulo' => true,
        ]);

        Livewire::actingAs($administrador)
            ->test(CreatePedido::class)
            ->call('selectArticle', 0, (string) $articulo->id)
            ->assertSet('form.detalles.0.presentacion_articulo_id', $predeterminada->id);
    }

    public function test_pedido_rechaza_presentacion_de_otro_articulo_duplicados_y_fracciones_no_permitidas(): void
    {
        $administrador = $this->crearAdministrador();
        $sucursal = Sucursal::factory()->create();
        $tomate = Articulo::factory()->create();
        $papa = Articulo::factory()->create();
        $cajaPapa = PresentacionArticulo::factory()->for($papa, 'articulo')->create([
            'permite_fraccion_presentacion_articulo' => false,
        ]);

        $componente = Livewire::actingAs($administrador)
            ->test(CreatePedido::class)
            ->set('form.sucursal_id', $sucursal->id)
            ->set('form.detalles.0.articulo_id', $tomate->id)
            ->set('form.detalles.0.presentacion_articulo_id', $cajaPapa->id)
            ->set('form.detalles.0.cantidad_solicitada_detalle_pedido', '1.500')
            ->call('save')
            ->assertHasErrors(['form.detalles.0.presentacion_articulo_id']);

        $componente
            ->set('form.detalles.0.articulo_id', $papa->id)
            ->call('save')
            ->assertHasErrors(['form.detalles.0.cantidad_solicitada_detalle_pedido'])
            ->set('form.detalles.0.cantidad_solicitada_detalle_pedido', '1')
            ->call('addDetail')
            ->set('form.detalles.1.articulo_id', $papa->id)
            ->set('form.detalles.1.presentacion_articulo_id', $cajaPapa->id)
            ->set('form.detalles.1.cantidad_solicitada_detalle_pedido', '2')
            ->call('save')
            ->assertHasErrors(['form.detalles.1.presentacion_articulo_id']);

        $this->assertSame(0, Pedido::query()->count());
    }

    public function test_sucursal_inactiva_o_con_cliente_inactivo_no_puede_generar_nuevo_pedido(): void
    {
        $administrador = $this->crearAdministrador();
        $cliente = Cliente::factory()->create(['activo_cliente' => false]);
        $sucursal = Sucursal::factory()->for($cliente, 'cliente')->create(['activo_sucursal' => true]);
        $presentacion = PresentacionArticulo::factory()->create();

        Livewire::actingAs($administrador)
            ->test(CreatePedido::class)
            ->set('form.sucursal_id', $sucursal->id)
            ->set('form.detalles.0.articulo_id', $presentacion->articulo_id)
            ->set('form.detalles.0.presentacion_articulo_id', $presentacion->id)
            ->set('form.detalles.0.cantidad_solicitada_detalle_pedido', '1')
            ->call('save')
            ->assertHasErrors(['form.sucursal_id']);
    }

    public function test_administrador_actualiza_pedido_pendiente_sin_alterar_snapshot_de_equivalencia(): void
    {
        $administrador = $this->crearAdministrador();
        $pedido = Pedido::factory()->for($administrador, 'registradoPor')->create();
        $presentacion = PresentacionArticulo::factory()->create(['equivalencia_base_presentacion_articulo' => '10.000']);
        $pedido->detalles()->create([
            'articulo_id' => $presentacion->articulo_id,
            'presentacion_articulo_id' => $presentacion->id,
            'cantidad_solicitada_detalle_pedido' => '1.000',
            'equivalencia_base_aplicada_detalle_pedido' => '8.000',
        ]);

        $this->actingAs($administrador)
            ->get(route('panel.pedidos.edit', $pedido))
            ->assertSee('Editar pedido')
            ->assertSee($pedido->codigo_pedido);

        Livewire::actingAs($administrador)
            ->test(EditPedido::class, ['pedidoId' => $pedido->id])
            ->assertSet('codigoPedido', $pedido->codigo_pedido)
            ->set('form.detalles.0.cantidad_solicitada_detalle_pedido', '3')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('panel.pedidos.index'))
            ->assertSessionHas('toast.title', 'Pedido actualizado');

        $this->assertSame('3.000', $pedido->fresh()->detalles()->sole()->cantidad_solicitada_detalle_pedido);
        $this->assertSame('8.000', $pedido->fresh()->detalles()->sole()->equivalencia_base_aplicada_detalle_pedido);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::Orders->value,
            'event' => 'updated',
            'subject_id' => $pedido->id,
            'causer_id' => $administrador->id,
        ]);
    }

    public function test_cancelacion_exige_motivo_conserva_detalle_y_bloquea_edicion(): void
    {
        $administrador = $this->crearAdministrador();
        $pedido = Pedido::factory()->for($administrador, 'registradoPor')->create();
        $presentacion = PresentacionArticulo::factory()->create();
        $pedido->detalles()->create([
            'articulo_id' => $presentacion->articulo_id,
            'presentacion_articulo_id' => $presentacion->id,
            'cantidad_solicitada_detalle_pedido' => 2,
            'equivalencia_base_aplicada_detalle_pedido' => $presentacion->equivalencia_base_presentacion_articulo,
        ]);

        $componente = Livewire::actingAs($administrador)->test(Index::class)
            ->call('openCancelModal', $pedido->id)
            ->call('cancelOrder')
            ->assertHasErrors(['cancellationReason' => ['required']])
            ->set('cancellationReason', 'La sucursal anuló la solicitud')
            ->call('cancelOrder')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Pedido cancelado', type: 'warning');

        $pedido->refresh();
        $this->assertSame(EstadoPedido::Cancelado, $pedido->estado_pedido);
        $this->assertSame($administrador->id, $pedido->cancelado_por);
        $this->assertSame('La sucursal anuló la solicitud', $pedido->motivo_cancelacion_pedido);
        $this->assertSame(1, $pedido->detalles()->count());
        Livewire::actingAs($administrador)
            ->test(EditPedido::class, ['pedidoId' => $pedido->id])
            ->assertForbidden();
    }

    public function test_usuario_de_solo_lectura_ve_detalle_pero_no_acciones_de_escritura(): void
    {
        $this->crearAdministrador();
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'Consulta de pedidos', 'guard_name' => 'web']);
        $rol->givePermissionTo(PermissionName::OrdersView->value);
        $usuario->assignRole($rol);
        $pedido = Pedido::factory()->create();

        Livewire::actingAs($usuario)
            ->test(Index::class)
            ->assertSee($pedido->codigo_pedido)
            ->assertDontSee('Nuevo pedido')
            ->assertDontSee(route('panel.pedidos.edit', $pedido), false)
            ->assertDontSee('wire:click="openCancelModal('.$pedido->id.')"', false)
            ->call('openDetailModal', $pedido->id)
            ->assertSet('showDetailModal', true);
    }

    public function test_hora_del_pedido_se_muestra_en_la_zona_horaria_de_bolivia_en_lista_y_detalle(): void
    {
        $administrador = $this->crearAdministrador();
        $this->travelTo('2026-09-04 20:11:00');
        $pedido = Pedido::factory()->for($administrador, 'registradoPor')->create();

        $componente = Livewire::actingAs($administrador)
            ->test(Index::class)
            ->assertSee('04/09/2026 16:11')
            ->assertDontSee('04/09/2026 20:11');

        $componente
            ->call('openDetailModal', $pedido->id)
            ->assertSet('showDetailModal', true)
            ->assertSee('04/09/2026 16:11')
            ->assertDontSee('04/09/2026 20:11');
    }

    public function test_filtros_de_estado_muestran_sus_contadores_y_solo_los_pedidos_seleccionados(): void
    {
        $administrador = $this->crearAdministrador();
        $pendiente = Pedido::factory()->create(['estado_pedido' => EstadoPedido::Pendiente]);
        $preparado = Pedido::factory()->create(['estado_pedido' => EstadoPedido::Preparado]);
        $cancelado = Pedido::factory()->create(['estado_pedido' => EstadoPedido::Cancelado]);

        $componente = Livewire::actingAs($administrador)->test(Index::class);

        $this->assertSame([
            'all' => 3,
            EstadoPedido::Pendiente->value => 1,
            EstadoPedido::Preparado->value => 1,
            EstadoPedido::Cancelado->value => 1,
        ], $componente->get('statusCounts'));

        $componente
            ->set('status', EstadoPedido::Preparado->value)
            ->assertSee($preparado->codigo_pedido)
            ->assertDontSee($pendiente->codigo_pedido)
            ->assertDontSee($cancelado->codigo_pedido);
    }

    public function test_pedido_preparado_no_puede_editarse_ni_cancelarse_desde_pedidos(): void
    {
        $administrador = $this->crearAdministrador();
        $pedido = Pedido::factory()->create(['estado_pedido' => EstadoPedido::Preparado]);

        $componente = Livewire::actingAs($administrador)
            ->test(Index::class)
            ->assertDontSee(route('panel.pedidos.edit', $pedido), false)
            ->assertDontSee('wire:click="openCancelModal('.$pedido->id.')"', false);

        Livewire::actingAs($administrador)
            ->test(EditPedido::class, ['pedidoId' => $pedido->id])
            ->assertForbidden();
    }

    private function crearAdministrador(): User
    {
        $administrador = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrador->refresh();
    }
}
