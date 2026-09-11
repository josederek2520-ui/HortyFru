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
        Schema::create('recepciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_recepcion', 30)->nullable()->unique();
            $table->foreignId('compra_id')->nullable()->constrained('compras')->restrictOnDelete();
            $table->foreignId('almacen_id')->constrained('almacenes')->restrictOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->dateTime('fecha_recepcion');
            $table->enum('estado_recepcion', ['BORRADOR', 'CONFIRMADA', 'CANCELADA'])
                ->default('BORRADOR')
                ->index();
            $table->text('observaciones_recepcion')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('confirmado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('confirmado_en')->nullable();
            $table->foreignId('cancelado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelado_en')->nullable();
            $table->string('motivo_cancelacion_recepcion', 500)->nullable();
            $table->timestamps();

            $table->index(['fecha_recepcion', 'estado_recepcion'], 'recepciones_fecha_estado_index');
            $table->index(['compra_id', 'fecha_recepcion'], 'recepciones_compra_fecha_index');
            $table->index(['almacen_id', 'fecha_recepcion'], 'recepciones_almacen_fecha_index');
            $table->index(['empleado_id', 'fecha_recepcion'], 'recepciones_empleado_fecha_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recepciones');
    }
};
