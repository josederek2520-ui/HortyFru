<?php

namespace App\Enums;

enum UsoPresentacionArticulo: string
{
    case Pedido = 'PEDIDO';
    case Compra = 'COMPRA';
    case Ambos = 'AMBOS';

    public function label(): string
    {
        return match ($this) {
            self::Pedido => 'Pedido',
            self::Compra => 'Compra',
            self::Ambos => 'Pedido y compra',
        };
    }

    public function permitePedido(): bool
    {
        return $this !== self::Compra;
    }

    public function permiteCompra(): bool
    {
        return $this !== self::Pedido;
    }
}
