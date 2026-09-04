<?php

namespace Database\Factories;

use App\Models\Empleado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empleado>
 */
class EmpleadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'nombre_empleado' => fake()->firstName(),
            'apellido_empleado' => fake()->lastName(),
            'ci_empleado' => fake()->unique()->numerify('########'),
            'telefono_empleado' => fake()->optional()->numerify('7#######'),
            'direccion_empleado' => fake()->optional()->streetAddress(),
            'cargo_empleado' => fake()->randomElement([
                'Encargado de almacén',
                'Chofer',
                'Preparador',
                'Personal de selección',
            ]),
            'fecha_ingreso_empleado' => fake()->optional()->dateTimeBetween('-5 years', 'now'),
            'activo_empleado' => true,
        ];
    }
}
