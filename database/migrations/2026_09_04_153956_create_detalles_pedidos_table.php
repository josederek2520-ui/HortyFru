<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalles_pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('articulo_id')->constrained('articulos')->restrictOnDelete();
            $table->foreignId('presentacion_articulo_id')->constrained('presentaciones_articulos')->restrictOnDelete();
            $table->decimal('cantidad_solicitada_detalle_pedido', 12, 3);
            $table->decimal('equivalencia_base_aplicada_detalle_pedido', 12, 3)->nullable();
            $table->string('observaciones_detalle_pedido', 500)->nullable();
            $table->timestamps();

            $table->unique(
                ['pedido_id', 'articulo_id', 'presentacion_articulo_id'],
                'detalles_pedidos_articulo_presentacion_unique',
            );
            $table->index(['pedido_id', 'articulo_id'], 'detalles_pedidos_pedido_articulo_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalles_pedidos');
    }
};
