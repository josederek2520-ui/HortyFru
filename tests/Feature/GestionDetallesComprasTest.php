<?php

namespace Tests\Feature;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoCompra;
use App\Enums\EstadoPedido;
use App\Enums\PermissionName;
use App\Enums\PrecioPorDetalleCompra;
use App\Enums\TipoEquivalenciaPresentacionArticulo;
use App\Enums\UsoPresentacionArticulo;
use App\Livewire\Panel\Compras\Details;
use App\Models\Articulo;
use App\Models\CategoriaArticulo;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\PresentacionArticulo;
use App\Models\Sucursal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GestionDetallesComprasTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_formulario_solicita_cantidad_real_y_total_pagado(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->create();

        Livewire::actingAs($administrador)
            ->test(Details::class, ['compra' => $compra])
            ->call('openCreateModal')
            ->assertSee('Cant. real')
            ->assertSee('Total pagado')
            ->assertDontSee('Precio Bs')
            ->assertDontSee('Precio aplicado por');
    }

    public function test_consolida_pedidos_de_la_fecha_por_sucursal_y_precarga_el_producto(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->create([
            'fecha_compra' => CarbonImmutable::parse('2026-09-12 07:00', 'America/La_Paz')->utc(),
        ]);
        $categoria = CategoriaArticulo::factory()->create(['nombre_categoria_articulo' => 'Hortalizas']);
        $yuca = Articulo::factory()->create([
            'nombre_articulo' => 'Yuca',
            'categoria_articulo_id' => $categoria->id,
        ]);
        $caja = PresentacionArticulo::factory()->for($yuca)->create([
            'nombre_presentacion_articulo' => 'Caja',
            'uso_presentacion_articulo' => UsoPresentacionArticulo::Pedido,
            'predeterminada_pedido_presentacion_articulo' => true,
        ]);
        $saco = PresentacionArticulo::factory()->for($yuca)->create([
            'nombre_presentacion_articulo' => 'Saco',
            'uso_presentacion_articulo' => UsoPresentacionArticulo::Compra,
            'tipo_equivalencia_presentacion_articulo' => TipoEquivalenciaPresentacionArticulo::Variable,
            'equivalencia_base_presentacion_articulo' => null,
            'predeterminada_compra_presentacion_articulo' => true,
        ]);
        $achumani = Sucursal::factory()->create(['nombre_sucursal' => 'Achumani']);
        $sopocachi = Sucursal::factory()->create(['nombre_sucursal' => 'Sopocachi']);
        $pedidoAchumani = Pedido::factory()->for($achumani)->create([
            'fecha_requerida_pedido' => '2026-09-12',
            'estado_pedido' => EstadoPedido::Pendiente,
        ]);
        $pedidoSopocachi = Pedido::factory()->for($sopocachi)->create([
            'fecha_requerida_pedido' => '2026-09-12',
            'estado_pedido' => EstadoPedido::Preparado,
        ]);
        DetallePedido::factory()->create([
            'pedido_id' => $pedidoAchumani->id,
            'articulo_id' => $yuca->id,
            'presentacion_articulo_id' => $caja->id,
            'cantidad_solicitada_detalle_pedido' => '2.000',
        ]);
        DetallePedido::factory()->create([
            'pedido_id' => $pedidoSopocachi->id,
            'articulo_id' => $yuca->id,
            'presentacion_articulo_id' => $caja->id,
            'cantidad_solicitada_detalle_pedido' => '3.500',
        ]);
        $sucursalCancelada = Sucursal::factory()->create(['nombre_sucursal' => 'Sucursal cancelada']);
        $pedidoCancelado = Pedido::factory()->for($sucursalCancelada)->create([
            'fecha_requerida_pedido' => '2026-09-12',
            'estado_pedido' => EstadoPedido::Cancelado,
        ]);
        DetallePedido::factory()->create([
            'pedido_id' => $pedidoCancelado->id,
            'articulo_id' => $yuca->id,
            'presentacion_articulo_id' => $caja->id,
            'cantidad_solicitada_detalle_pedido' => '99.000',
        ]);

        Livewire::actingAs($administrador)
            ->test(Details::class, ['compra' => $compra])
            ->assertSee('Necesidad de compra del 12/09/2026')
            ->assertSee('Hortalizas')
            ->assertSee('Achumani')
            ->assertSee('Sopocachi')
            ->assertSee('5,5')
            ->assertDontSee('Sucursal cancelada')
            ->call('openCreateModalFromNeed', $yuca->id)
            ->assertSet('showFormModal', true)
            ->assertSet('form.articulo_id', (string) $yuca->id)
            ->assertSet('form.presentacion_articulo_id', (string) $saco->id);
    }

    public function test_consolidado_marca_la_presentacion_habitual_ya_agregada(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->create([
            'fecha_compra' => CarbonImmutable::parse('2026-09-12 07:00', 'America/La_Paz')->utc(),
        ]);
        $articulo = Articulo::factory()->create(['nombre_articulo' => 'Papaya']);
        $presentacion = PresentacionArticulo::factory()->for($articulo)->create([
            'nombre_presentacion_articulo' => 'Caja',
            'uso_presentacion_articulo' => UsoPresentacionArticulo::Ambos,
            'predeterminada_compra_presentacion_articulo' => true,
        ]);
        $pedido = Pedido::factory()->create([
            'fecha_requerida_pedido' => '2026-09-12',
            'estado_pedido' => EstadoPedido::Pendiente,
        ]);
        DetallePedido::factory()->create([
            'pedido_id' => $pedido->id,
            'articulo_id' => $articulo->id,
            'presentacion_articulo_id' => $presentacion->id,
            'cantidad_solicitada_detalle_pedido' => '2.000',
        ]);
        DetalleCompra::factory()->create([
            'compra_id' => $compra->id,
            'articulo_id' => $articulo->id,
            'presentacion_articulo_id' => $presentacion->id,
            'cantidad_presentaciones_detalle_compra' => '2.000',
            'equivalencia_base_aplicada_detalle_compra' => $presentacion->equivalencia_base_presentacion_articulo,
            'precio_por_detalle_compra' => PrecioPorDetalleCompra::Presentacion,
        ]);

        Livewire::actingAs($administrador)
            ->test(Details::class, ['compra' => $compra])
            ->assertSee('Papaya')
            ->assertSee('Ya agregado')
            ->assertDontSee('wire:click="openCreateModalFromNeed('.$articulo->id.')"', false);
    }

    public function test_compra_con_presentacion_fija_calcula_cantidad_real_subtotal_y_total(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->for($administrador, 'registradoPor')->create();
        $articulo = Articulo::factory()->create();
        $presentacion = PresentacionArticulo::factory()->for($articulo)->create([
            'nombre_presentacion_articulo' => 'Caja de 10 kg',
            'tipo_equivalencia_presentacion_articulo' => TipoEquivalenciaPresentacionArticulo::Fija,
            'equivalencia_base_presentacion_articulo' => '10.000',
            'uso_presentacion_articulo' => UsoPresentacionArticulo::Compra,
        ]);

        Livewire::actingAs($administrador)
            ->test(Details::class, ['compra' => $compra])
            ->call('openCreateModal')
            ->call('selectArticle', (string) $articulo->id)
            ->set('form.presentacion_articulo_id', (string) $presentacion->id)
            ->set('form.cantidad_presentaciones_detalle_compra', '3')
            ->set('form.total_pagado_detalle_compra', '60')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Producto agregado', type: 'success');

        $detalle = DetalleCompra::query()->firstOrFail();
        $this->assertSame('3.000', $detalle->cantidad_presentaciones_detalle_compra);
        $this->assertSame('10.000', $detalle->equivalencia_base_aplicada_detalle_compra);
        $this->assertSame('30.000', $detalle->cantidad_real_detalle_compra);
        $this->assertSame($articulo->unidad_medida_id, $detalle->unidad_medida_id);
        $this->assertSame('20.00', $detalle->precio_unitario_detalle_compra);
        $this->assertSame(PrecioPorDetalleCompra::Presentacion, $detalle->precio_por_detalle_compra);
        $this->assertSame('60.00', $detalle->subtotal_detalle_compra);
        $this->assertSame('60.00', $compra->refresh()->total_compra);
    }

    public function test_compra_directa_en_unidad_base_calcula_subtotal(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->create();
        $articulo = Articulo::factory()->create();

        $this->agregarCompraDirecta($administrador, $compra, $articulo, '2.500', '20.00')
            ->assertHasNoErrors();

        $detalle = DetalleCompra::query()->firstOrFail();
        $this->assertNull($detalle->presentacion_articulo_id);
        $this->assertNull($detalle->cantidad_presentaciones_detalle_compra);
        $this->assertSame('2.500', $detalle->cantidad_real_detalle_compra);
        $this->assertSame('8.00', $detalle->precio_unitario_detalle_compra);
        $this->assertSame(PrecioPorDetalleCompra::UnidadBase, $detalle->precio_por_detalle_compra);
        $this->assertSame('20.00', $detalle->subtotal_detalle_compra);
        $this->assertSame('20.00', $compra->refresh()->total_compra);
    }

    public function test_compra_directa_exige_cantidad_real(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->create();
        $articulo = Articulo::factory()->create();

        Livewire::actingAs($administrador)
            ->test(Details::class, ['compra' => $compra])
            ->call('openCreateModal')
            ->call('selectArticle', (string) $articulo->id)
            ->set('form.presentacion_articulo_id', '')
            ->set('form.total_pagado_detalle_compra', '25')
            ->call('save')
            ->assertHasErrors(['form.cantidad_real_detalle_compra']);

        $this->assertSame(0, DetalleCompra::query()->count());
    }

    public function test_presentacion_variable_permite_omitir_cantidad_real(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->create();
        $articulo = Articulo::factory()->create();
        $presentacion = PresentacionArticulo::factory()->for($articulo)->create([
            'tipo_equivalencia_presentacion_articulo' => TipoEquivalenciaPresentacionArticulo::Variable,
            'equivalencia_base_presentacion_articulo' => null,
        ]);

        Livewire::actingAs($administrador)
            ->test(Details::class, ['compra' => $compra])
            ->call('openCreateModal')
            ->call('selectArticle', (string) $articulo->id)
            ->set('form.presentacion_articulo_id', (string) $presentacion->id)
            ->set('form.cantidad_presentaciones_detalle_compra', '5')
            ->set('form.total_pagado_detalle_compra', '850')
            ->call('save')
            ->assertHasNoErrors();

        $detalle = DetalleCompra::query()->firstOrFail();
        $this->assertNull($detalle->cantidad_real_detalle_compra);
        $this->assertNull($detalle->unidad_medida_id);
        $this->assertSame('170.00', $detalle->precio_unitario_detalle_compra);
        $this->assertSame('850.00', $detalle->subtotal_detalle_compra);
    }

    public function test_tres_cajas_de_papaya_guardan_cantidad_real_y_total_pagado(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->create();
        $papaya = Articulo::factory()->create(['nombre_articulo' => 'Papaya']);
        $caja = PresentacionArticulo::factory()->for($papaya)->create([
            'nombre_presentacion_articulo' => 'Caja',
            'tipo_equivalencia_presentacion_articulo' => TipoEquivalenciaPresentacionArticulo::Variable,
            'equivalencia_base_presentacion_articulo' => null,
            'uso_presentacion_articulo' => UsoPresentacionArticulo::Compra,
        ]);

        Livewire::actingAs($administrador)
            ->test(Details::class, ['compra' => $compra])
            ->call('openCreateModal')
            ->call('selectArticle', (string) $papaya->id)
            ->set('form.presentacion_articulo_id', (string) $caja->id)
            ->set('form.cantidad_presentaciones_detalle_compra', '3')
            ->set('form.cantidad_real_detalle_compra', '45')
            ->set('form.total_pagado_detalle_compra', '400')
            ->call('save')
            ->assertHasNoErrors();

        $detalle = DetalleCompra::query()->firstOrFail();
        $this->assertSame('3.000', $detalle->cantidad_presentaciones_detalle_compra);
        $this->assertSame('45.000', $detalle->cantidad_real_detalle_compra);
        $this->assertSame($papaya->unidad_medida_id, $detalle->unidad_medida_id);
        $this->assertSame('133.33', $detalle->precio_unitario_detalle_compra);
        $this->assertSame(PrecioPorDetalleCompra::Presentacion, $detalle->precio_por_detalle_compra);
        $this->assertSame('400.00', $detalle->subtotal_detalle_compra);
        $this->assertSame('400.00', $compra->refresh()->total_compra);
    }

    public function test_presentacion_sin_fracciones_rechaza_cantidad_decimal(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->create();
        $articulo = Articulo::factory()->create();
        $presentacion = PresentacionArticulo::factory()->for($articulo)->create([
            'permite_fraccion_presentacion_articulo' => false,
            'tipo_equivalencia_presentacion_articulo' => TipoEquivalenciaPresentacionArticulo::Fija,
            'equivalencia_base_presentacion_articulo' => '5.000',
        ]);

        Livewire::actingAs($administrador)
            ->test(Details::class, ['compra' => $compra])
            ->call('openCreateModal')
            ->call('selectArticle', (string) $articulo->id)
            ->set('form.presentacion_articulo_id', (string) $presentacion->id)
            ->set('form.cantidad_presentaciones_detalle_compra', '1.500')
            ->set('form.total_pagado_detalle_compra', '15')
            ->call('save')
            ->assertHasErrors(['form.cantidad_presentaciones_detalle_compra']);
    }

    public function test_rechaza_presentacion_de_otro_articulo_y_linea_directa_duplicada(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->create();
        $articulo = Articulo::factory()->create();
        $otroArticulo = Articulo::factory()->create();
        $presentacionAjena = PresentacionArticulo::factory()->for($otroArticulo)->create();

        Livewire::actingAs($administrador)
            ->test(Details::class, ['compra' => $compra])
            ->call('openCreateModal')
            ->call('selectArticle', (string) $articulo->id)
            ->set('form.presentacion_articulo_id', (string) $presentacionAjena->id)
            ->set('form.cantidad_presentaciones_detalle_compra', '1')
            ->set('form.total_pagado_detalle_compra', '10')
            ->call('save')
            ->assertHasErrors(['form.presentacion_articulo_id']);

        $this->agregarCompraDirecta($administrador, $compra, $articulo, '1', '10')->assertHasNoErrors();
        $this->agregarCompraDirecta($administrador, $compra, $articulo, '2', '24')
            ->assertHasErrors(['form.articulo_id']);
        $this->assertSame(1, DetalleCompra::query()->count());
    }

    public function test_editar_y_eliminar_producto_recalcula_total(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->create();
        $articulo = Articulo::factory()->create();
        $this->agregarCompraDirecta($administrador, $compra, $articulo, '2', '20');
        $detalle = DetalleCompra::query()->firstOrFail();

        Livewire::actingAs($administrador)
            ->test(Details::class, ['compra' => $compra])
            ->call('openEditModal', $detalle->id)
            ->set('form.cantidad_real_detalle_compra', '3')
            ->set('form.total_pagado_detalle_compra', '30')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('30.00', $compra->refresh()->total_compra);

        Livewire::actingAs($administrador)
            ->test(Details::class, ['compra' => $compra])
            ->call('openDeleteModal', $detalle->id)
            ->call('deleteDetail')
            ->assertHasNoErrors();

        $this->assertModelMissing($detalle);
        $this->assertSame('0.00', $compra->refresh()->total_compra);
    }

    public function test_registrar_compra_exige_productos_y_luego_bloquea_cambios(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->for($administrador, 'registradoPor')->create();

        Livewire::actingAs($administrador)
            ->test(Details::class, ['compra' => $compra])
            ->call('registerPurchase')
            ->assertHasErrors(['registro']);

        $articulo = Articulo::factory()->create();
        $this->agregarCompraDirecta($administrador, $compra, $articulo, '2', '14');

        Livewire::actingAs($administrador)
            ->test(Details::class, ['compra' => $compra])
            ->call('registerPurchase')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Compra registrada', type: 'success');

        $this->assertSame(EstadoCompra::Registrada, $compra->refresh()->estado_compra);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::Purchases->value,
            'event' => ActivityEvent::StatusChanged->value,
            'subject_id' => $compra->id,
            'causer_id' => $administrador->id,
        ]);

        Livewire::actingAs($administrador)
            ->test(Details::class, ['compra' => $compra])
            ->call('openCreateModal')
            ->assertForbidden();
    }

    public function test_usuario_solo_lectura_ve_detalle_sin_poder_modificarlo(): void
    {
        $this->crearAdministrador();
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'Consulta detalle compras', 'guard_name' => 'web']);
        $rol->givePermissionTo(PermissionName::PurchasesView->value);
        $usuario->assignRole($rol);
        $compra = Compra::factory()->create();

        $this->actingAs($usuario)->get(route('panel.compras.details', $compra))->assertOk();
        Livewire::actingAs($usuario)
            ->test(Details::class, ['compra' => $compra])
            ->assertDontSee('wire:click="openCreateModal"', false)
            ->assertDontSee('Registrar compra')
            ->call('openCreateModal')
            ->assertForbidden();
    }

    public function test_observacion_del_producto_rechaza_html(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->create();
        $articulo = Articulo::factory()->create();

        $this->agregarCompraDirecta($administrador, $compra, $articulo, '1', '10', '<script>alert(1)</script>')
            ->assertHasErrors(['form.observaciones_detalle_compra' => ['not_regex']]);

        $this->assertSame(0, DetalleCompra::query()->count());
    }

    private function agregarCompraDirecta(
        User $usuario,
        Compra $compra,
        Articulo $articulo,
        string $cantidad,
        string $totalPagado,
        string $observacion = '',
    ): Testable {
        return Livewire::actingAs($usuario)
            ->test(Details::class, ['compra' => $compra])
            ->call('openCreateModal')
            ->call('selectArticle', (string) $articulo->id)
            ->set('form.presentacion_articulo_id', '')
            ->set('form.cantidad_real_detalle_compra', $cantidad)
            ->set('form.total_pagado_detalle_compra', $totalPagado)
            ->set('form.observaciones_detalle_compra', $observacion)
            ->call('save');
    }

    private function crearAdministrador(): User
    {
        $administrador = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrador->refresh();
    }
}
