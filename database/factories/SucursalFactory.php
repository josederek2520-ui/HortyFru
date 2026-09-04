<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sucursal>
 */
class SucursalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'nombre_sucursal' => 'Sucursal '.fake()->unique()->city(),
            'direccion_sucursal' => fake()->streetAddress(),
            'telefono_sucursal' => fake()->optional()->numerify('7#######'),
            'referencia_sucursal' => fake()->optional()->sentence(6),
            'activo_sucursal' => true,
        ];
    }
}
