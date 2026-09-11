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
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_movimiento_inventario', 50)->unique();
            $table->foreignId('almacen_id')->constrained('almacenes')->restrictOnDelete();
            $table->enum('tipo_movimiento_inventario', [
                'ENTRADA_RECEPCION',
                'SALIDA_PEDIDO',
                'SALIDA_PRODUCCION',
                'ENTRADA_PRODUCCION',
                'SALIDA_MERMA',
                'DEVOLUCION',
                'AJUSTE_POSITIVO',
                'AJUSTE_NEGATIVO',
            ]);
            $table->dateTime('fecha_movimiento_inventario');
            $table->enum('estado_movimiento_inventario', ['REGISTRADO', 'ANULADO'])
                ->default('REGISTRADO');
            $table->enum('tipo_referencia_movimiento_inventario', [
                'RECEPCION',
                'PEDIDO',
                'PRODUCCION',
                'MERMA',
                'DEVOLUCION',
                'AJUSTE',
            ])->nullable();
            $table->unsignedBigInteger('referencia_id_movimiento_inventario')->nullable();
            $table->text('observaciones_movimiento_inventario')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(
                ['fecha_movimiento_inventario', 'tipo_movimiento_inventario'],
                'movimientos_inv_fecha_tipo_index',
            );
            $table->index(
                ['almacen_id', 'estado_movimiento_inventario', 'fecha_movimiento_inventario'],
                'movimientos_inv_almacen_estado_fecha_index',
            );
            $table->unique(
                [
                    'tipo_movimiento_inventario',
                    'tipo_referencia_movimiento_inventario',
                    'referencia_id_movimiento_inventario',
                    'almacen_id',
                ],
                'movimientos_inv_referencia_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
