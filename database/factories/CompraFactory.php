<?php

namespace Database\Factories;

use App\Enums\EstadoCompra;
use App\Models\Compra;
use App\Models\Empleado;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Compra>
 */
class CompraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_compra' => fake()->unique()->numerify('COM-TEST-######'),
            'proveedor_id' => Proveedor::factory(),
            'empleado_id' => Empleado::factory(),
            'fecha_compra' => fake()->dateTimeBetween('-1 month'),
            'estado_compra' => EstadoCompra::Borrador,
            'total_compra' => '0.00',
            'observaciones_compra' => fake()->optional()->sentence(),
            'registrado_por' => User::factory(),
        ];
    }

    public function registrada(): static
    {
        return $this->state(fn (): array => [
            'estado_compra' => EstadoCompra::Registrada,
        ]);
    }

    public function cancelada(): static
    {
        return $this->state(fn (): array => [
            'estado_compra' => EstadoCompra::Cancelada,
            'cancelado_por' => User::factory(),
            'cancelado_en' => now(),
            'motivo_cancelacion_compra' => 'Compra cancelada durante una prueba.',
        ]);
    }
}
