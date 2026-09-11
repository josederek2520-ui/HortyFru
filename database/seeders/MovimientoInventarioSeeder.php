<?php

namespace Database\Seeders;

use App\Models\MovimientoInventario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MovimientoInventarioSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MovimientoInventario::factory()->count(10)->create();
    }
}
