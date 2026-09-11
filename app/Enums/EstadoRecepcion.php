<?php

namespace App\Enums;

enum EstadoRecepcion: string
{
    case Borrador = 'BORRADOR';
    case Confirmada = 'CONFIRMADA';
    case Cancelada = 'CANCELADA';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Confirmada => 'Confirmada',
            self::Cancelada => 'Cancelada',
        };
    }
}
