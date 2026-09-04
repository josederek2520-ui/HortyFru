<?php

namespace Database\Factories;

use App\Models\CategoriaArticulo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoriaArticulo>
 */
class CategoriaArticuloFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_categoria_articulo' => fake()->unique()->randomElement([
                'Frutas',
                'Verduras',
                'Productos procesados',
                'Envases',
                'Insumos de limpieza',
                'Material de empaque',
            ]),
            'estado_categoria_articulo' => true,
        ];
    }
}
