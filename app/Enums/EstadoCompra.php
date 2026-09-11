<?php

namespace App\Enums;

enum EstadoCompra: string
{
    case Borrador = 'BORRADOR';
    case Registrada = 'REGISTRADA';
    case Cancelada = 'CANCELADA';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Registrada => 'Registrada',
            self::Cancelada => 'Cancelada',
        };
    }
}
