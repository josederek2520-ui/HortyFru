<?php

namespace Database\Factories;

use App\Models\Articulo;
use App\Models\DetalleRecepcion;
use App\Models\Recepcion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetalleRecepcion>
 */
class DetalleRecepcionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recepcion_id' => Recepcion::factory(),
            'detalle_compra_id' => null,
            'articulo_id' => Articulo::factory(),
            'presentacion_articulo_id' => null,
            'cantidad_presentaciones_detalle_recepcion' => null,
            'equivalencia_base_aplicada_detalle_recepcion' => null,
            'cantidad_base_detalle_recepcion' => '1.000',
            'unidad_medida_id' => fn (array $atributos): int => Articulo::query()
                ->findOrFail($atributos['articulo_id'])
                ->unidad_medida_id,
            'fecha_vencimiento_detalle_recepcion' => null,
            'calidad_detalle_recepcion' => null,
            'lote_id' => null,
            'observaciones_detalle_recepcion' => fake()->optional()->sentence(),
        ];
    }
}
