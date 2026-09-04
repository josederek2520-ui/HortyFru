<?php

namespace Database\Factories;

use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pedido>
 */
class PedidoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_pedido' => 'PED-'.fake()->unique()->numerify('########'),
            'sucursal_id' => Sucursal::factory(),
            'fecha_pedido' => now(),
            'fecha_requerida_pedido' => today()->addDay(),
            'estado_pedido' => EstadoPedido::Pendiente,
            'observaciones_pedido' => fake()->optional()->sentence(),
            'registrado_por' => User::factory(),
        ];
    }
}
