<?php

namespace App\Enums;

enum EstadoPedido: string
{
    case Pendiente = 'PENDIENTE';
    case Preparado = 'PREPARADO';
    case Cancelado = 'CANCELADO';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Preparado => 'Preparado',
            self::Cancelado => 'Cancelado',
        };
    }
}
