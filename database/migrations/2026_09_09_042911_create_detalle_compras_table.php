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
        Schema::create('detalles_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('articulo_id')->constrained('articulos')->restrictOnDelete();
            $table->foreignId('presentacion_articulo_id')->nullable()->constrained('presentaciones_articulos')->restrictOnDelete();
            $table->decimal('cantidad_presentaciones_detalle_compra', 12, 3)->nullable();
            $table->decimal('equivalencia_base_aplicada_detalle_compra', 12, 3)->nullable();
            $table->decimal('cantidad_real_detalle_compra', 14, 3)->nullable();
            $table->foreignId('unidad_medida_id')->nullable()->constrained('unidades_medida')->restrictOnDelete();
            $table->decimal('precio_unitario_detalle_compra', 14, 2);
            $table->enum('precio_por_detalle_compra', ['PRESENTACION', 'UNIDAD_BASE']);
            $table->decimal('subtotal_detalle_compra', 14, 2);
            $table->string('observaciones_detalle_compra', 500)->nullable();
            $table->timestamps();

            $table->unique(
                ['compra_id', 'articulo_id', 'presentacion_articulo_id'],
                'detalles_compras_articulo_presentacion_unique',
            );
            $table->index(['compra_id', 'articulo_id'], 'detalles_compras_compra_articulo_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_compras');
    }
};
