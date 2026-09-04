<?php

namespace App\Actions\Almacenes;

use App\Models\Almacen;

class ActualizarAlmacenAction
{
    /** @param array<string, mixed> $datos */
    public function __invoke(Almacen $almacen, array $datos): Almacen
    {
        $almacen->update($datos);

        return $almacen->refresh();
    }
}
