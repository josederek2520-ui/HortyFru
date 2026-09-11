<?php

namespace App\Enums;

enum TipoOrigenLote: string
{
    case Recepcion = 'RECEPCION';
    case Produccion = 'PRODUCCION';
    case Devolucion = 'DEVOLUCION';
    case Ajuste = 'AJUSTE';

    public function label(): string
    {
        return match ($this) {
            self::Recepcion => 'Recepción',
            self::Produccion => 'Producción',
            self::Devolucion => 'Devolución',
            self::Ajuste => 'Ajuste de inventario',
        };
    }
}
