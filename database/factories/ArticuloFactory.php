<?php

namespace Database\Factories;

use App\Models\Articulo;
use App\Models\CategoriaArticulo;
use App\Models\UnidadMedida;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Articulo>
 */
class ArticuloFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_articulo' => fake()->unique()->words(2, true),
            'categoria_articulo_id' => CategoriaArticulo::factory(),
            'unidad_medida_id' => UnidadMedida::factory(),
            'imagen_articulo' => null,
            'estado_articulo' => true,
        ];
    }
}
