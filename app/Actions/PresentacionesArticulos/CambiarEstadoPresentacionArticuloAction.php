<?php

namespace App\Actions\PresentacionesArticulos;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\PresentacionArticulo;
use Illuminate\Support\Facades\DB;

class CambiarEstadoPresentacionArticuloAction
{
    public function __invoke(PresentacionArticulo $presentacionArticulo): PresentacionArticulo
    {
        return DB::transaction(function () use ($presentacionArticulo): PresentacionArticulo {
            $estadoAnterior = $presentacionArticulo->estado_presentacion_articulo;
            $nuevoEstado = ! $estadoAnterior;
            $valoresAnteriores = [
                'estado_presentacion_articulo' => $estadoAnterior,
                'predeterminada_pedido_presentacion_articulo' => $presentacionArticulo->predeterminada_pedido_presentacion_articulo,
                'predeterminada_compra_presentacion_articulo' => $presentacionArticulo->predeterminada_compra_presentacion_articulo,
            ];
            $nuevosValores = [
                'estado_presentacion_articulo' => $nuevoEstado,
                'predeterminada_pedido_presentacion_articulo' => $nuevoEstado
                    ? $presentacionArticulo->predeterminada_pedido_presentacion_articulo
                    : false,
                'predeterminada_compra_presentacion_articulo' => $nuevoEstado
                    ? $presentacionArticulo->predeterminada_compra_presentacion_articulo
                    : false,
            ];

            $presentacionArticulo->disableLogging();
            $presentacionArticulo->update($nuevosValores);
            $presentacionArticulo->enableLogging();

            activity(ActivityLogName::ArticlePresentations->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($presentacionArticulo)
                ->withProperties([
                    'old' => $valoresAnteriores,
                    'attributes' => $nuevosValores,
                ])
                ->log($nuevoEstado ? 'Presentación de producto activada' : 'Presentación de producto desactivada');

            return $presentacionArticulo->refresh();
        });
    }
}
