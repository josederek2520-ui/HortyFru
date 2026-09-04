<?php

namespace App\Actions\Employees;

use App\Models\Empleado;
use Illuminate\Support\Facades\DB;

class CreateEmployeeAction
{
    /** @param array<string, mixed> $data */
    public function __invoke(array $data): Empleado
    {
        return DB::transaction(function () use ($data): Empleado {
            $empleado = Empleado::query()->create($data);

            if (! $empleado->activo_empleado && $empleado->user_id !== null) {
                $empleado->user()->update(['activo_usuario' => false]);
            }

            return $empleado->load('user:id,name,email,activo_usuario');
        });
    }
}
