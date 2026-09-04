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
        Schema::table('presentaciones_articulos', function (Blueprint $table) {
            $table->renameColumn(
                'cantidad_equivalente_presentacion_articulo',
                'equivalencia_base_presentacion_articulo',
            );
            $table->enum('uso_presentacion_articulo', ['PEDIDO', 'COMPRA', 'AMBOS'])
                ->default('AMBOS')
                ->after('nombre_presentacion_articulo');
            $table->enum('tipo_equivalencia_presentacion_articulo', ['FIJA', 'APROXIMADA', 'VARIABLE'])
                ->default('VARIABLE')
                ->after('uso_presentacion_articulo');
            $table->boolean('permite_fraccion_presentacion_articulo')
                ->default(false)
                ->after('equivalencia_base_presentacion_articulo');
            $table->boolean('predeterminada_pedido_presentacion_articulo')
                ->default(false)
                ->after('permite_fraccion_presentacion_articulo');
            $table->boolean('predeterminada_compra_presentacion_articulo')
                ->default(false)
                ->after('predeterminada_pedido_presentacion_articulo');

            $table->index(
                ['articulo_id', 'uso_presentacion_articulo', 'estado_presentacion_articulo'],
                'presentaciones_articulo_uso_estado_index',
            );
        });

        Schema::table('presentaciones_articulos', function (Blueprint $table) {
            $table->decimal('equivalencia_base_presentacion_articulo', 12, 3)
                ->nullable()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presentaciones_articulos', function (Blueprint $table) {
            $table->dropIndex('presentaciones_articulo_uso_estado_index');
            $table->dropColumn([
                'uso_presentacion_articulo',
                'tipo_equivalencia_presentacion_articulo',
                'permite_fraccion_presentacion_articulo',
                'predeterminada_pedido_presentacion_articulo',
                'predeterminada_compra_presentacion_articulo',
            ]);
        });

        Schema::table('presentaciones_articulos', function (Blueprint $table) {
            $table->decimal('equivalencia_base_presentacion_articulo', 10, 3)
                ->nullable()
                ->change();
            $table->renameColumn(
                'equivalencia_base_presentacion_articulo',
                'cantidad_equivalente_presentacion_articulo',
            );
        });
    }
};
