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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activo_usuario')->default(true)->index()->after('password');
            $table->dateTime('ultimo_acceso_usuario')->nullable()->after('activo_usuario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['activo_usuario']);
            $table->dropColumn(['activo_usuario', 'ultimo_acceso_usuario']);
        });
    }
};
