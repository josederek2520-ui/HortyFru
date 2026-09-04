<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->string('nombre_sucursal', 150)->index();
            $table->string('direccion_sucursal');
            $table->string('telefono_sucursal', 30)->nullable();
            $table->string('referencia_sucursal')->nullable();
            $table->boolean('activo_sucursal')->default(true)->index();
            $table->timestamps();

            $table->unique(['cliente_id', 'nombre_sucursal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};
