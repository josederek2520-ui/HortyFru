<?php

namespace App\Enums;

enum ActivityLogName: string
{
    case Users = 'usuarios';
    case Employees = 'empleados';
    case Clients = 'clientes';
    case Branches = 'sucursales';
    case Providers = 'proveedores';
    case Vehicles = 'vehiculos';
    case Warehouses = 'almacenes';
    case ArticleCategories = 'categorias_articulos';
    case MeasurementUnits = 'unidades_medida';
    case Articles = 'articulos';
    case ArticlePresentations = 'presentaciones_articulos';
    case Orders = 'pedidos';
    case Roles = 'roles';
    case Authentication = 'autenticacion';
    case System = 'sistema';

    public function label(): string
    {
        return match ($this) {
            self::Users => 'Usuarios',
            self::Employees => 'Empleados',
            self::Clients => 'Clientes',
            self::Branches => 'Sucursales',
            self::Providers => 'Proveedores',
            self::Vehicles => 'Vehículos',
            self::Warehouses => 'Almacenes',
            self::ArticleCategories => 'Categorías de artículos',
            self::MeasurementUnits => 'Unidades de medida',
            self::Articles => 'Artículos',
            self::ArticlePresentations => 'Presentaciones de artículos',
            self::Orders => 'Pedidos',
            self::Roles => 'Roles y permisos',
            self::Authentication => 'Autenticación',
            self::System => 'Sistema',
        };
    }
}
