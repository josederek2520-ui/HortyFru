<?php

namespace App\Actions\Almacenes;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Almacen;
use Illuminate\Support\Facades\DB;

class CambiarEstadoAlmacenAction
{
    public function __invoke(Almacen $almacen): Almacen
    {
        return DB::transaction(function () use ($almacen): Almacen {
            $estadoAnterior = $almacen->estado_almacen;
            $nuevoEstado = ! $estadoAnterior;

            $almacen->disableLogging();
            $almacen->update(['estado_almacen' => $nuevoEstado]);
            $almacen->enableLogging();

            activity(ActivityLogName::Warehouses->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($almacen)
                ->withProperties([
                    'old' => ['estado_almacen' => $estadoAnterior],
                    'attributes' => ['estado_almacen' => $nuevoEstado],
                ])
                ->log($nuevoEstado ? 'Almacén activado' : 'Almacén desactivado');

            return $almacen->refresh();
        });
    }
}
