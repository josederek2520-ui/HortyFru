<?php

namespace App\Models;

use App\Enums\PrecioPorDetalleCompra;
use Database\Factories\DetalleCompraFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetalleCompra extends Model
{
    /** @use HasFactory<DetalleCompraFactory> */
    use HasFactory;

    protected $table = 'detalles_compras';

    /** @var list<string> */
    protected $fillable = [
        'articulo_id',
        'presentacion_articulo_id',
        'cantidad_presentaciones_detalle_compra',
        'equivalencia_base_aplicada_detalle_compra',
        'cantidad_real_detalle_compra',
        'unidad_medida_id',
        'precio_unitario_detalle_compra',
        'precio_por_detalle_compra',
        'subtotal_detalle_compra',
        'observaciones_detalle_compra',
    ];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
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

    public function detallesRecepcion(): HasMany
    {
        return $this->hasMany(DetalleRecepcion::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'cantidad_presentaciones_detalle_compra' => 'decimal:3',
            'equivalencia_base_aplicada_detalle_compra' => 'decimal:3',
            'cantidad_real_detalle_compra' => 'decimal:3',
            'precio_unitario_detalle_compra' => 'decimal:2',
            'precio_por_detalle_compra' => PrecioPorDetalleCompra::class,
            'subtotal_detalle_compra' => 'decimal:2',
        ];
    }
}
