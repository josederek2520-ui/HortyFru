<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Helpers\MenuHelper;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuickAccessSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_buscador_muestra_modulos_y_acciones_autorizadas(): void
    {
        $administrador = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->actingAs($administrador)
            ->get(route('panel.inicio'))
            ->assertSee('Buscar módulo o acción...')
            ->assertSee('Registrar pedido')
            ->assertSee('Preparación de pedidos');
    }

    public function test_buscador_oculta_acciones_sin_permiso(): void
    {
        User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'Consulta de pedidos', 'guard_name' => 'web']);
        $rol->givePermissionTo(PermissionName::OrdersView->value);
        $usuario->assignRole($rol);

        $items = collect(MenuHelper::getQuickAccessItems($usuario))->pluck('name')->all();

        $this->assertSame(['Dashboard', 'Pedidos'], $items);
        $this->actingAs($usuario)
            ->get(route('panel.inicio'))
            ->assertSee('Pedidos')
            ->assertDontSee('Registrar pedido')
            ->assertDontSee('Preparación de pedidos');
    }
}
