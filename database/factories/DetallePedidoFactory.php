<?php

namespace Database\Factories;

use App\Models\Articulo;
use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\PresentacionArticulo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetallePedido>
 */
class DetallePedidoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pedido_id' => Pedido::factory(),
            'articulo_id' => Articulo::factory(),
            'presentacion_articulo_id' => fn (array $atributos) => PresentacionArticulo::factory()->create([
                'articulo_id' => $atributos['articulo_id'],
            ])->id,
            'cantidad_solicitada_detalle_pedido' => fake()->randomFloat(3, 1, 20),
            'equivalencia_base_aplicada_detalle_pedido' => 1,
            'observaciones_detalle_pedido' => fake()->optional()->sentence(),
            'preparado_detalle_pedido' => false,
        ];
    }
}
