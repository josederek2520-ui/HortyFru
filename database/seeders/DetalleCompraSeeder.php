<?php

namespace Database\Seeders;

use App\Models\Compra;
use App\Models\DetalleCompra;
use Illuminate\Database\Seeder;

class DetalleCompraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Compra::query()->where('estado_compra', 'BORRADOR')->each(function (Compra $compra): void {
            if (! $compra->detalles()->exists()) {
                DetalleCompra::factory()->for($compra)->create();
                $compra->update(['total_compra' => $compra->detalles()->sum('subtotal_detalle_compra')]);
            }
        });
    }
}
