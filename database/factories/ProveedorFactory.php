<?php

namespace Database\Factories;

use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proveedor>
 */
class ProveedorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_proveedor' => fake()->name(),
            'telefono_proveedor' => fake()->optional()->numerify('7#######'),
            'mercado_proveedor' => fake()->optional()->randomElement([
                'Mercado Abasto',
                'Mercado Central',
                'Mercado Nuevo',
            ]),
            'direccion_proveedor' => fake()->optional()->streetAddress(),
            'observacion_proveedor' => fake()->optional()->sentence(),
            'estado_proveedor' => true,
        ];
    }
}
