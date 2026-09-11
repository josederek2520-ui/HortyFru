<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use Database\Factories\DetalleMovimientoInventarioFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DetalleMovimientoInventario extends Model
{
    /** @use HasFactory<DetalleMovimientoInventarioFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'detalle_movimiento_inventario';

    /** @var list<string> */
    protected $fillable = [
        'movimiento_inventario_id',
        'articulo_id',
        'lote_id',
        'unidad_medida_id',
        'cantidad_movimiento_inventario',
    ];

    public function movimientoInventario(): BelongsTo
    {
        return $this->belongsTo(MovimientoInventario::class);
    }

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::InventoryMovements->value)
            ->logOnly([
                'movimiento_inventario_id',
                'articulo_id',
                'lote_id',
                'unidad_medida_id',
                'cantidad_movimiento_inventario',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Detalle de inventario registrado',
                default => 'Actividad del detalle de inventario',
            });
    }

    protected function cantidadConSigno(): Attribute
    {
        return Attribute::get(function (): string {
            $signo = $this->movimientoInventario?->tipo_movimiento_inventario?->signo() ?? 1;

            return ($signo < 0 ? '-' : '+').number_format(
                (float) $this->cantidad_movimiento_inventario,
                3,
                '.',
                '',
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'cantidad_movimiento_inventario' => 'decimal:3',
        ];
    }
}
