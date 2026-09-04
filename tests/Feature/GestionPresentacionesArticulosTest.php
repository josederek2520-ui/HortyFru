<?php

namespace Tests\Feature;

use App\Enums\ActivityLogName;
use App\Enums\PermissionName;
use App\Enums\TipoEquivalenciaPresentacionArticulo;
use App\Enums\UsoPresentacionArticulo;
use App\Livewire\Panel\PresentacionesArticulos\Index;
use App\Models\Activity;
use App\Models\Articulo;
use App\Models\PresentacionArticulo;
use App\Models\UnidadMedida;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GestionPresentacionesArticulosTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_usuario_no_autenticado_es_redirigido_desde_gestion_de_presentaciones(): void
    {
        $this->get(route('panel.presentaciones-articulos.index'))->assertRedirect(route('login'));
    }

    public function test_usuario_sin_permiso_no_puede_ver_gestion_de_presentaciones(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get(route('panel.presentaciones-articulos.index'))->assertForbidden();
        Livewire::actingAs($usuario)->test(Index::class)->assertForbidden();
    }

    public function test_administrador_puede_ver_presentaciones_con_articulo_y_unidad(): void
    {
        $administrador = $this->crearAdministrador();
        $unidadMedida = UnidadMedida::factory()->create([
            'nombre_unidad_medida' => 'Kilogramo',
            'abreviatura_unidad_medida' => 'kg',
        ]);
        $articulo = Articulo::factory()->for($unidadMedida, 'unidadMedida')->create(['nombre_articulo' => 'Tomate']);
        PresentacionArticulo::factory()->for($articulo, 'articulo')->create([
            'nombre_presentacion_articulo' => 'Caja completa',
            'equivalencia_base_presentacion_articulo' => 20,
        ]);

        $this->actingAs($administrador)
            ->get(route('panel.presentaciones-articulos.index'))
            ->assertOk()
            ->assertSee('Caja completa')
            ->assertSee('Tomate')
            ->assertSee('kg');
    }

    public function test_administrador_crea_presentacion_normalizada_con_equivalencia_y_auditoria(): void
    {
        $administrador = $this->crearAdministrador();
        $articulo = Articulo::factory()->create(['nombre_articulo' => 'Tomate']);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.articulo_id', $articulo->id)
            ->set('form.nombre_presentacion_articulo', '  Caja   completa  ')
            ->set('form.uso_presentacion_articulo', UsoPresentacionArticulo::Ambos->value)
            ->set('form.tipo_equivalencia_presentacion_articulo', TipoEquivalenciaPresentacionArticulo::Fija->value)
            ->set('form.equivalencia_base_presentacion_articulo', '20.500')
            ->set('form.permite_fraccion_presentacion_articulo', true)
            ->set('form.predeterminada_pedido_presentacion_articulo', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched(
                'toast',
                title: 'Presentación registrada',
                message: 'La presentación Caja completa fue registrada correctamente.',
                type: 'success',
            );

        $presentacionArticulo = PresentacionArticulo::query()->where('nombre_presentacion_articulo', 'Caja completa')->first();

        $this->assertNotNull($presentacionArticulo);
        $this->assertSame($articulo->id, $presentacionArticulo->articulo_id);
        $this->assertSame(UsoPresentacionArticulo::Ambos, $presentacionArticulo->uso_presentacion_articulo);
        $this->assertSame(TipoEquivalenciaPresentacionArticulo::Fija, $presentacionArticulo->tipo_equivalencia_presentacion_articulo);
        $this->assertSame('20.500', $presentacionArticulo->equivalencia_base_presentacion_articulo);
        $this->assertTrue($presentacionArticulo->permite_fraccion_presentacion_articulo);
        $this->assertTrue($presentacionArticulo->predeterminada_pedido_presentacion_articulo);
        $this->assertTrue($presentacionArticulo->estado_presentacion_articulo);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::ArticlePresentations->value,
            'event' => 'created',
            'subject_type' => PresentacionArticulo::class,
            'subject_id' => $presentacionArticulo->id,
            'causer_id' => $administrador->id,
        ]);
    }

    public function test_presentacion_requiere_articulo_y_nombre(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->call('save')
            ->assertHasErrors([
                'form.articulo_id' => ['required'],
                'form.nombre_presentacion_articulo' => ['required'],
            ]);

        $this->assertSame(0, PresentacionArticulo::query()->count());
    }

    public function test_equivalencias_fija_y_aproximada_requieren_un_valor_base(): void
    {
        $administrador = $this->crearAdministrador();
        $articulo = Articulo::factory()->create();

        foreach ([TipoEquivalenciaPresentacionArticulo::Fija, TipoEquivalenciaPresentacionArticulo::Aproximada] as $tipoEquivalencia) {
            Livewire::actingAs($administrador)
                ->test(Index::class)
                ->call('openCreateModal')
                ->set('form.articulo_id', $articulo->id)
                ->set('form.nombre_presentacion_articulo', 'Caja '.$tipoEquivalencia->label())
                ->set('form.tipo_equivalencia_presentacion_articulo', $tipoEquivalencia->value)
                ->set('form.equivalencia_base_presentacion_articulo', null)
                ->call('save')
                ->assertHasErrors(['form.equivalencia_base_presentacion_articulo' => ['required']]);
        }

        $this->assertSame(0, PresentacionArticulo::query()->count());
    }

    public function test_presentacion_variable_no_guarda_equivalencia_ni_predeterminada_incompatible(): void
    {
        $administrador = $this->crearAdministrador();
        $articulo = Articulo::factory()->create();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.articulo_id', $articulo->id)
            ->set('form.nombre_presentacion_articulo', 'Caja variable')
            ->set('form.uso_presentacion_articulo', UsoPresentacionArticulo::Pedido->value)
            ->set('form.tipo_equivalencia_presentacion_articulo', TipoEquivalenciaPresentacionArticulo::Variable->value)
            ->set('form.equivalencia_base_presentacion_articulo', '25.000')
            ->set('form.predeterminada_compra_presentacion_articulo', true)
            ->call('save')
            ->assertHasNoErrors();

        $presentacionArticulo = PresentacionArticulo::query()->sole();

        $this->assertNull($presentacionArticulo->equivalencia_base_presentacion_articulo);
        $this->assertFalse($presentacionArticulo->predeterminada_compra_presentacion_articulo);
    }

    public function test_nueva_predeterminada_reemplaza_la_anterior_del_mismo_articulo(): void
    {
        $administrador = $this->crearAdministrador();
        $articulo = Articulo::factory()->create();
        $anterior = PresentacionArticulo::factory()->for($articulo, 'articulo')->create([
            'nombre_presentacion_articulo' => 'Caja anterior',
            'predeterminada_pedido_presentacion_articulo' => true,
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.articulo_id', $articulo->id)
            ->set('form.nombre_presentacion_articulo', 'Caja nueva')
            ->set('form.uso_presentacion_articulo', UsoPresentacionArticulo::Pedido->value)
            ->set('form.predeterminada_pedido_presentacion_articulo', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($anterior->fresh()->predeterminada_pedido_presentacion_articulo);
        $this->assertTrue(PresentacionArticulo::query()
            ->where('nombre_presentacion_articulo', 'Caja nueva')
            ->sole()
            ->predeterminada_pedido_presentacion_articulo);
        $this->assertSame(1, PresentacionArticulo::query()
            ->whereBelongsTo($articulo)
            ->where('predeterminada_pedido_presentacion_articulo', true)
            ->count());
    }

    public function test_presentacion_rechaza_articulo_inactivo_y_equivalencia_invalida(): void
    {
        $administrador = $this->crearAdministrador();
        $articulo = Articulo::factory()->create(['estado_articulo' => false]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.articulo_id', $articulo->id)
            ->set('form.nombre_presentacion_articulo', 'Caja completa')
            ->set('form.tipo_equivalencia_presentacion_articulo', TipoEquivalenciaPresentacionArticulo::Fija->value)
            ->set('form.equivalencia_base_presentacion_articulo', '0')
            ->call('save')
            ->assertHasErrors([
                'form.articulo_id' => ['exists'],
                'form.equivalencia_base_presentacion_articulo' => ['between'],
            ]);

        $this->assertSame(0, PresentacionArticulo::query()->count());
    }

    public function test_nombre_es_unico_por_articulo_pero_puede_repetirse_en_otro(): void
    {
        $administrador = $this->crearAdministrador();
        $tomate = Articulo::factory()->create(['nombre_articulo' => 'Tomate']);
        $papa = Articulo::factory()->create(['nombre_articulo' => 'Papa']);
        PresentacionArticulo::factory()->for($tomate, 'articulo')->create(['nombre_presentacion_articulo' => 'Caja']);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.articulo_id', $tomate->id)
            ->set('form.nombre_presentacion_articulo', 'Caja')
            ->call('save')
            ->assertHasErrors(['form.nombre_presentacion_articulo' => ['unique']])
            ->set('form.articulo_id', $papa->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, PresentacionArticulo::query()->where('nombre_presentacion_articulo', 'Caja')->count());
    }

    public function test_administrador_actualiza_presentacion_y_conserva_articulo_inactivo_actual(): void
    {
        $administrador = $this->crearAdministrador();
        $articulo = Articulo::factory()->create(['estado_articulo' => false]);
        $presentacionArticulo = PresentacionArticulo::factory()->for($articulo, 'articulo')->create([
            'nombre_presentacion_articulo' => 'Media caja',
            'equivalencia_base_presentacion_articulo' => 10,
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openEditModal', $presentacionArticulo->id)
            ->set('form.nombre_presentacion_articulo', 'Caja pequeña')
            ->set('form.tipo_equivalencia_presentacion_articulo', TipoEquivalenciaPresentacionArticulo::Variable->value)
            ->set('form.equivalencia_base_presentacion_articulo', null)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Presentación actualizada', type: 'info');

        $presentacionArticulo->refresh();
        $this->assertSame('Caja pequeña', $presentacionArticulo->nombre_presentacion_articulo);
        $this->assertSame($articulo->id, $presentacionArticulo->articulo_id);
        $this->assertNull($presentacionArticulo->equivalencia_base_presentacion_articulo);
    }

    public function test_administrador_desactiva_y_reactiva_presentacion_sin_eliminarla(): void
    {
        $administrador = $this->crearAdministrador();
        $presentacionArticulo = PresentacionArticulo::factory()->create([
            'predeterminada_pedido_presentacion_articulo' => true,
            'predeterminada_compra_presentacion_articulo' => true,
            'estado_presentacion_articulo' => true,
        ]);
        $componente = Livewire::actingAs($administrador)->test(Index::class);

        $componente
            ->call('openStatusModal', $presentacionArticulo->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Presentación desactivada', type: 'warning');

        $this->assertFalse($presentacionArticulo->fresh()->estado_presentacion_articulo);
        $this->assertFalse($presentacionArticulo->fresh()->predeterminada_pedido_presentacion_articulo);
        $this->assertFalse($presentacionArticulo->fresh()->predeterminada_compra_presentacion_articulo);
        $this->assertModelExists($presentacionArticulo);

        $componente
            ->call('openStatusModal', $presentacionArticulo->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Presentación activada', type: 'success');

        $this->assertTrue($presentacionArticulo->fresh()->estado_presentacion_articulo);
        $this->assertFalse($presentacionArticulo->fresh()->predeterminada_pedido_presentacion_articulo);
        $this->assertFalse($presentacionArticulo->fresh()->predeterminada_compra_presentacion_articulo);
        $this->assertSame(2, Activity::query()
            ->where('log_name', ActivityLogName::ArticlePresentations->value)
            ->where('event', 'status_changed')
            ->where('subject_id', $presentacionArticulo->id)
            ->count());
    }

    public function test_busqueda_y_estado_filtran_presentaciones_por_articulo_y_unidad(): void
    {
        $administrador = $this->crearAdministrador();
        $kilogramo = UnidadMedida::factory()->create(['nombre_unidad_medida' => 'Kilogramo', 'abreviatura_unidad_medida' => 'kg']);
        $unidad = UnidadMedida::factory()->create(['nombre_unidad_medida' => 'Unidad', 'abreviatura_unidad_medida' => 'und']);
        $tomate = Articulo::factory()->for($kilogramo, 'unidadMedida')->create(['nombre_articulo' => 'Tomate']);
        $bandeja = Articulo::factory()->for($unidad, 'unidadMedida')->create(['nombre_articulo' => 'Bandeja']);
        PresentacionArticulo::factory()->for($tomate, 'articulo')->create(['nombre_presentacion_articulo' => 'Caja', 'estado_presentacion_articulo' => false]);
        PresentacionArticulo::factory()->for($tomate, 'articulo')->create(['nombre_presentacion_articulo' => 'Bolsa', 'estado_presentacion_articulo' => true]);
        PresentacionArticulo::factory()->for($bandeja, 'articulo')->create(['nombre_presentacion_articulo' => 'Paquete', 'estado_presentacion_articulo' => false]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->set('search', 'kg')
            ->set('status', 'inactive')
            ->assertSee('Caja')
            ->assertDontSee('Bolsa')
            ->assertDontSee('Paquete');
    }

    public function test_usuario_de_solo_lectura_no_ve_acciones_de_presentaciones(): void
    {
        $this->crearAdministrador();
        $usuarioConsulta = User::factory()->create();
        $presentacionArticulo = PresentacionArticulo::factory()->create();
        $rol = Role::create(['name' => 'Consulta de presentaciones', 'guard_name' => 'web']);
        $rol->givePermissionTo(PermissionName::ArticlePresentationsView->value);
        $usuarioConsulta->assignRole($rol);

        Livewire::actingAs($usuarioConsulta)
            ->test(Index::class)
            ->assertDontSee('wire:click="openCreateModal"', false)
            ->assertDontSee('wire:click="openEditModal('.$presentacionArticulo->id.')"', false)
            ->assertDontSee('wire:click="openStatusModal('.$presentacionArticulo->id.')"', false);
    }

    public function test_contenido_de_presentacion_se_muestra_escapado(): void
    {
        $administrador = $this->crearAdministrador();
        PresentacionArticulo::factory()->create([
            'nombre_presentacion_articulo' => '<script>alert("riesgo")</script>',
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("riesgo")</script>', false);
    }

    private function crearAdministrador(): User
    {
        $administrador = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrador->refresh();
    }
}
