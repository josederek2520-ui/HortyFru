<?php

namespace Tests\Feature;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoRecepcion;
use App\Enums\PermissionName;
use App\Enums\TipoEquivalenciaPresentacionArticulo;
use App\Enums\TipoMovimientoInventario;
use App\Enums\TipoOrigenLote;
use App\Enums\TipoReferenciaMovimientoInventario;
use App\Enums\UsoPresentacionArticulo;
use App\Livewire\Panel\Recepciones\Details;
use App\Models\Activity;
use App\Models\Articulo;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\DetalleMovimientoInventario;
use App\Models\DetalleRecepcion;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\PresentacionArticulo;
use App\Models\Recepcion;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GestionDetallesRecepcionesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_usuario_no_autenticado_es_redirigido_desde_productos_de_recepcion(): void
    {
        $recepcion = Recepcion::factory()->create();

        $this->get(route('panel.recepciones.details', $recepcion))
            ->assertRedirect(route('login'));
    }

    public function test_pagina_explica_que_todo_lo_recibido_ingresa_al_almacen(): void
    {
        $administrador = $this->crearAdministrador();
        $recepcion = Recepcion::factory()->create();

        Livewire::actingAs($administrador)
            ->test(Details::class, ['recepcion' => $recepcion])
            ->assertSee('Todo lo registrado aquí ingresa al almacén')
            ->assertSee('Cantidad física recibida')
            ->assertSee('Confirmar recepción');
    }

    public function test_recepcion_sin_compra_guarda_producto_directo_en_unidad_base(): void
    {
        $administrador = $this->crearAdministrador();
        $recepcion = Recepcion::factory()->create();
        $articulo = Articulo::factory()->create(['nombre_articulo' => 'Papaya']);

        Livewire::actingAs($administrador)
            ->test(Details::class, ['recepcion' => $recepcion])
            ->call('openCreateModal')
            ->call('selectArticle', (string) $articulo->id)
            ->set('form.presentacion_articulo_id', '')
            ->set('form.cantidad_base_detalle_recepcion', '46,500')
            ->set('form.calidad_detalle_recepcion', '  Madura   firme ')
            ->set('form.observaciones_detalle_recepcion', '  Descargada   en almacén ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Producto recibido agregado', type: 'success');

        $detalle = DetalleRecepcion::query()->firstOrFail();
        $this->assertSame($recepcion->id, $detalle->recepcion_id);
        $this->assertNull($detalle->detalle_compra_id);
        $this->assertSame($articulo->id, $detalle->articulo_id);
        $this->assertNull($detalle->presentacion_articulo_id);
        $this->assertSame('46.500', $detalle->cantidad_base_detalle_recepcion);
        $this->assertSame($articulo->unidad_medida_id, $detalle->unidad_medida_id);
        $this->assertSame('Madura firme', $detalle->calidad_detalle_recepcion);
        $this->assertNull($detalle->lote_id);
    }

    public function test_presentacion_fija_calcula_cantidad_base_y_conserva_equivalencia(): void
    {
        $administrador = $this->crearAdministrador();
        $recepcion = Recepcion::factory()->create();
        $articulo = Articulo::factory()->create();
        $presentacion = PresentacionArticulo::factory()->for($articulo)->create([
            'tipo_equivalencia_presentacion_articulo' => TipoEquivalenciaPresentacionArticulo::Fija,
            'equivalencia_base_presentacion_articulo' => '15.000',
            'uso_presentacion_articulo' => UsoPresentacionArticulo::Compra,
        ]);

        Livewire::actingAs($administrador)
            ->test(Details::class, ['recepcion' => $recepcion])
            ->call('openCreateModal')
            ->call('selectArticle', (string) $articulo->id)
            ->set('form.presentacion_articulo_id', (string) $presentacion->id)
            ->set('form.cantidad_presentaciones_detalle_recepcion', '3')
            ->set('form.cantidad_base_detalle_recepcion', '999')
            ->call('save')
            ->assertHasNoErrors();

        $detalle = DetalleRecepcion::query()->firstOrFail();
        $this->assertSame('3.000', $detalle->cantidad_presentaciones_detalle_recepcion);
        $this->assertSame('15.000', $detalle->equivalencia_base_aplicada_detalle_recepcion);
        $this->assertSame('45.000', $detalle->cantidad_base_detalle_recepcion);
    }

    public function test_presentacion_variable_exige_cantidad_fisica_real(): void
    {
        $administrador = $this->crearAdministrador();
        $recepcion = Recepcion::factory()->create();
        $articulo = Articulo::factory()->create();
        $presentacion = PresentacionArticulo::factory()->for($articulo)->create([
            'tipo_equivalencia_presentacion_articulo' => TipoEquivalenciaPresentacionArticulo::Variable,
            'equivalencia_base_presentacion_articulo' => null,
        ]);

        Livewire::actingAs($administrador)
            ->test(Details::class, ['recepcion' => $recepcion])
            ->call('openCreateModal')
            ->call('selectArticle', (string) $articulo->id)
            ->set('form.presentacion_articulo_id', (string) $presentacion->id)
            ->set('form.cantidad_presentaciones_detalle_recepcion', '3')
            ->call('save')
            ->assertHasErrors(['form.cantidad_base_detalle_recepcion']);

        $this->assertSame(0, DetalleRecepcion::query()->count());
    }

    public function test_recepcion_vinculada_solo_acepta_productos_de_su_compra(): void
    {
        $administrador = $this->crearAdministrador();
        $compra = Compra::factory()->registrada()->create();
        $otraCompra = Compra::factory()->registrada()->create();
        $articulo = Articulo::factory()->create();
        $detalleCompra = DetalleCompra::factory()->for($compra)->for($articulo)->create([
            'cantidad_real_detalle_compra' => '12.000',
        ]);
        $detalleAjeno = DetalleCompra::factory()->for($otraCompra)->create();
        $recepcion = Recepcion::factory()->for($compra)->create();

        Livewire::actingAs($administrador)
            ->test(Details::class, ['recepcion' => $recepcion])
            ->call('openCreateModal')
            ->set('form.detalle_compra_id', (string) $detalleAjeno->id)
            ->set('form.articulo_id', (string) $detalleAjeno->articulo_id)
            ->set('form.cantidad_base_detalle_recepcion', '10')
            ->call('save')
            ->assertHasErrors(['form.detalle_compra_id']);

        Livewire::actingAs($administrador)
            ->test(Details::class, ['recepcion' => $recepcion])
            ->call('openCreateModal')
            ->call('selectPurchaseDetail', (string) $detalleCompra->id)
            ->call('save')
            ->assertHasNoErrors();

        $detalle = DetalleRecepcion::query()->firstOrFail();
        $this->assertSame($detalleCompra->id, $detalle->detalle_compra_id);
        $this->assertSame($articulo->id, $detalle->articulo_id);
        $this->assertSame('12.000', $detalle->cantidad_base_detalle_recepcion);
    }

    public function test_rechaza_duplicados_html_y_vencimiento_anterior(): void
    {
        $administrador = $this->crearAdministrador();
        $recepcion = Recepcion::factory()->create(['fecha_recepcion' => '2026-09-10 14:00:00']);
        $articulo = Articulo::factory()->create();

        $this->agregarProductoDirecto($administrador, $recepcion, $articulo, '5')->assertHasNoErrors();
        $this->agregarProductoDirecto($administrador, $recepcion, $articulo, '3')
            ->assertHasErrors(['form.articulo_id']);

        $otroArticulo = Articulo::factory()->create();
        Livewire::actingAs($administrador)
            ->test(Details::class, ['recepcion' => $recepcion])
            ->call('openCreateModal')
            ->call('selectArticle', (string) $otroArticulo->id)
            ->set('form.cantidad_base_detalle_recepcion', '2')
            ->set('form.fecha_vencimiento_detalle_recepcion', '2026-09-09')
            ->call('save')
            ->assertHasErrors(['form.fecha_vencimiento_detalle_recepcion']);

        Livewire::actingAs($administrador)
            ->test(Details::class, ['recepcion' => $recepcion])
            ->call('openCreateModal')
            ->call('selectArticle', (string) $otroArticulo->id)
            ->set('form.cantidad_base_detalle_recepcion', '2')
            ->set('form.observaciones_detalle_recepcion', '<script>alert(1)</script>')
            ->call('save')
            ->assertHasErrors(['form.observaciones_detalle_recepcion']);
    }

    public function test_editar_y_eliminar_solo_funciona_mientras_esta_en_borrador(): void
    {
        $administrador = $this->crearAdministrador();
        $recepcion = Recepcion::factory()->create();
        $articulo = Articulo::factory()->create();
        $this->agregarProductoDirecto($administrador, $recepcion, $articulo, '5');
        $detalle = DetalleRecepcion::query()->firstOrFail();

        Livewire::actingAs($administrador)
            ->test(Details::class, ['recepcion' => $recepcion])
            ->call('openEditModal', $detalle->id)
            ->set('form.cantidad_base_detalle_recepcion', '8')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('8.000', $detalle->fresh()->cantidad_base_detalle_recepcion);

        Livewire::actingAs($administrador)
            ->test(Details::class, ['recepcion' => $recepcion])
            ->call('openDeleteModal', $detalle->id)
            ->call('deleteDetail')
            ->assertHasNoErrors();

        $this->assertModelMissing($detalle);
    }

    public function test_confirmar_exige_al_menos_un_producto(): void
    {
        $administrador = $this->crearAdministrador();
        $recepcion = Recepcion::factory()->create();

        Livewire::actingAs($administrador)
            ->test(Details::class, ['recepcion' => $recepcion])
            ->call('confirmReception')
            ->assertHasErrors(['confirmacion']);

        $this->assertSame(EstadoRecepcion::Borrador, $recepcion->fresh()->estado_recepcion);
        $this->assertSame(0, Lote::query()->count());
        $this->assertSame(0, MovimientoInventario::query()->count());
    }

    public function test_confirmar_genera_lotes_y_una_entrada_de_inventario(): void
    {
        $this->travelTo('2026-09-10 18:00:00');
        $administrador = $this->crearAdministrador();
        $recepcion = Recepcion::factory()->create([
            'fecha_recepcion' => '2026-09-10 14:30:00',
        ]);
        $papaya = Articulo::factory()->create(['nombre_articulo' => 'Papaya']);
        $zanahoria = Articulo::factory()->create(['nombre_articulo' => 'Zanahoria']);
        $this->agregarProductoDirecto($administrador, $recepcion, $papaya, '46.500');
        $this->agregarProductoDirecto($administrador, $recepcion, $zanahoria, '30');

        Livewire::actingAs($administrador)
            ->test(Details::class, ['recepcion' => $recepcion])
            ->call('confirmReception')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Recepción confirmada', type: 'success');

        $recepcion->refresh();
        $this->assertSame(EstadoRecepcion::Confirmada, $recepcion->estado_recepcion);
        $this->assertSame($administrador->id, $recepcion->confirmado_por);
        $this->assertNotNull($recepcion->confirmado_en);
        $this->assertSame(2, Lote::query()->count());
        $this->assertSame(0, Lote::query()->where('tipo_origen_lote', '!=', TipoOrigenLote::Recepcion)->count());

        $movimiento = MovimientoInventario::query()->firstOrFail();
        $this->assertSame(TipoMovimientoInventario::EntradaRecepcion, $movimiento->tipo_movimiento_inventario);
        $this->assertSame(TipoReferenciaMovimientoInventario::Recepcion, $movimiento->tipo_referencia_movimiento_inventario);
        $this->assertSame($recepcion->id, $movimiento->referencia_id_movimiento_inventario);
        $this->assertSame($recepcion->almacen_id, $movimiento->almacen_id);
        $this->assertSame(2, DetalleMovimientoInventario::query()->count());
        $this->assertEqualsCanonicalizing(
            ['46.500', '30.000'],
            DetalleMovimientoInventario::query()->pluck('cantidad_movimiento_inventario')->all(),
        );
        $this->assertSame(0, DetalleRecepcion::query()->whereNull('lote_id')->count());
        $this->assertSame(1, Activity::query()
            ->where('log_name', ActivityLogName::Receptions->value)
            ->where('event', ActivityEvent::StatusChanged->value)
            ->where('subject_id', $recepcion->id)
            ->count());

        Livewire::actingAs($administrador)
            ->test(Details::class, ['recepcion' => $recepcion])
            ->call('openCreateModal')
            ->assertForbidden();
    }

    public function test_usuario_solo_lectura_no_puede_modificar_ni_confirmar(): void
    {
        $this->crearAdministrador();
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'Consulta recepciones', 'guard_name' => 'web']);
        $rol->givePermissionTo(PermissionName::ReceptionsView->value);
        $usuario->assignRole($rol);
        $recepcion = Recepcion::factory()->create();

        $this->actingAs($usuario)
            ->get(route('panel.recepciones.details', $recepcion))
            ->assertOk();

        Livewire::actingAs($usuario)
            ->test(Details::class, ['recepcion' => $recepcion])
            ->assertDontSee('wire:click="openCreateModal"', false)
            ->assertDontSee('Confirmar recepción')
            ->call('openCreateModal')
            ->assertForbidden();
    }

    private function agregarProductoDirecto(
        User $usuario,
        Recepcion $recepcion,
        Articulo $articulo,
        string $cantidad,
    ): Testable {
        return Livewire::actingAs($usuario)
            ->test(Details::class, ['recepcion' => $recepcion])
            ->call('openCreateModal')
            ->call('selectArticle', (string) $articulo->id)
            ->set('form.presentacion_articulo_id', '')
            ->set('form.cantidad_base_detalle_recepcion', $cantidad)
            ->call('save');
    }

    private function crearAdministrador(): User
    {
        $administrador = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrador->refresh();
    }
}
