<?php

namespace App\Enums;

enum PermissionName: string
{
    case UsersView = 'usuarios.ver';
    case UsersCreate = 'usuarios.crear';
    case UsersUpdate = 'usuarios.editar';
    case UsersChangeStatus = 'usuarios.cambiar_estado';
    case UsersAssignRole = 'usuarios.asignar_rol';
    case RolesView = 'roles.ver';
    case RolesCreate = 'roles.crear';
    case RolesUpdate = 'roles.editar';
    case RolesDelete = 'roles.eliminar';
    case RolesAssignPermissions = 'roles.asignar_permisos';
    case EmployeesView = 'empleados.ver';
    case EmployeesCreate = 'empleados.crear';
    case EmployeesUpdate = 'empleados.editar';
    case EmployeesChangeStatus = 'empleados.cambiar_estado';
    case EmployeesAssignUser = 'empleados.asignar_usuario';
    case ClientsView = 'clientes.ver';
    case ClientsCreate = 'clientes.crear';
    case ClientsUpdate = 'clientes.editar';
    case ClientsChangeStatus = 'clientes.cambiar_estado';
    case BranchesView = 'sucursales.ver';
    case BranchesCreate = 'sucursales.crear';
    case BranchesUpdate = 'sucursales.editar';
    case BranchesChangeStatus = 'sucursales.cambiar_estado';
    case ProvidersView = 'proveedores.ver';
    case ProvidersCreate = 'proveedores.crear';
    case ProvidersUpdate = 'proveedores.editar';
    case ProvidersChangeStatus = 'proveedores.cambiar_estado';
    case VehiclesView = 'vehiculos.ver';
    case VehiclesCreate = 'vehiculos.crear';
    case VehiclesUpdate = 'vehiculos.editar';
    case VehiclesChangeStatus = 'vehiculos.cambiar_estado';
    case WarehousesView = 'almacenes.ver';
    case WarehousesCreate = 'almacenes.crear';
    case WarehousesUpdate = 'almacenes.editar';
    case WarehousesChangeStatus = 'almacenes.cambiar_estado';
    case ArticleCategoriesView = 'categorias_articulos.ver';
    case ArticleCategoriesCreate = 'categorias_articulos.crear';
    case ArticleCategoriesUpdate = 'categorias_articulos.editar';
    case ArticleCategoriesChangeStatus = 'categorias_articulos.cambiar_estado';
    case MeasurementUnitsView = 'unidades_medida.ver';
    case MeasurementUnitsCreate = 'unidades_medida.crear';
    case MeasurementUnitsUpdate = 'unidades_medida.editar';
    case MeasurementUnitsChangeStatus = 'unidades_medida.cambiar_estado';
    case ArticlesView = 'articulos.ver';
    case ArticlesCreate = 'articulos.crear';
    case ArticlesUpdate = 'articulos.editar';
    case ArticlesChangeStatus = 'articulos.cambiar_estado';
    case ArticlePresentationsView = 'presentaciones_articulos.ver';
    case ArticlePresentationsCreate = 'presentaciones_articulos.crear';
    case ArticlePresentationsUpdate = 'presentaciones_articulos.editar';
    case ArticlePresentationsChangeStatus = 'presentaciones_articulos.cambiar_estado';
    case OrdersView = 'pedidos.ver';
    case OrdersCreate = 'pedidos.crear';
    case OrdersUpdate = 'pedidos.editar';
    case OrdersCancel = 'pedidos.cancelar';
    case OrdersPrepare = 'pedidos.preparar';
    case ActivityView = 'actividad.ver';
    case ActivityViewDetails = 'actividad.ver_detalle';
    case ActivityExport = 'actividad.exportar';

    public function label(): string
    {
        return match ($this) {
            self::UsersView => 'Ver usuarios',
            self::UsersCreate => 'Crear usuarios',
            self::UsersUpdate => 'Editar usuarios',
            self::UsersChangeStatus => 'Activar o desactivar usuarios',
            self::UsersAssignRole => 'Asignar roles a usuarios',
            self::RolesView => 'Ver roles y permisos',
            self::RolesCreate => 'Crear roles',
            self::RolesUpdate => 'Editar roles',
            self::RolesDelete => 'Eliminar roles',
            self::RolesAssignPermissions => 'Asignar permisos a roles',
            self::EmployeesView => 'Ver empleados',
            self::EmployeesCreate => 'Crear empleados',
            self::EmployeesUpdate => 'Editar empleados',
            self::EmployeesChangeStatus => 'Activar o retirar empleados',
            self::EmployeesAssignUser => 'Asignar cuentas a empleados',
            self::ClientsView => 'Ver clientes',
            self::ClientsCreate => 'Crear clientes',
            self::ClientsUpdate => 'Editar clientes',
            self::ClientsChangeStatus => 'Activar o desactivar clientes',
            self::BranchesView => 'Ver sucursales',
            self::BranchesCreate => 'Crear sucursales',
            self::BranchesUpdate => 'Editar sucursales',
            self::BranchesChangeStatus => 'Activar o desactivar sucursales',
            self::ProvidersView => 'Ver proveedores',
            self::ProvidersCreate => 'Crear proveedores',
            self::ProvidersUpdate => 'Editar proveedores',
            self::ProvidersChangeStatus => 'Activar o desactivar proveedores',
            self::VehiclesView => 'Ver vehículos',
            self::VehiclesCreate => 'Crear vehículos',
            self::VehiclesUpdate => 'Editar vehículos',
            self::VehiclesChangeStatus => 'Activar o desactivar vehículos',
            self::WarehousesView => 'Ver almacenes',
            self::WarehousesCreate => 'Crear almacenes',
            self::WarehousesUpdate => 'Editar almacenes',
            self::WarehousesChangeStatus => 'Activar o desactivar almacenes',
            self::ArticleCategoriesView => 'Ver categorías de artículos',
            self::ArticleCategoriesCreate => 'Crear categorías de artículos',
            self::ArticleCategoriesUpdate => 'Editar categorías de artículos',
            self::ArticleCategoriesChangeStatus => 'Activar o desactivar categorías de artículos',
            self::MeasurementUnitsView => 'Ver unidades de medida',
            self::MeasurementUnitsCreate => 'Crear unidades de medida',
            self::MeasurementUnitsUpdate => 'Editar unidades de medida',
            self::MeasurementUnitsChangeStatus => 'Activar o desactivar unidades de medida',
            self::ArticlesView => 'Ver artículos',
            self::ArticlesCreate => 'Crear artículos',
            self::ArticlesUpdate => 'Editar artículos',
            self::ArticlesChangeStatus => 'Activar o desactivar artículos',
            self::ArticlePresentationsView => 'Ver presentaciones de artículos',
            self::ArticlePresentationsCreate => 'Crear presentaciones de artículos',
            self::ArticlePresentationsUpdate => 'Editar presentaciones de artículos',
            self::ArticlePresentationsChangeStatus => 'Activar o desactivar presentaciones de artículos',
            self::OrdersView => 'Ver pedidos',
            self::OrdersCreate => 'Crear pedidos',
            self::OrdersUpdate => 'Editar pedidos pendientes',
            self::OrdersCancel => 'Cancelar pedidos pendientes',
            self::OrdersPrepare => 'Preparar pedidos',
            self::ActivityView => 'Ver registro de actividad',
            self::ActivityViewDetails => 'Ver detalles del registro de actividad',
            self::ActivityExport => 'Exportar registro de actividad',
        };
    }

    public function module(): string
    {
        return str($this->value)->before('.')->toString();
    }

    public function moduleLabel(): string
    {
        return match ($this->module()) {
            'usuarios' => 'Usuarios',
            'roles' => 'Roles y permisos',
            'empleados' => 'Empleados',
            'clientes' => 'Clientes',
            'sucursales' => 'Sucursales',
            'proveedores' => 'Proveedores',
            'vehiculos' => 'Vehículos',
            'almacenes' => 'Almacenes',
            'categorias_articulos' => 'Categorías de artículos',
            'unidades_medida' => 'Unidades de medida',
            'articulos' => 'Artículos',
            'presentaciones_articulos' => 'Presentaciones de artículos',
            'pedidos' => 'Pedidos',
            'actividad' => 'Registro de actividad',
        };
    }
}
