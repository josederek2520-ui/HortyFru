<?php

namespace App\Actions\Proveedores;

use App\Models\Proveedor;

class ActualizarProveedorAction
{
    /** @param array<string, mixed> $datos */
    public function __invoke(Proveedor $proveedor, array $datos): Proveedor
    {
        $proveedor->update($datos);

        return $proveedor->refresh();
    }
}
