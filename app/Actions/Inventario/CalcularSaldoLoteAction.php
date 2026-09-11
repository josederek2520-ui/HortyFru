<?php

namespace App\Actions\Inventario;

use App\Enums\EstadoMovimientoInventario;
use App\Enums\TipoMovimientoInventario;
use App\Models\Almacen;
use App\Models\DetalleMovimientoInventario;
use App\Models\Lote;
use Brick\Math\BigDecimal;

class CalcularSaldoLoteAction
{
    public function __invoke(Lote $lote, ?Almacen $almacen = null): BigDecimal
    {
        $tiposEntrada = TipoMovimientoInventario::valoresDeEntrada();
        $marcadores = implode(', ', array_fill(0, count($tiposEntrada), '?'));

        $saldo = DetalleMovimientoInventario::query()
            ->join(
                'movimientos_inventario',
                'movimientos_inventario.id',
                '=',
                'detalle_movimiento_inventario.movimiento_inventario_id',
            )
            ->where('detalle_movimiento_inventario.lote_id', $lote->id)
            ->where(
                'movimientos_inventario.estado_movimiento_inventario',
                EstadoMovimientoInventario::Registrado->value,
            )
            ->when(
                $almacen !== null,
                fn ($query) => $query->where('movimientos_inventario.almacen_id', $almacen->id),
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN movimientos_inventario.tipo_movimiento_inventario IN ({$marcadores}) THEN detalle_movimiento_inventario.cantidad_movimiento_inventario ELSE -detalle_movimiento_inventario.cantidad_movimiento_inventario END), 0) AS saldo",
                $tiposEntrada,
            )
            ->value('saldo');

        return BigDecimal::of((string) ($saldo ?? '0'));
    }
}
