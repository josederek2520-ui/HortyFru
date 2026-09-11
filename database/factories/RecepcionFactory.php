<?php

namespace Database\Factories;

use App\Enums\EstadoRecepcion;
use App\Models\Almacen;
use App\Models\Empleado;
use App\Models\Recepcion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recepcion>
 */
class RecepcionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_recepcion' => fake()->unique()->numerify('REC-TEST-######'),
            'compra_id' => null,
            'almacen_id' => Almacen::factory(),
            'empleado_id' => Empleado::factory(),
            'fecha_recepcion' => fake()->dateTimeBetween('-1 month'),
            'estado_recepcion' => EstadoRecepcion::Borrador,
            'observaciones_recepcion' => fake()->optional()->sentence(),
            'registrado_por' => User::factory(),
            'confirmado_por' => null,
            'confirmado_en' => null,
            'cancelado_por' => null,
            'cancelado_en' => null,
            'motivo_cancelacion_recepcion' => null,
        ];
    }

    public function confirmada(): static
    {
        return $this->state(fn (): array => [
            'estado_recepcion' => EstadoRecepcion::Confirmada,
            'confirmado_por' => User::factory(),
            'confirmado_en' => now(),
        ]);
    }

    public function cancelada(): static
    {
        return $this->state(fn (): array => [
            'estado_recepcion' => EstadoRecepcion::Cancelada,
            'cancelado_por' => User::factory(),
            'cancelado_en' => now(),
            'motivo_cancelacion_recepcion' => 'Recepción cancelada durante una prueba.',
        ]);
    }
}
