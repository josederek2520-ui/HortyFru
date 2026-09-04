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
        Schema::create('presentaciones_articulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articulo_id')->constrained('articulos')->restrictOnDelete();
            $table->string('nombre_presentacion_articulo', 100);
            $table->decimal('cantidad_equivalente_presentacion_articulo', 10, 3)->nullable();
            $table->boolean('estado_presentacion_articulo')->default(true)->index();
            $table->timestamps();

            $table->unique(
                ['articulo_id', 'nombre_presentacion_articulo'],
                'presentaciones_articulo_nombre_unique',
            );
            $table->index(
                ['articulo_id', 'estado_presentacion_articulo'],
                'presentaciones_articulo_estado_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presentaciones_articulos');
    }
};
