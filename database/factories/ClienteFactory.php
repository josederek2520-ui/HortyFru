<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'razon_social' => fake()->company(),
            'nit' => fake()->unique()->numerify('##########'),
            'telefono_cliente' => fake()->optional()->numerify('7#######'),
            'email_cliente' => fake()->optional()->companyEmail(),
            'activo_cliente' => true,
        ];
    }
}
