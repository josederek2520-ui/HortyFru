<?php

namespace Tests\Unit\Helpers;

use App\Helpers\MenuHelper;
use Tests\TestCase;

class MenuHelperTest extends TestCase
{
    public function test_menu_agrupa_las_opciones_segun_el_flujo_del_negocio(): void
    {
        $groups = collect(MenuHelper::getMenuGroups())->keyBy('title');

        $this->assertSame(
            ['Pedidos', 'Preparación de pedidos'],
            collect($groups->get('Operaciones')['items'])->pluck('name')->all(),
        );
        $this->assertSame(
            ['Almacenes', 'Categorías de artículos', 'Unidades de medida', 'Artículos', 'Presentaciones de artículos'],
            collect($groups->get('Inventario y catálogo')['items'])->pluck('name')->all(),
        );
        $this->assertSame(
            ['Clientes', 'Sucursales', 'Proveedores'],
            collect($groups->get('Comercial')['items'])->pluck('name')->all(),
        );
        $this->assertSame(
            ['Vehículos'],
            collect($groups->get('Logística')['items'])->pluck('name')->all(),
        );
    }
}
