<?php

namespace App\Enums;

enum EstadoMovimientoInventario: string
{
    case Registrado = 'REGISTRADO';
    case Anulado = 'ANULADO';

    public function label(): string
    {
        return match ($this) {
            self::Registrado => 'Registrado',
            self::Anulado => 'Anulado',
        };
    }
}
