<?php

namespace App\Enums;

enum TipoMovimientoInventario: string
{
    case EntradaRecepcion = 'ENTRADA_RECEPCION';
    case SalidaPedido = 'SALIDA_PEDIDO';
    case SalidaProduccion = 'SALIDA_PRODUCCION';
    case EntradaProduccion = 'ENTRADA_PRODUCCION';
    case SalidaMerma = 'SALIDA_MERMA';
    case Devolucion = 'DEVOLUCION';
    case AjustePositivo = 'AJUSTE_POSITIVO';
    case AjusteNegativo = 'AJUSTE_NEGATIVO';

    public function label(): string
    {
        return match ($this) {
            self::EntradaRecepcion => 'Entrada por recepción',
            self::SalidaPedido => 'Salida por pedido',
            self::SalidaProduccion => 'Salida a producción',
            self::EntradaProduccion => 'Entrada de producción',
            self::SalidaMerma => 'Salida por merma',
            self::Devolucion => 'Devolución al almacén',
            self::AjustePositivo => 'Ajuste positivo',
            self::AjusteNegativo => 'Ajuste negativo',
        };
    }

    public function esEntrada(): bool
    {
        return in_array($this, [
            self::EntradaRecepcion,
            self::EntradaProduccion,
            self::Devolucion,
            self::AjustePositivo,
        ], true);
    }

    public function signo(): int
    {
        return $this->esEntrada() ? 1 : -1;
    }

    public function referenciaEsperada(): TipoReferenciaMovimientoInventario
    {
        return match ($this) {
            self::EntradaRecepcion => TipoReferenciaMovimientoInventario::Recepcion,
            self::SalidaPedido => TipoReferenciaMovimientoInventario::Pedido,
            self::SalidaProduccion, self::EntradaProduccion => TipoReferenciaMovimientoInventario::Produccion,
            self::SalidaMerma => TipoReferenciaMovimientoInventario::Merma,
            self::Devolucion => TipoReferenciaMovimientoInventario::Devolucion,
            self::AjustePositivo, self::AjusteNegativo => TipoReferenciaMovimientoInventario::Ajuste,
        };
    }

    /** @return list<string> */
    public static function valoresDeEntrada(): array
    {
        return array_values(array_map(
            static fn (self $tipo): string => $tipo->value,
            array_filter(self::cases(), static fn (self $tipo): bool => $tipo->esEntrada()),
        ));
    }
}
