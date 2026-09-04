<?php

namespace App\Actions\CategoriasArticulos;

use App\Models\CategoriaArticulo;

class CrearCategoriaArticuloAction
{
    /** @param array<string, mixed> $datos */
    public function __invoke(array $datos): CategoriaArticulo
    {
        return CategoriaArticulo::query()->create($datos);
    }
}
