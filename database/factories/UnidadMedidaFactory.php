<?php

namespace Database\Factories;

use App\Models\UnidadMedida;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnidadMedida>
 */
class UnidadMedidaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $identificador = fake()->unique()->numberBetween(100, 99999);

        return [
            'nombre_unidad_medida' => 'Unidad '.$identificador,
            'abreviatura_unidad_medida' => 'u'.$identificador,
            'estado_unidad_medida' => true,
        ];
    }
}
