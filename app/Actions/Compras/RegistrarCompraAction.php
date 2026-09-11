<?php

namespace App\Actions\Compras;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoCompra;
use App\Models\Compra;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrarCompraAction
{
    public function __invoke(Compra $compra, User $usuario): Compra
    {
        return DB::transaction(function () use ($compra, $usuario): Compra {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);

            if (! $compra->estaEnBorrador()) {
                throw ValidationException::withMessages(['registro' => 'Esta compra ya no está en borrador.']);
            }

            $cantidadItems = $compra->detalles()->lockForUpdate()->count();

            if ($cantidadItems === 0 || (float) $compra->total_compra <= 0) {
                throw ValidationException::withMessages(['registro' => 'Agrega al menos un producto válido antes de registrar la compra.']);
            }

            $compra->disableLogging();
            $compra->update(['estado_compra' => EstadoCompra::Registrada]);
            $compra->enableLogging();

            activity(ActivityLogName::Purchases->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($compra)
                ->causedBy($usuario)
                ->withProperties([
                    'old' => ['estado_compra' => EstadoCompra::Borrador->value],
                    'attributes' => [
                        'estado_compra' => EstadoCompra::Registrada->value,
                        'cantidad_items_compra' => $cantidadItems,
                        'total_compra' => $compra->total_compra,
                    ],
                ])
                ->log('Compra registrada');

            return $compra->refresh();
        });
    }
}
