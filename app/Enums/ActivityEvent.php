<?php

namespace App\Enums;

enum ActivityEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case StatusChanged = 'status_changed';
    case PreparationUpdated = 'preparation_updated';
    case RoleAssigned = 'role_assigned';
    case PermissionsUpdated = 'permissions_updated';
    case PasswordChanged = 'password_changed';
    case LoginSucceeded = 'login_succeeded';
    case LoginFailed = 'login_failed';
    case LoginBlocked = 'login_blocked';
    case Logout = 'logout';
    case PasswordReset = 'password_reset';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Creación',
            self::Updated => 'Actualización',
            self::Deleted => 'Eliminación',
            self::StatusChanged => 'Cambio de estado',
            self::PreparationUpdated => 'Preparación',
            self::RoleAssigned => 'Asignación de rol',
            self::PermissionsUpdated => 'Cambio de permisos',
            self::PasswordChanged => 'Cambio de contraseña',
            self::LoginSucceeded => 'Inicio de sesión',
            self::LoginFailed => 'Acceso fallido',
            self::LoginBlocked => 'Acceso bloqueado',
            self::Logout => 'Cierre de sesión',
            self::PasswordReset => 'Restablecimiento de contraseña',
        };
    }
}
