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
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_proveedor', 150)->index();
            $table->string('telefono_proveedor', 30)->nullable();
            $table->string('mercado_proveedor', 150)->nullable();
            $table->string('direccion_proveedor')->nullable();
            $table->text('observacion_proveedor')->nullable();
            $table->boolean('estado_proveedor')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
