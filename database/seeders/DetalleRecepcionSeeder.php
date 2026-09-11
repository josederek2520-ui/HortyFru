<?php

namespace Database\Seeders;

use App\Models\DetalleRecepcion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DetalleRecepcionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DetalleRecepcion::factory()->count(10)->create();
    }
}
