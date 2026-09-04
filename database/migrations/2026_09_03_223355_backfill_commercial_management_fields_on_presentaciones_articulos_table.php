<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('presentaciones_articulos')
            ->whereNotNull('equivalencia_base_presentacion_articulo')
            ->update(['tipo_equivalencia_presentacion_articulo' => 'FIJA']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No requiere reversión: la migración estructural posterior elimina este campo.
    }
};
