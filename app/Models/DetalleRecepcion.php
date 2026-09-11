<?php

namespace App\Models;

use Database\Factories\DetalleRecepcionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleRecepcion extends Model
{
    /** @use HasFactory<DetalleRecepcionFactory> */
    use HasFactory;

    protected $table = 'detalle_recepcion';

    /** @var list<string> */
    protected $fillable = [
        'detalle_compra_id',
        'articulo_id',
        'presentacion_articulo_id',
        'cantidad_presentaciones_detalle_recepcion',
        'equivalencia_base_aplicada_detalle_recepcion',
        'cantidad_base_detalle_recepcion',
        'unidad_medida_id',
        'fecha_vencimiento_detalle_recepcion',
        'calidad_detalle_recepcion',
        'lote_id',
        'observaciones_detalle_recepcion',
    ];

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(Recepcion::class);
    }

    public function detalleCompra(): BelongsTo
    {
        return $this->belongsTo(DetalleCompra::class);
    }

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }

    public function presentacionArticulo(): BelongsTo
    {
        return $this->belongsTo(PresentacionArticulo::class);
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'cantidad_presentaciones_detalle_recepcion' => 'decimal:3',
            'equivalencia_base_aplicada_detalle_recepcion' => 'decimal:3',
            'cantidad_base_detalle_recepcion' => 'decimal:3',
            'fecha_vencimiento_detalle_recepcion' => 'date',
        ];
    }
}
