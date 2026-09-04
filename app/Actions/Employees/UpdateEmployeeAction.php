<?php

namespace App\Actions\Employees;

use App\Models\Empleado;
use Illuminate\Support\Facades\DB;

class UpdateEmployeeAction
{
    /** @param array<string, mixed> $data */
    public function __invoke(Empleado $empleado, array $data): Empleado
    {
        return DB::transaction(function () use ($empleado, $data): Empleado {
            $empleado->update($data);

            if (! $empleado->activo_empleado && $empleado->user_id !== null) {
                $empleado->user()->update(['activo_usuario' => false]);
            }

            return $empleado->refresh()->load('user:id,name,email,activo_usuario');
        });
    }
}
