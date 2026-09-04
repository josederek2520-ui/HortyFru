<?php

namespace Tests\Feature;

use App\Enums\ActivityLogName;
use App\Enums\PermissionName;
use App\Livewire\Panel\CategoriasArticulos\Index;
use App\Models\Activity;
use App\Models\CategoriaArticulo;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GestionCategoriasArticulosTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_usuario_no_autenticado_es_redirigido_desde_gestion_de_categorias(): void
    {
        $this->get(route('panel.categorias-articulos.index'))->assertRedirect(route('login'));
    }

    public function test_usuario_sin_permiso_no_puede_ver_gestion_de_categorias(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get(route('panel.categorias-articulos.index'))->assertForbidden();
        Livewire::actingAs($usuario)->test(Index::class)->assertForbidden();
    }

    public function test_administrador_puede_ver_categorias(): void
    {
        $administrador = $this->crearAdministrador();
        CategoriaArticulo::factory()->create(['nombre_categoria_articulo' => 'Frutas']);

        $this->actingAs($administrador)
            ->get(route('panel.categorias-articulos.index'))
            ->assertOk()
            ->assertSee('Frutas');
    }

    public function test_administrador_crea_categoria_con_nombre_normalizado_y_auditoria(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nombre_categoria_articulo', '  Productos   procesados  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched(
                'toast',
                title: 'Categoría registrada',
                message: 'La categoría Productos procesados fue registrada correctamente.',
                type: 'success',
            );

        $categoriaArticulo = CategoriaArticulo::query()
            ->where('nombre_categoria_articulo', 'Productos procesados')
            ->first();

        $this->assertNotNull($categoriaArticulo);
        $this->assertTrue($categoriaArticulo->estado_categoria_articulo);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::ArticleCategories->value,
            'event' => 'created',
            'subject_type' => CategoriaArticulo::class,
            'subject_id' => $categoriaArticulo->id,
            'causer_id' => $administrador->id,
        ]);
    }

    public function test_categoria_requiere_nombre_y_rechaza_contenido_invalido(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nombre_categoria_articulo', '<script>')
            ->call('save')
            ->assertHasErrors(['form.nombre_categoria_articulo' => ['not_regex']]);

        $this->assertSame(0, CategoriaArticulo::query()->count());
    }

    public function test_nombre_de_categoria_debe_ser_unico(): void
    {
        $administrador = $this->crearAdministrador();
        CategoriaArticulo::factory()->create(['nombre_categoria_articulo' => 'Verduras']);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nombre_categoria_articulo', 'Verduras')
            ->call('save')
            ->assertHasErrors(['form.nombre_categoria_articulo' => ['unique']]);

        $this->assertSame(1, CategoriaArticulo::query()->count());
    }

    public function test_administrador_actualiza_categoria_sin_conflicto_con_su_nombre(): void
    {
        $administrador = $this->crearAdministrador();
        $categoriaArticulo = CategoriaArticulo::factory()->create([
            'nombre_categoria_articulo' => 'Materiales',
        ]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openEditModal', $categoriaArticulo->id)
            ->set('form.nombre_categoria_articulo', 'Material de empaque')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Categoría actualizada', type: 'info');

        $this->assertSame('Material de empaque', $categoriaArticulo->fresh()->nombre_categoria_articulo);
    }

    public function test_administrador_desactiva_y_reactiva_categoria_sin_eliminarla(): void
    {
        $administrador = $this->crearAdministrador();
        $categoriaArticulo = CategoriaArticulo::factory()->create(['estado_categoria_articulo' => true]);
        $componente = Livewire::actingAs($administrador)->test(Index::class);

        $componente
            ->call('openStatusModal', $categoriaArticulo->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Categoría desactivada', type: 'warning');

        $this->assertFalse($categoriaArticulo->fresh()->estado_categoria_articulo);
        $this->assertModelExists($categoriaArticulo);

        $componente
            ->call('openStatusModal', $categoriaArticulo->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Categoría activada', type: 'success');

        $this->assertTrue($categoriaArticulo->fresh()->estado_categoria_articulo);
        $this->assertSame(2, Activity::query()
            ->where('log_name', ActivityLogName::ArticleCategories->value)
            ->where('event', 'status_changed')
            ->where('subject_id', $categoriaArticulo->id)
            ->count());
    }

    public function test_busqueda_y_estado_filtran_categorias(): void
    {
        $administrador = $this->crearAdministrador();
        CategoriaArticulo::factory()->create(['nombre_categoria_articulo' => 'Frutas frescas', 'estado_categoria_articulo' => false]);
        CategoriaArticulo::factory()->create(['nombre_categoria_articulo' => 'Frutas congeladas', 'estado_categoria_articulo' => true]);
        CategoriaArticulo::factory()->create(['nombre_categoria_articulo' => 'Envases', 'estado_categoria_articulo' => false]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->set('search', 'Frutas')
            ->set('status', 'inactive')
            ->assertSee('Frutas frescas')
            ->assertDontSee('Frutas congeladas')
            ->assertDontSee('Envases');
    }

    public function test_usuario_de_solo_lectura_no_ve_acciones_de_categorias(): void
    {
        $this->crearAdministrador();
        $usuarioConsulta = User::factory()->create();
        $categoriaArticulo = CategoriaArticulo::factory()->create();
        $rol = Role::create(['name' => 'Consulta de categorías', 'guard_name' => 'web']);
        $rol->givePermissionTo(PermissionName::ArticleCategoriesView->value);
        $usuarioConsulta->assignRole($rol);

        Livewire::actingAs($usuarioConsulta)
            ->test(Index::class)
            ->assertDontSee('wire:click="openCreateModal"', false)
            ->assertDontSee('wire:click="openEditModal('.$categoriaArticulo->id.')"', false)
            ->assertDontSee('wire:click="openStatusModal('.$categoriaArticulo->id.')"', false);
    }

    public function test_contenido_de_categoria_se_muestra_escapado(): void
    {
        $administrador = $this->crearAdministrador();
        CategoriaArticulo::factory()->create([
            'nombre_categoria_articulo' => '<script>alert("riesgo")</script>',
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
