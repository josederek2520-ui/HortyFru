<?php

namespace App\Actions\Lotes;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoLote;
use App\Models\Lote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CambiarEstadoLoteAction
{
    public function __invoke(Lote $lote, ?string $motivo, User $usuario): Lote
    {
        return DB::transaction(function () use ($lote, $motivo, $usuario): Lote {
            $lote = Lote::query()->lockForUpdate()->findOrFail($lote->id);

            if (! $lote->estado_lote->sePuedeCambiarManualmente()) {
                throw ValidationException::withMessages([
                    'estado' => 'Un lote agotado solo puede cambiar mediante un movimiento de inventario.',
                ]);
            }

            $estadoAnterior = $lote->estado_lote;
            $nuevoEstado = $estadoAnterior === EstadoLote::Disponible
                ? EstadoLote::Bloqueado
                : EstadoLote::Disponible;
            $motivo = Str::squish($motivo ?? '');

            if ($nuevoEstado === EstadoLote::Bloqueado && $motivo === '') {
                throw ValidationException::withMessages([
                    'motivoBloqueo' => 'Explica por qué se bloqueará el lote.',
                ]);
            }

            if ($nuevoEstado === EstadoLote::Disponible && $lote->estaVencido()) {
                throw ValidationException::withMessages([
                    'estado' => 'No se puede habilitar un lote cuya fecha de vencimiento ya pasó.',
                ]);
            }

            $motivoAnterior = $lote->motivo_bloqueo_lote;
            $lote->disableLogging();
            $lote->update([
                'estado_lote' => $nuevoEstado,
                'motivo_bloqueo_lote' => $nuevoEstado === EstadoLote::Bloqueado ? $motivo : null,
            ]);
            $lote->enableLogging();

            activity(ActivityLogName::Lots->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($lote)
                ->causedBy($usuario)
                ->withProperties([
                    'old' => [
                        'estado_lote' => $estadoAnterior->value,
                        'motivo_bloqueo_lote' => $motivoAnterior,
                    ],
                    'attributes' => [
                        'estado_lote' => $nuevoEstado->value,
                        'motivo_bloqueo_lote' => $lote->motivo_bloqueo_lote,
                    ],
                ])
                ->log($nuevoEstado === EstadoLote::Bloqueado ? 'Lote bloqueado' : 'Lote habilitado');

            return $lote->refresh();
        });
    }
}
