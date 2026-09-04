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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_pedido', 30)->nullable()->unique();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->dateTime('fecha_pedido');
            $table->date('fecha_requerida_pedido');
            $table->enum('estado_pedido', ['PENDIENTE', 'PREPARADO', 'CANCELADO'])
                ->default('PENDIENTE')
                ->index();
            $table->text('observaciones_pedido')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('preparado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('preparado_en')->nullable();
            $table->foreignId('cancelado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelado_en')->nullable();
            $table->string('motivo_cancelacion_pedido', 500)->nullable();
            $table->timestamps();

            $table->index(['fecha_requerida_pedido', 'estado_pedido'], 'pedidos_fecha_estado_index');
            $table->index(['sucursal_id', 'fecha_pedido'], 'pedidos_sucursal_fecha_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
