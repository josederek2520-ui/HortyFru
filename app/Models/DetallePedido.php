<?php

namespace App\Models;

use Database\Factories\DetallePedidoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetallePedido extends Model
{
    /** @use HasFactory<DetallePedidoFactory> */
    use HasFactory;

    protected $table = 'detalles_pedidos';

    /** @var list<string> */
    protected $fillable = [
        'articulo_id', 'presentacion_articulo_id', 'cantidad_solicitada_detalle_pedido',
        'equivalencia_base_aplicada_detalle_pedido', 'observaciones_detalle_pedido',
        'preparado_detalle_pedido', 'preparado_por', 'preparado_en',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }

    public function presentacionArticulo(): BelongsTo
    {
        return $this->belongsTo(PresentacionArticulo::class);
    }

    public function preparadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'preparado_por');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'cantidad_solicitada_detalle_pedido' => 'decimal:3',
            'equivalencia_base_aplicada_detalle_pedido' => 'decimal:3',
            'preparado_detalle_pedido' => 'boolean',
            'preparado_en' => 'datetime',
        ];
    }
}
