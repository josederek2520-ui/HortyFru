<?php

namespace Tests\Feature;

use App\Enums\ActivityLogName;
use App\Enums\PermissionName;
use App\Livewire\Panel\Articulos\Index;
use App\Models\Activity;
use App\Models\Articulo;
use App\Models\CategoriaArticulo;
use App\Models\UnidadMedida;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GestionArticulosTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_usuario_no_autenticado_es_redirigido_desde_gestion_de_articulos(): void
    {
        $this->get(route('panel.articulos.index'))->assertRedirect(route('login'));
    }

    public function test_usuario_sin_permiso_no_puede_ver_gestion_de_articulos(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get(route('panel.articulos.index'))->assertForbidden();
        Livewire::actingAs($usuario)->test(Index::class)->assertForbidden();
    }

    public function test_administrador_puede_ver_articulos_con_categoria_y_unidad(): void
    {
        $administrador = $this->crearAdministrador();
        $categoria = CategoriaArticulo::factory()->create(['nombre_categoria_articulo' => 'Verduras']);
        $unidad = UnidadMedida::factory()->create(['nombre_unidad_medida' => 'Kilogramo', 'abreviatura_unidad_medida' => 'kg']);
        Articulo::factory()->for($categoria, 'categoriaArticulo')->for($unidad, 'unidadMedida')->create(['nombre_articulo' => 'Tomate']);

        $this->actingAs($administrador)
            ->get(route('panel.articulos.index'))
            ->assertOk()
            ->assertSee('Tomate')
            ->assertSee('Verduras')
            ->assertSee('kg');
    }

    public function test_administrador_crea_articulo_normalizado_con_relaciones_y_auditoria(): void
    {
        $administrador = $this->crearAdministrador();
        $categoria = CategoriaArticulo::factory()->create(['nombre_categoria_articulo' => 'Productos procesados']);
        $unidad = UnidadMedida::factory()->create(['nombre_unidad_medida' => 'Unidad', 'abreviatura_unidad_medida' => 'und']);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nombre_articulo', '  Mix de   verduras 500 g  ')
            ->set('form.categoria_articulo_id', $categoria->id)
            ->set('form.unidad_medida_id', $unidad->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched(
                'toast',
                title: 'Artículo registrado',
                message: 'El artículo Mix de verduras 500 g fue registrado correctamente.',
                type: 'success',
            );

        $articulo = Articulo::query()->where('nombre_articulo', 'Mix de verduras 500 g')->first();

        $this->assertNotNull($articulo);
        $this->assertSame($categoria->id, $articulo->categoria_articulo_id);
        $this->assertSame($unidad->id, $articulo->unidad_medida_id);
        $this->assertTrue($articulo->estado_articulo);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLogName::Articles->value,
            'event' => 'created',
            'subject_type' => Articulo::class,
            'subject_id' => $articulo->id,
            'causer_id' => $administrador->id,
        ]);
    }

    public function test_articulo_requiere_nombre_categoria_y_unidad(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->call('save')
            ->assertHasErrors([
                'form.nombre_articulo' => ['required'],
                'form.categoria_articulo_id' => ['required'],
                'form.unidad_medida_id' => ['required'],
            ]);

        $this->assertSame(0, Articulo::query()->count());
    }

    public function test_administrador_carga_imagen_con_nombre_seguro_generado(): void
    {
        Storage::fake('public');
        $administrador = $this->crearAdministrador();
        $categoria = CategoriaArticulo::factory()->create();
        $unidad = UnidadMedida::factory()->create();
        $imagen = UploadedFile::fake()->image('tomate original.jpg', 800, 800)->size(500);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nombre_articulo', 'Tomate')
            ->set('form.categoria_articulo_id', $categoria->id)
            ->set('form.unidad_medida_id', $unidad->id)
            ->set('form.imagen_articulo', $imagen)
            ->call('save')
            ->assertHasNoErrors();

        $articulo = Articulo::query()->where('nombre_articulo', 'Tomate')->firstOrFail();

        $this->assertNotNull($articulo->imagen_articulo);
        $this->assertStringStartsWith('articulos/', $articulo->imagen_articulo);
        $this->assertStringNotContainsString('tomate original', $articulo->imagen_articulo);
        Storage::disk('public')->assertExists($articulo->imagen_articulo);
    }

    public function test_articulo_rechaza_archivo_que_no_es_imagen(): void
    {
        Storage::fake('public');
        $administrador = $this->crearAdministrador();
        $categoria = CategoriaArticulo::factory()->create();
        $unidad = UnidadMedida::factory()->create();
        $archivo = UploadedFile::fake()->create('documento.pdf', 200, 'application/pdf');

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nombre_articulo', 'Tomate')
            ->set('form.categoria_articulo_id', $categoria->id)
            ->set('form.unidad_medida_id', $unidad->id)
            ->set('form.imagen_articulo', $archivo)
            ->call('save')
            ->assertHasErrors(['form.imagen_articulo' => ['image']]);

        $this->assertSame(0, Articulo::query()->count());
        Storage::disk('public')->assertDirectoryEmpty('articulos');
    }

    public function test_articulo_rechaza_imagen_mayor_a_tres_megabytes(): void
    {
        Storage::fake('public');
        $administrador = $this->crearAdministrador();
        $categoria = CategoriaArticulo::factory()->create();
        $unidad = UnidadMedida::factory()->create();
        $imagen = UploadedFile::fake()->image('pesada.jpg', 800, 800)->size(3073);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nombre_articulo', 'Tomate')
            ->set('form.categoria_articulo_id', $categoria->id)
            ->set('form.unidad_medida_id', $unidad->id)
            ->set('form.imagen_articulo', $imagen)
            ->call('save')
            ->assertHasErrors(['form.imagen_articulo' => ['max']]);

        $this->assertSame(0, Articulo::query()->count());
    }

    public function test_articulo_nuevo_rechaza_categoria_y_unidad_inactivas(): void
    {
        $administrador = $this->crearAdministrador();
        $categoria = CategoriaArticulo::factory()->create(['estado_categoria_articulo' => false]);
        $unidad = UnidadMedida::factory()->create(['estado_unidad_medida' => false]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nombre_articulo', 'Artículo inválido')
            ->set('form.categoria_articulo_id', $categoria->id)
            ->set('form.unidad_medida_id', $unidad->id)
            ->call('save')
            ->assertHasErrors([
                'form.categoria_articulo_id' => ['exists'],
                'form.unidad_medida_id' => ['exists'],
            ]);

        $this->assertSame(0, Articulo::query()->count());
    }

    public function test_nombre_del_articulo_debe_ser_unico(): void
    {
        $administrador = $this->crearAdministrador();
        $categoria = CategoriaArticulo::factory()->create();
        $unidad = UnidadMedida::factory()->create();
        Articulo::factory()->for($categoria, 'categoriaArticulo')->for($unidad, 'unidadMedida')->create(['nombre_articulo' => 'Papa']);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('form.nombre_articulo', 'Papa')
            ->set('form.categoria_articulo_id', $categoria->id)
            ->set('form.unidad_medida_id', $unidad->id)
            ->call('save')
            ->assertHasErrors(['form.nombre_articulo' => ['unique']]);

        $this->assertSame(1, Articulo::query()->count());
    }

    public function test_administrador_actualiza_articulo_y_sus_relaciones(): void
    {
        $administrador = $this->crearAdministrador();
        $categoriaInicial = CategoriaArticulo::factory()->create(['nombre_categoria_articulo' => 'Insumos']);
        $categoriaNueva = CategoriaArticulo::factory()->create(['nombre_categoria_articulo' => 'Envases']);
        $unidadInicial = UnidadMedida::factory()->create(['nombre_unidad_medida' => 'Metro', 'abreviatura_unidad_medida' => 'm']);
        $unidadNueva = UnidadMedida::factory()->create(['nombre_unidad_medida' => 'Unidad', 'abreviatura_unidad_medida' => 'und']);
        $articulo = Articulo::factory()->for($categoriaInicial, 'categoriaArticulo')->for($unidadInicial, 'unidadMedida')->create(['nombre_articulo' => 'Film plástico']);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openEditModal', $articulo->id)
            ->set('form.nombre_articulo', 'Bandeja plástica')
            ->set('form.categoria_articulo_id', $categoriaNueva->id)
            ->set('form.unidad_medida_id', $unidadNueva->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', title: 'Artículo actualizado', type: 'info');

        $articulo->refresh();
        $this->assertSame('Bandeja plástica', $articulo->nombre_articulo);
        $this->assertSame($categoriaNueva->id, $articulo->categoria_articulo_id);
        $this->assertSame($unidadNueva->id, $articulo->unidad_medida_id);
    }

    public function test_reemplazar_imagen_elimina_el_archivo_anterior(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('articulos/anterior.jpg', 'imagen anterior');
        $administrador = $this->crearAdministrador();
        $articulo = Articulo::factory()->create(['imagen_articulo' => 'articulos/anterior.jpg']);
        $imagenNueva = UploadedFile::fake()->image('nueva.png', 900, 900)->size(600);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openEditModal', $articulo->id)
            ->set('form.imagen_articulo', $imagenNueva)
            ->call('save')
            ->assertHasNoErrors();

        $articulo->refresh();
        $this->assertNotNull($articulo->imagen_articulo);
        $this->assertNotSame('articulos/anterior.jpg', $articulo->imagen_articulo);
        Storage::disk('public')->assertMissing('articulos/anterior.jpg');
        Storage::disk('public')->assertExists($articulo->imagen_articulo);
    }

    public function test_quitar_imagen_elimina_el_archivo_y_limpia_la_ruta(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('articulos/tomate.jpg', 'imagen');
        $administrador = $this->crearAdministrador();
        $articulo = Articulo::factory()->create(['imagen_articulo' => 'articulos/tomate.jpg']);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openEditModal', $articulo->id)
            ->call('removeImage')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($articulo->fresh()->imagen_articulo);
        Storage::disk('public')->assertMissing('articulos/tomate.jpg');
    }

    public function test_articulo_con_relaciones_inactivas_puede_conservarlas_al_editarse(): void
    {
        $administrador = $this->crearAdministrador();
        $categoria = CategoriaArticulo::factory()->create(['estado_categoria_articulo' => false]);
        $unidad = UnidadMedida::factory()->create(['estado_unidad_medida' => false]);
        $articulo = Articulo::factory()->for($categoria, 'categoriaArticulo')->for($unidad, 'unidadMedida')->create(['nombre_articulo' => 'Artículo histórico']);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->call('openEditModal', $articulo->id)
            ->set('form.nombre_articulo', 'Artículo histórico actualizado')
            ->call('save')
            ->assertHasNoErrors();

        $articulo->refresh();
        $this->assertSame($categoria->id, $articulo->categoria_articulo_id);
        $this->assertSame($unidad->id, $articulo->unidad_medida_id);
    }

    public function test_administrador_desactiva_y_reactiva_articulo_sin_eliminarlo(): void
    {
        $administrador = $this->crearAdministrador();
        $articulo = Articulo::factory()->create(['estado_articulo' => true]);
        $componente = Livewire::actingAs($administrador)->test(Index::class);

        $componente
            ->call('openStatusModal', $articulo->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Artículo desactivado', type: 'warning');

        $this->assertFalse($articulo->fresh()->estado_articulo);
        $this->assertModelExists($articulo);

        $componente
            ->call('openStatusModal', $articulo->id)
            ->call('changeStatus')
            ->assertDispatched('toast', title: 'Artículo activado', type: 'success');

        $this->assertTrue($articulo->fresh()->estado_articulo);
        $this->assertSame(2, Activity::query()
            ->where('log_name', ActivityLogName::Articles->value)
            ->where('event', 'status_changed')
            ->where('subject_id', $articulo->id)
            ->count());
    }

    public function test_busqueda_y_estado_filtran_articulos_por_sus_relaciones(): void
    {
        $administrador = $this->crearAdministrador();
        $verduras = CategoriaArticulo::factory()->create(['nombre_categoria_articulo' => 'Verduras']);
        $frutas = CategoriaArticulo::factory()->create(['nombre_categoria_articulo' => 'Frutas']);
        $kilogramo = UnidadMedida::factory()->create(['nombre_unidad_medida' => 'Kilogramo', 'abreviatura_unidad_medida' => 'kg']);
        Articulo::factory()->for($verduras, 'categoriaArticulo')->for($kilogramo, 'unidadMedida')->create(['nombre_articulo' => 'Tomate', 'estado_articulo' => false]);
        Articulo::factory()->for($verduras, 'categoriaArticulo')->for($kilogramo, 'unidadMedida')->create(['nombre_articulo' => 'Papa', 'estado_articulo' => true]);
        Articulo::factory()->for($frutas, 'categoriaArticulo')->for($kilogramo, 'unidadMedida')->create(['nombre_articulo' => 'Manzana', 'estado_articulo' => false]);

        Livewire::actingAs($administrador)
            ->test(Index::class)
            ->set('search', 'Verduras')
            ->set('status', 'inactive')
            ->assertSee('Tomate')
            ->assertDontSee('Papa')
            ->assertDontSee('Manzana');
    }

    public function test_usuario_de_solo_lectura_no_ve_acciones_de_articulos(): void
    {
        $this->crearAdministrador();
        $usuarioConsulta = User::factory()->create();
        $articulo = Articulo::factory()->create();
        $rol = Role::create(['name' => 'Consulta de artículos', 'guard_name' => 'web']);
        $rol->givePermissionTo(PermissionName::ArticlesView->value);
        $usuarioConsulta->assignRole($rol);

        Livewire::actingAs($usuarioConsulta)
            ->test(Index::class)
            ->assertDontSee('wire:click="openCreateModal"', false)
            ->assertDontSee('wire:click="openEditModal('.$articulo->id.')"', false)
            ->assertDontSee('wire:click="openStatusModal('.$articulo->id.')"', false);
    }

    public function test_contenido_del_articulo_se_muestra_escapado(): void
    {
        $administrador = $this->crearAdministrador();
        Articulo::factory()->create(['nombre_articulo' => '<script>alert("riesgo")</script>']);

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
