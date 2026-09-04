<?php

namespace App\Actions\Vehiculos;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\DB;

class CambiarEstadoVehiculoAction
{
    public function __invoke(Vehiculo $vehiculo): Vehiculo
    {
        return DB::transaction(function () use ($vehiculo): Vehiculo {
            $estadoAnterior = $vehiculo->estado_vehiculo;
            $nuevoEstado = ! $estadoAnterior;

            $vehiculo->disableLogging();
            $vehiculo->update(['estado_vehiculo' => $nuevoEstado]);
            $vehiculo->enableLogging();

            activity(ActivityLogName::Vehicles->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($vehiculo)
                ->withProperties([
                    'old' => ['estado_vehiculo' => $estadoAnterior],
                    'attributes' => ['estado_vehiculo' => $nuevoEstado],
                ])
                ->log($nuevoEstado ? 'Vehículo activado' : 'Vehículo desactivado');

            return $vehiculo->refresh();
        });
    }
}
