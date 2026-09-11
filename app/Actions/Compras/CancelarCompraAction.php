<?php

namespace App\Actions\Compras;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoCompra;
use App\Enums\EstadoRecepcion;
use App\Models\Compra;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelarCompraAction
{
    public function __invoke(Compra $compra, string $motivo, User $usuario): Compra
    {
        return DB::transaction(function () use ($compra, $motivo, $usuario): Compra {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);

            if (! $compra->sePuedeCancelar()) {
                throw ValidationException::withMessages(['cancelacion' => 'Esta compra ya no se puede cancelar.']);
            }

            if ($compra->recepciones()
                ->where('estado_recepcion', '!=', EstadoRecepcion::Cancelada->value)
                ->exists()) {
                throw ValidationException::withMessages([
                    'cancelacion' => 'No puedes cancelar una compra que tiene una recepción activa.',
                ]);
            }

            $estadoAnterior = $compra->estado_compra;
            $compra->disableLogging();
            $compra->update([
                'estado_compra' => EstadoCompra::Cancelada,
                'cancelado_por' => $usuario->id,
                'cancelado_en' => now(),
                'motivo_cancelacion_compra' => $motivo,
            ]);
            $compra->enableLogging();

            activity(ActivityLogName::Purchases->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($compra)
                ->causedBy($usuario)
                ->withProperties([
                    'old' => ['estado_compra' => $estadoAnterior->value],
                    'attributes' => [
                        'estado_compra' => EstadoCompra::Cancelada->value,
                        'cancelado_por' => $usuario->id,
                        'cancelado_en' => $compra->cancelado_en?->toDateTimeString(),
                        'motivo_cancelacion_compra' => $motivo,
                    ],
                ])
                ->log('Compra cancelada');

            return $compra->refresh();
        });
    }
}
