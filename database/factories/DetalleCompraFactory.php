<?php

namespace Database\Factories;

use App\Enums\PrecioPorDetalleCompra;
use App\Models\Articulo;
use App\Models\Compra;
use App\Models\DetalleCompra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetalleCompra>
 */
class DetalleCompraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'compra_id' => Compra::factory(),
            'articulo_id' => Articulo::factory(),
            'presentacion_articulo_id' => null,
            'cantidad_presentaciones_detalle_compra' => null,
            'equivalencia_base_aplicada_detalle_compra' => null,
            'cantidad_real_detalle_compra' => '1.000',
            'unidad_medida_id' => fn (array $atributos): int => Articulo::query()->findOrFail($atributos['articulo_id'])->unidad_medida_id,
            'precio_unitario_detalle_compra' => '10.00',
            'precio_por_detalle_compra' => PrecioPorDetalleCompra::UnidadBase,
            'subtotal_detalle_compra' => '10.00',
            'observaciones_detalle_compra' => fake()->optional()->sentence(),
        ];
    }
}
