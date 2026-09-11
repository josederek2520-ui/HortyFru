<?php

namespace Database\Seeders;

use App\Models\DetalleMovimientoInventario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DetalleMovimientoInventarioSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DetalleMovimientoInventario::factory()->count(10)->create();
    }
}
