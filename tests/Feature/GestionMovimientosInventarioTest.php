<?php

namespace Tests\Feature;

use App\Actions\Inventario\CalcularSaldoLoteAction;
use App\Actions\Inventario\RegistrarMovimientoInventarioAction;
use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoLote;
use App\Enums\EstadoMovimientoInventario;
use App\Enums\TipoMovimientoInventario;
use App\Enums\TipoReferenciaMovimientoInventario;
use App\Livewire\Panel\MovimientosInventario\Index;
use App\Models\Almacen;
use App\Models\Articulo;
use App\Models\DetalleMovimientoInventario;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class GestionMovimientosInventarioTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_usuario_no_autenticado_es_redirigido_desde_movimientos(): void
    {
        $this->get(route('panel.movimientos-inventario.index'))->assertRedirect(route('login'));
    }

    public function test_usuario_sin_permiso_no_puede_ver_movimientos(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get(route('panel.movimientos-inventario.index'))->assertForbidden();
        Livewire::actingAs($usuario)->test(Index::class)->assertForbidden();
    }

    public function test_modulo_es_solo_historial_y_explica_el_registro_automatico(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->assertSee('Movimientos de inventario')
            ->assertSee('Registro automático')
            ->assertSee('recepciones, pedidos, producción, mermas y ajustes autorizados')
            ->assertDontSee('Nuevo movimiento');
    }

    public function test_entrada_registra_cabecera_detalle_unidad_base_y_auditoria(): void
    {
        $administrador = $this->crearAdministrador();
        $almacen = Almacen::factory()->create(['nombre_almacen' => 'Almacén central']);
        $papaya = Articulo::factory()->create(['nombre_articulo' => 'Papaya']);
        $lote = Lote::factory()->for($papaya)->create([
            'codigo_lote' => 'LOT-PAPAYA-001',
            'fecha_vencimiento_lote' => '2026-09-20',
        ]);

        $movimiento = $this->registrarMovimiento(
            TipoMovimientoInventario::EntradaRecepcion,
            $almacen,
            $lote,
            '42.500',
            $administrador,
            TipoReferenciaMovimientoInventario::Recepcion,
            25,
        );

        $this->assertSame('MOV-10092026-001', $movimiento->codigo_movimiento_inventario);
        $this->assertSame(TipoMovimientoInventario::EntradaRecepcion, $movimiento->tipo_movimiento_inventario);
        $this->assertSame(EstadoMovimientoInventario::Registrado, $movimiento->estado_movimiento_inventario);
        $this->assertCount(1, $movimiento->detalles);
        $this->assertSame($papaya->id, $movimiento->detalles->first()->articulo_id);
        $this->assertSame($papaya->unidad_medida_id, $movimiento->detalles->first()->unidad_medida_id);
        $this->assertSame('42.500', $movimiento->detalles->first()->cantidad_movimiento_inventario);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::InventoryMovements->value,
            'event' => ActivityEvent::Created->value,
            'subject_type' => MovimientoInventario::class,
            'subject_id' => $movimiento->id,
            'causer_id' => $administrador->id,
        ]);
    }

    public function test_saldo_se_calcula_con_entradas_menos_salidas_sin_stock_actual(): void
    {
        $administrador = $this->crearAdministrador();
        $almacen = Almacen::factory()->create();
        $lote = Lote::factory()->create(['fecha_vencimiento_lote' => '2026-09-20']);

        $this->registrarMovimiento(TipoMovimientoInventario::EntradaRecepcion, $almacen, $lote, '120', $administrador, TipoReferenciaMovimientoInventario::Recepcion, 1);
        $this->registrarMovimiento(TipoMovimientoInventario::SalidaPedido, $almacen, $lote, '35', $administrador, TipoReferenciaMovimientoInventario::Pedido, 1);
        $this->registrarMovimiento(TipoMovimientoInventario::SalidaMerma, $almacen, $lote, '4', $administrador, TipoReferenciaMovimientoInventario::Merma, 1);

        $saldo = app(CalcularSaldoLoteAction::class)($lote, $almacen);

        $this->assertSame('81.000', $saldo->toScale(3)->__toString());
        $this->assertFalse(Schema::hasColumn('lotes', 'stock_actual'));
    }

    public function test_salida_exacta_marca_el_lote_como_agotado(): void
    {
        $administrador = $this->crearAdministrador();
        $almacen = Almacen::factory()->create();
        $lote = Lote::factory()->create(['fecha_vencimiento_lote' => '2026-09-20']);

        $this->registrarMovimiento(TipoMovimientoInventario::AjustePositivo, $almacen, $lote, '10', $administrador);
        $this->registrarMovimiento(TipoMovimientoInventario::SalidaPedido, $almacen, $lote, '10', $administrador, TipoReferenciaMovimientoInventario::Pedido, 9);

        $this->assertSame(EstadoLote::Agotado, $lote->refresh()->estado_lote);
    }

    public function test_lote_sigue_disponible_si_conserva_saldo_en_otro_almacen(): void
    {
        $administrador = $this->crearAdministrador();
        $almacenPrincipal = Almacen::factory()->create();
        $almacenSecundario = Almacen::factory()->create();
        $lote = Lote::factory()->create(['fecha_vencimiento_lote' => '2026-09-20']);

        $this->registrarMovimiento(TipoMovimientoInventario::AjustePositivo, $almacenPrincipal, $lote, '10', $administrador);
        $this->registrarMovimiento(TipoMovimientoInventario::AjustePositivo, $almacenSecundario, $lote, '3', $administrador);
        $this->registrarMovimiento(TipoMovimientoInventario::SalidaPedido, $almacenPrincipal, $lote, '10', $administrador, TipoReferenciaMovimientoInventario::Pedido, 10);

        $this->assertSame(EstadoLote::Disponible, $lote->refresh()->estado_lote);
        $this->assertSame('0.000', app(CalcularSaldoLoteAction::class)($lote, $almacenPrincipal)->toScale(3)->__toString());
        $this->assertSame('3.000', app(CalcularSaldoLoteAction::class)($lote, $almacenSecundario)->toScale(3)->__toString());
    }

    public function test_no_permite_sacar_mas_cantidad_que_el_saldo_del_lote(): void
    {
        $administrador = $this->crearAdministrador();
        $almacen = Almacen::factory()->create();
        $lote = Lote::factory()->create(['fecha_vencimiento_lote' => '2026-09-20']);
        $this->registrarMovimiento(TipoMovimientoInventario::AjustePositivo, $almacen, $lote, '5', $administrador);

        try {
            $this->registrarMovimiento(TipoMovimientoInventario::SalidaPedido, $almacen, $lote, '6', $administrador, TipoReferenciaMovimientoInventario::Pedido, 30);
            $this->fail('Se esperaba una validación por saldo insuficiente.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('detalles', $exception->errors());
        }

        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->assertDatabaseCount('detalle_movimiento_inventario', 1);
    }

    public function test_no_permite_mover_un_lote_bloqueado(): void
    {
        $administrador = $this->crearAdministrador();
        $almacen = Almacen::factory()->create();
        $lote = Lote::factory()->bloqueado()->create(['fecha_vencimiento_lote' => '2026-09-20']);

        $this->expectException(ValidationException::class);

        $this->registrarMovimiento(TipoMovimientoInventario::AjustePositivo, $almacen, $lote, '5', $administrador);
    }

    public function test_reintento_de_una_misma_recepcion_no_duplica_el_movimiento(): void
    {
        $administrador = $this->crearAdministrador();
        $almacen = Almacen::factory()->create();
        $lote = Lote::factory()->create(['fecha_vencimiento_lote' => '2026-09-20']);

        $primero = $this->registrarMovimiento(TipoMovimientoInventario::EntradaRecepcion, $almacen, $lote, '25', $administrador, TipoReferenciaMovimientoInventario::Recepcion, 77);
        $segundo = $this->registrarMovimiento(TipoMovimientoInventario::EntradaRecepcion, $almacen, $lote, '25', $administrador, TipoReferenciaMovimientoInventario::Recepcion, 77);

        $this->assertSame($primero->id, $segundo->id);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->assertDatabaseCount('detalle_movimiento_inventario', 1);
    }

    public function test_movimiento_anulado_no_afecta_el_saldo_calculado(): void
    {
        $almacen = Almacen::factory()->create();
        $lote = Lote::factory()->create();
        $registrado = MovimientoInventario::factory()->for($almacen)->create([
            'tipo_movimiento_inventario' => TipoMovimientoInventario::AjustePositivo,
            'estado_movimiento_inventario' => EstadoMovimientoInventario::Registrado,
        ]);
        $anulado = MovimientoInventario::factory()->for($almacen)->anulado()->create([
            'tipo_movimiento_inventario' => TipoMovimientoInventario::AjustePositivo,
        ]);
        $this->crearDetalle($registrado, $lote, '12.000');
        $this->crearDetalle($anulado, $lote, '100.000');

        $saldo = app(CalcularSaldoLoteAction::class)($lote, $almacen);

        $this->assertSame('12.000', $saldo->toScale(3)->__toString());
    }

    public function test_busqueda_y_tipo_filtran_el_historial(): void
    {
        $administrador = $this->crearAdministrador();
        $almacen = Almacen::factory()->create();
        $papaya = Articulo::factory()->create(['nombre_articulo' => 'Papaya']);
        $zanahoria = Articulo::factory()->create(['nombre_articulo' => 'Zanahoria']);
        $lotePapaya = Lote::factory()->for($papaya)->create(['codigo_lote' => 'LOT-PAPAYA', 'fecha_vencimiento_lote' => '2026-09-20']);
        $loteZanahoria = Lote::factory()->for($zanahoria)->create(['codigo_lote' => 'LOT-ZANAHORIA', 'fecha_vencimiento_lote' => '2026-09-20']);
        $entrada = $this->registrarMovimiento(TipoMovimientoInventario::AjustePositivo, $almacen, $lotePapaya, '10', $administrador);
        $otraEntrada = $this->registrarMovimiento(TipoMovimientoInventario::AjustePositivo, $almacen, $loteZanahoria, '10', $administrador);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->set('search', 'Papaya')
            ->set('type', TipoMovimientoInventario::AjustePositivo->value)
            ->assertSee($entrada->codigo_movimiento_inventario)
            ->assertDontSee($otraEntrada->codigo_movimiento_inventario);
    }

    private function crearAdministrador(): User
    {
        $administrador = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrador->refresh();
    }

    private function registrarMovimiento(
        TipoMovimientoInventario $tipo,
        Almacen $almacen,
        Lote $lote,
        string $cantidad,
        User $usuario,
        ?TipoReferenciaMovimientoInventario $tipoReferencia = null,
        ?int $referenciaId = null,
    ): MovimientoInventario {
        return app(RegistrarMovimientoInventarioAction::class)(
            $tipo,
            $almacen,
            CarbonImmutable::parse('2026-09-10 08:30', 'America/La_Paz'),
            [['lote' => $lote, 'cantidad' => $cantidad]],
            $usuario,
            $tipoReferencia,
            $referenciaId,
        );
    }

    private function crearDetalle(MovimientoInventario $movimiento, Lote $lote, string $cantidad): DetalleMovimientoInventario
    {
        return DetalleMovimientoInventario::factory()->for($movimiento, 'movimientoInventario')->create([
            'lote_id' => $lote->id,
            'articulo_id' => $lote->articulo_id,
            'unidad_medida_id' => $lote->articulo->unidad_medida_id,
            'cantidad_movimiento_inventario' => $cantidad,
        ]);
    }
}
