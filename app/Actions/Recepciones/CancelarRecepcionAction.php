<?php

namespace App\Actions\Recepciones;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoRecepcion;
use App\Models\Recepcion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelarRecepcionAction
{
    public function __invoke(Recepcion $recepcion, string $motivo, User $usuario): Recepcion
    {
        return DB::transaction(function () use ($recepcion, $motivo, $usuario): Recepcion {
            $recepcion = Recepcion::query()->lockForUpdate()->findOrFail($recepcion->id);

            if (! $recepcion->sePuedeCancelar()) {
                throw ValidationException::withMessages([
                    'cancelacion' => 'Solo se puede cancelar una recepción en borrador.',
                ]);
            }

            $recepcion->disableLogging();
            $recepcion->update([
                'estado_recepcion' => EstadoRecepcion::Cancelada,
                'cancelado_por' => $usuario->id,
                'cancelado_en' => now(),
                'motivo_cancelacion_recepcion' => $motivo,
            ]);
            $recepcion->enableLogging();

            activity(ActivityLogName::Receptions->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($recepcion)
                ->causedBy($usuario)
                ->withProperties([
                    'old' => ['estado_recepcion' => EstadoRecepcion::Borrador->value],
                    'attributes' => [
                        'estado_recepcion' => EstadoRecepcion::Cancelada->value,
                        'cancelado_por' => $usuario->id,
                        'cancelado_en' => $recepcion->cancelado_en?->toDateTimeString(),
                        'motivo_cancelacion_recepcion' => $motivo,
                    ],
                ])
                ->log('Recepción cancelada');

            return $recepcion->refresh();
        });
    }
}
