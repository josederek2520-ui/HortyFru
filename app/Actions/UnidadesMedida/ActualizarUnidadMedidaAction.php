<?php

namespace App\Actions\UnidadesMedida;

use App\Models\UnidadMedida;

class ActualizarUnidadMedidaAction
{
    /** @param array<string, mixed> $datos */
    public function __invoke(UnidadMedida $unidadMedida, array $datos): UnidadMedida
    {
        $unidadMedida->update($datos);

        return $unidadMedida->refresh();
    }
}
