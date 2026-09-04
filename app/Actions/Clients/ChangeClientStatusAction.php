<?php

namespace App\Actions\Clients;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;

class ChangeClientStatusAction
{
    public function __invoke(Cliente $cliente): Cliente
    {
        return DB::transaction(function () use ($cliente): Cliente {
            $previousStatus = $cliente->activo_cliente;
            $newStatus = ! $previousStatus;

            $cliente->disableLogging();
            $cliente->update(['activo_cliente' => $newStatus]);
            $cliente->enableLogging();

            activity(ActivityLogName::Clients->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($cliente)
                ->withProperties([
                    'old' => ['activo_cliente' => $previousStatus],
                    'attributes' => ['activo_cliente' => $newStatus],
                ])
                ->log($newStatus ? 'Cliente activado' : 'Cliente desactivado');

            return $cliente->refresh();
        });
    }
}
