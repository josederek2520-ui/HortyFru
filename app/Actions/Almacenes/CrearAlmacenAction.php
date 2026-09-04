<?php

namespace App\Actions\Almacenes;

use App\Models\Almacen;

class CrearAlmacenAction
{
    /** @param array<string, mixed> $datos */
    public function __invoke(array $datos): Almacen
    {
        return Almacen::query()->create($datos);
    }
}
