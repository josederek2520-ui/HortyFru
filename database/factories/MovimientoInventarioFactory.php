<?php

namespace Database\Factories;

use App\Enums\EstadoMovimientoInventario;
use App\Enums\TipoMovimientoInventario;
use App\Enums\TipoReferenciaMovimientoInventario;
use App\Models\Almacen;
use App\Models\MovimientoInventario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovimientoInventario>
 */
class MovimientoInventarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_movimiento_inventario' => fake()->unique()->numerify('MOV-TEST-######'),
            'almacen_id' => Almacen::factory(),
            'tipo_movimiento_inventario' => TipoMovimientoInventario::AjustePositivo,
            'fecha_movimiento_inventario' => fake()->dateTimeBetween('-1 month'),
            'estado_movimiento_inventario' => EstadoMovimientoInventario::Registrado,
            'tipo_referencia_movimiento_inventario' => TipoReferenciaMovimientoInventario::Ajuste,
            'referencia_id_movimiento_inventario' => fake()->unique()->numberBetween(1, 1000000),
            'observaciones_movimiento_inventario' => fake()->optional()->sentence(),
            'registrado_por' => User::factory(),
        ];
    }

    public function anulado(): static
    {
        return $this->state(fn (): array => [
            'estado_movimiento_inventario' => EstadoMovimientoInventario::Anulado,
        ]);
    }
}
