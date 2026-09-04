<?php

namespace App\Actions\UnidadesMedida;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\UnidadMedida;
use Illuminate\Support\Facades\DB;

class CambiarEstadoUnidadMedidaAction
{
    public function __invoke(UnidadMedida $unidadMedida): UnidadMedida
    {
        return DB::transaction(function () use ($unidadMedida): UnidadMedida {
            $estadoAnterior = $unidadMedida->estado_unidad_medida;
            $nuevoEstado = ! $estadoAnterior;

            $unidadMedida->disableLogging();
            $unidadMedida->update(['estado_unidad_medida' => $nuevoEstado]);
            $unidadMedida->enableLogging();

            activity(ActivityLogName::MeasurementUnits->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($unidadMedida)
                ->withProperties([
                    'old' => ['estado_unidad_medida' => $estadoAnterior],
                    'attributes' => ['estado_unidad_medida' => $nuevoEstado],
                ])
                ->log($nuevoEstado ? 'Unidad de medida activada' : 'Unidad de medida desactivada');

            return $unidadMedida->refresh();
        });
    }
}
