<?php

namespace App\Actions\Vehiculos;

use App\Models\Vehiculo;

class ActualizarVehiculoAction
{
    /** @param array<string, mixed> $datos */
    public function __invoke(Vehiculo $vehiculo, array $datos): Vehiculo
    {
        $vehiculo->update($datos);

        return $vehiculo->refresh();
    }
}
