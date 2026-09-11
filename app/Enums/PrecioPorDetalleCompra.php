<?php

namespace App\Enums;

enum PrecioPorDetalleCompra: string
{
    case Presentacion = 'PRESENTACION';
    case UnidadBase = 'UNIDAD_BASE';

    public function label(): string
    {
        return match ($this) {
            self::Presentacion => 'Por presentación',
            self::UnidadBase => 'Por unidad base',
        };
    }
}
