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
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('nombre_empleado', 100);
            $table->string('apellido_empleado', 100);
            $table->string('ci_empleado', 30)->unique();
            $table->string('telefono_empleado', 30)->nullable();
            $table->string('direccion_empleado')->nullable();
            $table->string('cargo_empleado', 100)->index();
            $table->date('fecha_ingreso_empleado')->nullable();
            $table->boolean('activo_empleado')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
