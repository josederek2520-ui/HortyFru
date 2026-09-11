<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('detalle_movimiento_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimiento_inventario_id')
                ->constrained('movimientos_inventario')
                ->restrictOnDelete();
            $table->foreignId('articulo_id')->constrained('articulos')->restrictOnDelete();
            $table->foreignId('lote_id')->constrained('lotes')->restrictOnDelete();
            $table->foreignId('unidad_medida_id')->constrained('unidades_medida')->restrictOnDelete();
            $table->decimal('cantidad_movimiento_inventario', 14, 3);
            $table->timestamps();

            $table->unique(
                ['movimiento_inventario_id', 'lote_id'],
                'detalle_mov_inv_movimiento_lote_unique',
            );
            $table->index(
                ['lote_id', 'articulo_id'],
                'detalle_mov_inv_lote_articulo_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_movimiento_inventario');
    }
};
