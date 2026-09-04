<?php

namespace App\Enums;

enum TipoEquivalenciaPresentacionArticulo: string
{
    case Fija = 'FIJA';
    case Aproximada = 'APROXIMADA';
    case Variable = 'VARIABLE';

    public function label(): string
    {
        return match ($this) {
            self::Fija => 'Fija',
            self::Aproximada => 'Aproximada',
            self::Variable => 'Variable',
        };
    }
}
