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
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_lote', 50)->unique();
            $table->foreignId('articulo_id')->constrained('articulos')->restrictOnDelete();
            $table->enum('tipo_origen_lote', ['RECEPCION', 'PRODUCCION', 'DEVOLUCION', 'AJUSTE']);
            $table->dateTime('fecha_ingreso_lote');
            $table->date('fecha_vencimiento_lote')->nullable();
            $table->string('calidad_lote', 100)->nullable();
            $table->enum('estado_lote', ['DISPONIBLE', 'BLOQUEADO', 'AGOTADO'])
                ->default('DISPONIBLE');
            $table->string('motivo_bloqueo_lote', 500)->nullable();
            $table->text('observaciones_lote')->nullable();
            $table->timestamps();

            $table->index(
                ['articulo_id', 'estado_lote', 'fecha_vencimiento_lote'],
                'lotes_articulo_estado_vencimiento_index',
            );
            $table->index(
                ['tipo_origen_lote', 'fecha_ingreso_lote'],
                'lotes_origen_fecha_ingreso_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
