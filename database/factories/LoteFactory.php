<?php

namespace Database\Factories;

use App\Enums\EstadoLote;
use App\Enums\TipoOrigenLote;
use App\Models\Articulo;
use App\Models\Lote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lote>
 */
class LoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_lote' => fake()->unique()->bothify('LOT-TEST-########'),
            'articulo_id' => Articulo::factory(),
            'tipo_origen_lote' => TipoOrigenLote::Recepcion,
            'fecha_ingreso_lote' => fake()->dateTimeBetween('-1 month'),
            'fecha_vencimiento_lote' => fake()->optional(0.8)->dateTimeBetween('now', '+2 weeks'),
            'calidad_lote' => fake()->optional()->randomElement(['Buena', 'Regular', 'Madura']),
            'estado_lote' => EstadoLote::Disponible,
            'motivo_bloqueo_lote' => null,
            'observaciones_lote' => fake()->optional()->sentence(),
        ];
    }

    public function bloqueado(): static
    {
        return $this->state(fn (): array => [
            'estado_lote' => EstadoLote::Bloqueado,
            'motivo_bloqueo_lote' => 'Pendiente de revisión de calidad.',
        ]);
    }

    public function agotado(): static
    {
        return $this->state(fn (): array => [
            'estado_lote' => EstadoLote::Agotado,
            'motivo_bloqueo_lote' => null,
        ]);
    }
}
