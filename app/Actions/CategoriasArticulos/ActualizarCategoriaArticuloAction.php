<?php

namespace App\Actions\CategoriasArticulos;

use App\Models\CategoriaArticulo;

class ActualizarCategoriaArticuloAction
{
    /** @param array<string, mixed> $datos */
    public function __invoke(CategoriaArticulo $categoriaArticulo, array $datos): CategoriaArticulo
    {
        $categoriaArticulo->update($datos);

        return $categoriaArticulo->refresh();
    }
}
