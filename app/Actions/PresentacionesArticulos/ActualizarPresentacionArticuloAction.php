<?php

namespace App\Actions\PresentacionesArticulos;

use App\Models\Articulo;
use App\Models\PresentacionArticulo;
use Illuminate\Support\Facades\DB;

class ActualizarPresentacionArticuloAction
{
    /** @param array<string, mixed> $datos */
    public function __invoke(PresentacionArticulo $presentacionArticulo, array $datos): PresentacionArticulo
    {
        return DB::transaction(function () use ($presentacionArticulo, $datos): PresentacionArticulo {
            Articulo::query()
                ->whereIn('id', array_unique([$presentacionArticulo->articulo_id, $datos['articulo_id']]))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $this->retirarPredeterminadasAnteriores($presentacionArticulo, $datos);
            $presentacionArticulo->update($datos);

            return $presentacionArticulo->refresh();
        });
    }

    /** @param array<string, mixed> $datos */
    private function retirarPredeterminadasAnteriores(
        PresentacionArticulo $presentacionArticulo,
        array $datos,
    ): void {
        $consulta = PresentacionArticulo::query()
            ->where('articulo_id', $datos['articulo_id'])
            ->where('id', '!=', $presentacionArticulo->id);

        if ($datos['predeterminada_pedido_presentacion_articulo']) {
            (clone $consulta)->update(['predeterminada_pedido_presentacion_articulo' => false]);
        }

        if ($datos['predeterminada_compra_presentacion_articulo']) {
            (clone $consulta)->update(['predeterminada_compra_presentacion_articulo' => false]);
        }
    }
}
