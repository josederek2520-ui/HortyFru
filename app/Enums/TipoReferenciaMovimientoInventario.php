<?php

namespace App\Enums;

enum TipoReferenciaMovimientoInventario: string
{
    case Recepcion = 'RECEPCION';
    case Pedido = 'PEDIDO';
    case Produccion = 'PRODUCCION';
    case Merma = 'MERMA';
    case Devolucion = 'DEVOLUCION';
    case Ajuste = 'AJUSTE';

    public function label(): string
    {
        return match ($this) {
            self::Recepcion => 'Recepción',
            self::Pedido => 'Pedido',
            self::Produccion => 'Producción',
            self::Merma => 'Merma',
            self::Devolucion => 'Devolución',
            self::Ajuste => 'Ajuste de inventario',
        };
    }
}
