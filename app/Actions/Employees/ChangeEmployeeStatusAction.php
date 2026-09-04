<?php

namespace App\Actions\Employees;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Empleado;
use Illuminate\Support\Facades\DB;

class ChangeEmployeeStatusAction
{
    public function __invoke(Empleado $empleado): Empleado
    {
        return DB::transaction(function () use ($empleado): Empleado {
            $previousStatus = $empleado->activo_empleado;
            $newStatus = ! $previousStatus;

            $empleado->disableLogging();
            $empleado->update(['activo_empleado' => $newStatus]);
            $empleado->enableLogging();

            if (! $empleado->activo_empleado && $empleado->user_id !== null) {
                $empleado->user()->update(['activo_usuario' => false]);
            }

            activity(ActivityLogName::Employees->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($empleado)
                ->withProperties([
                    'old' => ['activo_empleado' => $previousStatus],
                    'attributes' => ['activo_empleado' => $newStatus],
                ])
                ->log($newStatus ? 'Empleado reincorporado' : 'Empleado retirado');

            return $empleado->refresh()->load('user:id,name,email,activo_usuario');
        });
    }
}
