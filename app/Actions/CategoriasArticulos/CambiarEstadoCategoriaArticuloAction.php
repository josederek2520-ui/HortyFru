<?php

namespace App\Actions\CategoriasArticulos;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\CategoriaArticulo;
use Illuminate\Support\Facades\DB;

class CambiarEstadoCategoriaArticuloAction
{
    public function __invoke(CategoriaArticulo $categoriaArticulo): CategoriaArticulo
    {
        return DB::transaction(function () use ($categoriaArticulo): CategoriaArticulo {
            $estadoAnterior = $categoriaArticulo->estado_categoria_articulo;
            $nuevoEstado = ! $estadoAnterior;

            $categoriaArticulo->disableLogging();
            $categoriaArticulo->update(['estado_categoria_articulo' => $nuevoEstado]);
            $categoriaArticulo->enableLogging();

            activity(ActivityLogName::ArticleCategories->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($categoriaArticulo)
                ->withProperties([
                    'old' => ['estado_categoria_articulo' => $estadoAnterior],
                    'attributes' => ['estado_categoria_articulo' => $nuevoEstado],
                ])
                ->log($nuevoEstado ? 'Categoría de producto activada' : 'Categoría de producto desactivada');

            return $categoriaArticulo->refresh();
        });
    }
}
