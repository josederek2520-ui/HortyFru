<?php

namespace Database\Factories;

use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehiculo>
 */
class VehiculoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placa_vehiculo' => fake()->unique()->bothify('???-###'),
            'marca_vehiculo' => fake()->optional()->randomElement([
                'Toyota',
                'Nissan',
                'Suzuki',
                'Hyundai',
            ]),
            'tipo_vehiculo' => fake()->randomElement([
                'Camión',
                'Camioneta',
                'Automóvil',
                'Motocicleta',
            ]),
            'estado_vehiculo' => true,
        ];
    }
}
