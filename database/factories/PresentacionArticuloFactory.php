<?php

namespace Database\Factories;

use App\Enums\TipoEquivalenciaPresentacionArticulo;
use App\Enums\UsoPresentacionArticulo;
use App\Models\Articulo;
use App\Models\PresentacionArticulo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PresentacionArticulo>
 */
class PresentacionArticuloFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'articulo_id' => Articulo::factory(),
            'nombre_presentacion_articulo' => fake()->unique()->words(2, true),
            'uso_presentacion_articulo' => UsoPresentacionArticulo::Ambos,
            'tipo_equivalencia_presentacion_articulo' => TipoEquivalenciaPresentacionArticulo::Fija,
            'equivalencia_base_presentacion_articulo' => fake()->randomFloat(3, 0.001, 999),
            'permite_fraccion_presentacion_articulo' => false,
            'predeterminada_pedido_presentacion_articulo' => false,
            'predeterminada_compra_presentacion_articulo' => false,
            'estado_presentacion_articulo' => true,
        ];
    }
}
