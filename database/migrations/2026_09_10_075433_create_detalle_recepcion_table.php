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
        Schema::create('detalle_recepcion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_id')->constrained('recepciones')->cascadeOnDelete();
            $table->foreignId('detalle_compra_id')->nullable()->constrained('detalles_compras')->restrictOnDelete();
            $table->foreignId('articulo_id')->constrained('articulos')->restrictOnDelete();
            $table->foreignId('presentacion_articulo_id')->nullable()->constrained('presentaciones_articulos')->restrictOnDelete();
            $table->decimal('cantidad_presentaciones_detalle_recepcion', 12, 3)->nullable();
            $table->decimal('equivalencia_base_aplicada_detalle_recepcion', 12, 3)->nullable();
            $table->decimal('cantidad_base_detalle_recepcion', 14, 3);
            $table->foreignId('unidad_medida_id')->constrained('unidades_medida')->restrictOnDelete();
            $table->date('fecha_vencimiento_detalle_recepcion')->nullable();
            $table->string('calidad_detalle_recepcion', 100)->nullable();
            $table->foreignId('lote_id')->nullable()->unique()->constrained('lotes')->restrictOnDelete();
            $table->string('observaciones_detalle_recepcion', 500)->nullable();
            $table->timestamps();

            $table->unique(['recepcion_id', 'detalle_compra_id'], 'detalle_recepcion_compra_unique');
            $table->unique(
                ['recepcion_id', 'articulo_id', 'presentacion_articulo_id'],
                'detalle_recepcion_articulo_presentacion_unique',
            );
            $table->index(
                ['recepcion_id', 'articulo_id'],
                'detalle_recepcion_recepcion_articulo_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_recepcion');
    }
};
