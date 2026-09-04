<?php

namespace App\Actions\Vehiculos;

use App\Models\Vehiculo;

class CrearVehiculoAction
{
    /** @param array<string, mixed> $datos */
    public function __invoke(array $datos): Vehiculo
    {
        return Vehiculo::query()->create($datos);
    }
}
