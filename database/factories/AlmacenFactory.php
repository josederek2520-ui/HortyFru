<?php

namespace Database\Factories;

use App\Models\Almacen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Almacen>
 */
class AlmacenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_almacen' => 'Almacén '.fake()->unique()->city(),
            'direccion_almacen' => fake()->optional()->streetAddress(),
            'estado_almacen' => true,
        ];
    }
}
