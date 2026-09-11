<?php

namespace App\Helpers;

use App\Models\User;

final class MenuHelper
{
    /** @return list<array{title: string, items: list<array<string, mixed>>}> */
    public static function getMenuGroups(): array
    {
        return [
            [
                'title' => 'Inicio',
                'items' => [
                    ['icon' => 'dashboard', 'name' => 'Dashboard', 'path' => '/panel'],
                ],
            ],
            [
                'title' => 'Operaciones',
                'items' => [
                    ['icon' => 'orders', 'name' => 'Pedidos', 'path' => '/panel/pedidos', 'permission' => 'pedidos.ver'],
                    ['icon' => 'purchases', 'name' => 'Compras', 'path' => '/panel/compras', 'permission' => 'compras.ver'],
                    ['icon' => 'receptions', 'name' => 'Recepciones', 'path' => '/panel/recepciones', 'permission' => 'recepciones.ver'],
                    ['icon' => 'preparation', 'name' => 'Preparación de pedidos', 'path' => '/panel/preparacion-pedidos', 'permission' => 'pedidos.preparar'],
                ],
            ],
            [
                'title' => 'Inventario y catálogo',
                'items' => [
                    ['icon' => 'warehouses', 'name' => 'Almacenes', 'path' => '/panel/almacenes', 'permission' => 'almacenes.ver'],
                    ['icon' => 'lots', 'name' => 'Lotes', 'path' => '/panel/lotes', 'permission' => 'lotes.ver'],
                    ['icon' => 'inventory-movements', 'name' => 'Movimientos de inventario', 'path' => '/panel/movimientos-inventario', 'permission' => 'movimientos_inventario.ver'],
                    ['icon' => 'article-categories', 'name' => 'Categorías de productos', 'path' => '/panel/categorias-articulos', 'permission' => 'categorias_articulos.ver'],
                    ['icon' => 'measurement-units', 'name' => 'Unidades de medida', 'path' => '/panel/unidades-medida', 'permission' => 'unidades_medida.ver'],
                    ['icon' => 'articles', 'name' => 'Productos', 'path' => '/panel/articulos', 'permission' => 'articulos.ver'],
                    ['icon' => 'article-presentations', 'name' => 'Presentaciones de productos', 'path' => '/panel/presentaciones-articulos', 'permission' => 'presentaciones_articulos.ver'],
                ],
            ],
            [
                'title' => 'Comercial',
                'items' => [
                    ['icon' => 'clients', 'name' => 'Clientes', 'path' => '/panel/clients', 'permission' => 'clientes.ver'],
                    ['icon' => 'branches', 'name' => 'Sucursales', 'path' => '/panel/branches', 'permission' => 'sucursales.ver'],
                    ['icon' => 'providers', 'name' => 'Proveedores', 'path' => '/panel/proveedores', 'permission' => 'proveedores.ver'],
                ],
            ],
            [
                'title' => 'Logística',
                'items' => [
                    ['icon' => 'vehicles', 'name' => 'Vehículos', 'path' => '/panel/vehiculos', 'permission' => 'vehiculos.ver'],
                ],
            ],
            [
                'title' => 'Administración',
                'items' => [
                    ['icon' => 'users', 'name' => 'Usuarios', 'path' => '/panel/users', 'permission' => 'usuarios.ver'],
                    ['icon' => 'employee', 'name' => 'Empleados', 'path' => '/panel/employees', 'permission' => 'empleados.ver'],
                    ['icon' => 'shield', 'name' => 'Roles y permisos', 'path' => '/panel/roles', 'permission' => 'roles.ver'],
                    ['icon' => 'activity', 'name' => 'Registro de actividad', 'path' => '/panel/activity', 'permission' => 'actividad.ver'],
                ],
            ],
        ];
    }

    /** @return list<array{title: string, items: list<array<string, mixed>>}> */
    public static function getVisibleMenuGroups(User $user): array
    {
        return collect(self::getMenuGroups())
            ->map(fn (array $group): array => [
                ...$group,
                'items' => collect($group['items'])
                    ->filter(fn (array $item): bool => ! isset($item['permission']) || $user->can($item['permission']))
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $group): bool => $group['items'] !== [])
            ->values()
            ->all();
    }

    /** @return list<array{name: string, section: string, path: string}> */
    public static function getQuickAccessItems(User $user): array
    {
        $items = collect(self::getVisibleMenuGroups($user))
            ->flatMap(fn (array $group) => collect($group['items'])->map(fn (array $item): array => [
                'name' => $item['name'],
                'section' => $group['title'],
                'path' => $item['path'],
            ]));

        if ($user->can('pedidos.crear')) {
            $items->push([
                'name' => 'Registrar pedido',
                'section' => 'Acción rápida',
                'path' => '/panel/pedidos/crear',
            ]);
        }

        if ($user->can('compras.crear')) {
            $items->push([
                'name' => 'Registrar compra',
                'section' => 'Acción rápida',
                'path' => '/panel/compras',
            ]);
        }

        if ($user->can('recepciones.crear')) {
            $items->push([
                'name' => 'Registrar recepción',
                'section' => 'Acción rápida',
                'path' => '/panel/recepciones',
            ]);
        }

        return $items->values()->all();
    }

    public static function getIconSvg(string $iconName): string
    {
        $paths = [
            'dashboard' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z',
            'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
            'employee' => 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM3 21v-2a6 6 0 0 1 12 0v2M17 8h4M19 6v4',
            'clients' => 'M4 7h16v13H4zM7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2M4 12h16M9 15h6',
            'branches' => 'M4 21V7l8-4 8 4v14M8 10h2m4 0h2M8 14h2m4 0h2M9 21v-4h6v4',
            'providers' => 'M3 7h18v13H3zM7 7V4h10v3M3 12h18M8 16h3m4 0h2',
            'vehicles' => 'M5 16h14l-1.5-5h-11L5 16ZM7 11l1.5-4h7L17 11M7 16v2m10-2v2M4 16h16',
            'warehouses' => 'M3 9l9-5 9 5v11H3V9Zm4 3h10M7 16h10M9 12v8m6-8v8',
            'lots' => 'M4 7h16v13H4V7Zm3 0V4h10v3M8 11h8M8 15h5',
            'inventory-movements' => 'M4 7h16v13H4V7Zm3 0V4h10v3M8 12h8M12 9l3 3-3 3M8 17h8',
            'article-categories' => 'M4 5h16v14H4zM8 9h8M8 13h8M8 17h5',
            'measurement-units' => 'M4 7h16v10H4zM8 7v4m4-4v2m4-2v4',
            'articles' => 'M5 4h14v16H5zM8 8h8M8 12h8M8 16h5',
            'article-presentations' => 'M4 7h16v10H4zM7 4h10v3M8 11h8M8 14h5',
            'orders' => 'M6 3h12v18H6zM9 7h6M9 11h6M9 15h3M4 6h2M4 10h2M4 14h2',
            'purchases' => 'M4 7h16v13H4zM7 7V4h10v3M4 11h16M8 15h3M15 15h1M8 18h8',
            'receptions' => 'M4 8h16v11H4V8Zm4 0V5h8v3M8 13h8M12 10v6',
            'preparation' => 'M4 5h16v14H4zM8 9l1.5 1.5L12 8M8 15l1.5 1.5L12 14M14 9h3M14 15h3',
            'shield' => 'M12 3 20 6v5c0 5-3.4 8.6-8 10-4.6-1.4-8-5-8-10V6l8-3zM9 12l2 2 4-4',
            'activity' => 'M4 5h16M4 12h16M4 19h10M7 3v4M12 10v4M17 17v4',
        ];

        $path = $paths[$iconName] ?? $paths['dashboard'];

        return sprintf(
            '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="%s" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>',
            $path,
        );
    }
}
