<?php

namespace Database\Factories;

use App\Models\DetalleMovimientoInventario;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetalleMovimientoInventario>
 */
class DetalleMovimientoInventarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'movimiento_inventario_id' => MovimientoInventario::factory(),
            'lote_id' => Lote::factory(),
            'articulo_id' => fn (array $attributes): int => (int) Lote::query()
                ->findOrFail($attributes['lote_id'])
                ->articulo_id,
            'unidad_medida_id' => fn (array $attributes): int => (int) Lote::query()
                ->with('articulo:id,unidad_medida_id')
                ->findOrFail($attributes['lote_id'])
                ->articulo
                ->unidad_medida_id,
            'cantidad_movimiento_inventario' => fake()->randomFloat(3, 1, 100),
        ];
    }
}
