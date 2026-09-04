<?php

namespace App\Actions\Proveedores;

use App\Models\Proveedor;

class CrearProveedorAction
{
    /** @param array<string, mixed> $datos */
    public function __invoke(array $datos): Proveedor
    {
        return Proveedor::query()->create($datos);
    }
}
