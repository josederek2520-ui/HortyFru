<?php

namespace App\Actions\Articulos;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Articulo;
use Illuminate\Support\Facades\DB;

class CambiarEstadoArticuloAction
{
    public function __invoke(Articulo $articulo): Articulo
    {
        return DB::transaction(function () use ($articulo): Articulo {
            $estadoAnterior = $articulo->estado_articulo;
            $nuevoEstado = ! $estadoAnterior;

            $articulo->disableLogging();
            $articulo->update(['estado_articulo' => $nuevoEstado]);
            $articulo->enableLogging();

            activity(ActivityLogName::Articles->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($articulo)
                ->withProperties([
                    'old' => ['estado_articulo' => $estadoAnterior],
                    'attributes' => ['estado_articulo' => $nuevoEstado],
                ])
                ->log($nuevoEstado ? 'Producto activado' : 'Producto desactivado');

            return $articulo->refresh();
        });
    }
}
