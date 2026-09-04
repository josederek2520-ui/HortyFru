<?php

namespace Tests\Feature;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoPedido;
use App\Enums\PermissionName;
use App\Livewire\Panel\Pedidos\Edit as EditPedido;
use App\Livewire\Panel\Pedidos\Preparation;
use App\Models\Articulo;
use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\PresentacionArticulo;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PreparacionPedidosTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_usuario_no_autenticado_es_redirigido_desde_preparacion(): void
    {
        $this->get(route('panel.pedidos.preparation'))->assertRedirect(route('login'));
    }

    public function test_usuario_sin_permiso_no_puede_abrir_preparacion(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get(route('panel.pedidos.preparation'))->assertForbidden();
        Livewire::actingAs($usuario)->test(Preparation::class)->assertForbidden();
    }

    public function test_permiso_de_preparacion_es_suficiente_para_usar_la_hoja(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'Encargado de preparación', 'guard_name' => 'web']);
        $rol->givePermissionTo(PermissionName::OrdersPrepare->value);
        $usuario->assignRole($rol);

        $this->actingAs($usuario)
            ->get(route('panel.pedidos.preparation'))
            ->assertSee('Preparación de pedidos');
    }

    public function test_hoja_consolida_el_mismo_producto_por_sucursal_y_muestra_total(): void
    {
        $administrador = $this->crearAdministrador();
        $fecha = today()->addDay();
        $articulo = Articulo::factory()->create(['nombre_articulo' => 'Tomate']);
        $presentacion = PresentacionArticulo::factory()->for($articulo, 'articulo')->create([
            'nombre_presentacion_articulo' => 'Caja',
        ]);
        $pedidoCentro = Pedido::factory()->for(Sucursal::factory()->state(['nombre_sucursal' => 'Centro']), 'sucursal')->create([
            'fecha_requerida_pedido' => $fecha,
        ]);
        $pedidoNorte = Pedido::factory()->for(Sucursal::factory()->state(['nombre_sucursal' => 'Norte']), 'sucursal')->create([
            'fecha_requerida_pedido' => $fecha,
        ]);
        $this->crearDetalle($pedidoCentro, $presentacion, '2.000');
        $this->crearDetalle($pedidoNorte, $presentacion, '3.000');

        Livewire::actingAs($administrador)
            ->test(Preparation::class)
            ->set('requiredDate', $fecha->toDateString())
            ->assertSee('Tomate')
            ->assertSee('Centro')
            ->assertSee('Norte')
            ->assertSee('5');
    }

    public function test_hoja_muestra_imagen_resumen_y_filtra_lineas_por_preparacion(): void
    {
        $administrador = $this->crearAdministrador();
        $pedido = Pedido::factory()->create(['fecha_requerida_pedido' => today()->addDay()]);
        $tomate = Articulo::factory()->create([
            'nombre_articulo' => 'Tomate con imagen',
            'imagen_articulo' => 'articulos/tomate.jpg',
        ]);
        $papaya = Articulo::factory()->create(['nombre_articulo' => 'Papaya preparada']);
        $cajaTomate = PresentacionArticulo::factory()->for($tomate, 'articulo')->create();
        $cajaPapaya = PresentacionArticulo::factory()->for($papaya, 'articulo')->create();
        $this->crearDetalle($pedido, $cajaTomate, '2.000');
        $this->crearDetalle($pedido, $cajaPapaya, '1.000', true, $administrador);

        $componente = Livewire::actingAs($administrador)->test(Preparation::class);

        $this->assertSame(2, $componente->get('totalDetails'));
        $this->assertSame(1, $componente->get('pendingDetails'));
        $this->assertSame(1, $componente->get('preparedDetails'));
        $componente
            ->set('preparationStatus', 'pending')
            ->assertSee('Tomate con imagen')
            ->assertSee('articulos/tomate.jpg')
            ->assertDontSee('Papaya preparada')
            ->set('preparationStatus', 'prepared')
            ->assertSee('Papaya preparada')
            ->assertDontSee('Tomate con imagen');
    }

    public function test_al_marcar_todas_las_lineas_pedido_cambia_automaticamente_a_preparado(): void
    {
        $administrador = $this->crearAdministrador();
        $pedido = Pedido::factory()->for($administrador, 'registradoPor')->create([
            'fecha_requerida_pedido' => today()->addDay(),
        ]);
        $primeraPresentacion = PresentacionArticulo::factory()->create();
        $segundaPresentacion = PresentacionArticulo::factory()->create();
        $primerDetalle = $this->crearDetalle($pedido, $primeraPresentacion, '2.000');
        $segundoDetalle = $this->crearDetalle($pedido, $segundaPresentacion, '1.000');

        $componente = Livewire::actingAs($administrador)
            ->test(Preparation::class)
            ->call('setPrepared', $primerDetalle->id, true)
            ->assertHasNoErrors();

        $this->assertTrue($primerDetalle->fresh()->preparado_detalle_pedido);
        $this->assertSame(EstadoPedido::Pendiente, $pedido->fresh()->estado_pedido);
        Livewire::actingAs($administrador)
            ->test(EditPedido::class, ['pedidoId' => $pedido->id])
            ->assertForbidden();

        $componente
            ->call('setPrepared', $segundoDetalle->id, true)
            ->assertDispatched('toast', title: 'Pedido preparado', type: 'success');

        $pedido->refresh();
        $this->assertSame(EstadoPedido::Preparado, $pedido->estado_pedido);
        $this->assertSame($administrador->id, $pedido->preparado_por);
        $this->assertNotNull($pedido->preparado_en);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::Orders->value,
            'event' => ActivityEvent::PreparationUpdated->value,
            'subject_id' => $pedido->id,
            'causer_id' => $administrador->id,
        ]);
    }

    public function test_desmarcar_una_linea_devuelve_el_pedido_a_pendiente(): void
    {
        $administrador = $this->crearAdministrador();
        $pedido = Pedido::factory()->for($administrador, 'registradoPor')->create([
            'fecha_requerida_pedido' => today()->addDay(),
            'estado_pedido' => EstadoPedido::Preparado,
            'preparado_por' => $administrador->id,
            'preparado_en' => now(),
        ]);
        $presentacion = PresentacionArticulo::factory()->create();
        $detalle = $this->crearDetalle($pedido, $presentacion, '2.000', true, $administrador);

        Livewire::actingAs($administrador)
            ->test(Preparation::class)
            ->call('setPrepared', $detalle->id, false)
            ->assertHasNoErrors();

        $pedido->refresh();
        $this->assertFalse($detalle->fresh()->preparado_detalle_pedido);
        $this->assertSame(EstadoPedido::Pendiente, $pedido->estado_pedido);
        $this->assertNull($pedido->preparado_por);
        $this->assertNull($pedido->preparado_en);
    }

    public function test_pedido_cancelado_no_aparece_ni_permite_marcar_preparacion(): void
    {
        $administrador = $this->crearAdministrador();
        $pedido = Pedido::factory()->create([
            'fecha_requerida_pedido' => today()->addDay(),
            'estado_pedido' => EstadoPedido::Cancelado,
        ]);
        $presentacion = PresentacionArticulo::factory()->create();
        $detalle = $this->crearDetalle($pedido, $presentacion, '1.000');

        $componente = Livewire::actingAs($administrador)
            ->test(Preparation::class)
            ->assertDontSee($pedido->codigo_pedido);

        $componente->call('setPrepared', $detalle->id, true)->assertForbidden();
        $this->assertFalse($detalle->fresh()->preparado_detalle_pedido);
    }

    private function crearDetalle(
        Pedido $pedido,
        PresentacionArticulo $presentacion,
        string $cantidad,
        bool $preparado = false,
        ?User $usuario = null,
    ): DetallePedido {
        return $pedido->detalles()->create([
            'articulo_id' => $presentacion->articulo_id,
            'presentacion_articulo_id' => $presentacion->id,
            'cantidad_solicitada_detalle_pedido' => $cantidad,
            'equivalencia_base_aplicada_detalle_pedido' => $presentacion->equivalencia_base_presentacion_articulo,
            'preparado_detalle_pedido' => $preparado,
            'preparado_por' => $preparado ? $usuario?->id : null,
            'preparado_en' => $preparado ? now() : null,
        ]);
    }

    private function crearAdministrador(): User
    {
        $administrador = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrador->refresh();
    }
}
