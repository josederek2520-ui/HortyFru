<?php

namespace App\Actions\UnidadesMedida;

use App\Models\UnidadMedida;

class CrearUnidadMedidaAction
{
    /** @param array<string, mixed> $datos */
    public function __invoke(array $datos): UnidadMedida
    {
        return UnidadMedida::query()->create($datos);
    }
}
