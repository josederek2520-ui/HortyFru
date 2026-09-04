<?php

namespace App\Actions\PresentacionesArticulos;

use App\Models\Articulo;
use App\Models\PresentacionArticulo;
use Illuminate\Support\Facades\DB;

class CrearPresentacionArticuloAction
{
    /** @param array<string, mixed> $datos */
    public function __invoke(array $datos): PresentacionArticulo
    {
        return DB::transaction(function () use ($datos): PresentacionArticulo {
            Articulo::query()->lockForUpdate()->findOrFail($datos['articulo_id']);

            $this->retirarPredeterminadasAnteriores($datos);

            return PresentacionArticulo::query()->create($datos);
        });
    }

    /** @param array<string, mixed> $datos */
    private function retirarPredeterminadasAnteriores(array $datos): void
    {
        $consulta = PresentacionArticulo::query()->where('articulo_id', $datos['articulo_id']);

        if ($datos['predeterminada_pedido_presentacion_articulo']) {
            (clone $consulta)->update(['predeterminada_pedido_presentacion_articulo' => false]);
        }

        if ($datos['predeterminada_compra_presentacion_articulo']) {
            (clone $consulta)->update(['predeterminada_compra_presentacion_articulo' => false]);
        }
    }
}
