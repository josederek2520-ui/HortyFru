<?php

namespace App\Actions\Proveedores;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;

class CambiarEstadoProveedorAction
{
    public function __invoke(Proveedor $proveedor): Proveedor
    {
        return DB::transaction(function () use ($proveedor): Proveedor {
            $estadoAnterior = $proveedor->estado_proveedor;
            $nuevoEstado = ! $estadoAnterior;

            $proveedor->disableLogging();
            $proveedor->update(['estado_proveedor' => $nuevoEstado]);
            $proveedor->enableLogging();

            activity(ActivityLogName::Providers->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($proveedor)
                ->withProperties([
                    'old' => ['estado_proveedor' => $estadoAnterior],
                    'attributes' => ['estado_proveedor' => $nuevoEstado],
                ])
                ->log($nuevoEstado ? 'Proveedor activado' : 'Proveedor desactivado');

            return $proveedor->refresh();
        });
    }
}
