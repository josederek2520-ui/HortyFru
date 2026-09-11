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
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_compra', 30)->nullable()->unique();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->dateTime('fecha_compra');
            $table->enum('estado_compra', ['BORRADOR', 'REGISTRADA', 'CANCELADA'])
                ->default('BORRADOR')
                ->index();
            $table->decimal('total_compra', 14, 2)->default(0);
            $table->text('observaciones_compra')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('cancelado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelado_en')->nullable();
            $table->string('motivo_cancelacion_compra', 500)->nullable();
            $table->timestamps();

            $table->index(['fecha_compra', 'estado_compra'], 'compras_fecha_estado_index');
            $table->index(['proveedor_id', 'fecha_compra'], 'compras_proveedor_fecha_index');
            $table->index(['empleado_id', 'fecha_compra'], 'compras_empleado_fecha_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
