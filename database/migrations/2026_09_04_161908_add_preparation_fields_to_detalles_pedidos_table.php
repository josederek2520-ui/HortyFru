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
        Schema::table('detalles_pedidos', function (Blueprint $table) {
            $table->boolean('preparado_detalle_pedido')->default(false)->index();
            $table->foreignId('preparado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('preparado_en')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalles_pedidos', function (Blueprint $table) {
            $table->dropForeign(['preparado_por']);
            $table->dropColumn(['preparado_detalle_pedido', 'preparado_por', 'preparado_en']);
        });
    }
};
