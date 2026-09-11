<?php

namespace App\Enums;

enum EstadoLote: string
{
    case Disponible = 'DISPONIBLE';
    case Bloqueado = 'BLOQUEADO';
    case Agotado = 'AGOTADO';

    public function label(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::Bloqueado => 'Bloqueado',
            self::Agotado => 'Agotado',
        };
    }

    public function sePuedeCambiarManualmente(): bool
    {
        return $this !== self::Agotado;
    }
}
