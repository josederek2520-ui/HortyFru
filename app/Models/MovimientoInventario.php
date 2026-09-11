<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use App\Enums\EstadoMovimientoInventario;
use App\Enums\TipoMovimientoInventario;
use App\Enums\TipoReferenciaMovimientoInventario;
use Database\Factories\MovimientoInventarioFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MovimientoInventario extends Model
{
    /** @use HasFactory<MovimientoInventarioFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'movimientos_inventario';

    /** @var list<string> */
    protected $fillable = [
        'codigo_movimiento_inventario',
        'almacen_id',
        'tipo_movimiento_inventario',
        'fecha_movimiento_inventario',
        'estado_movimiento_inventario',
        'tipo_referencia_movimiento_inventario',
        'referencia_id_movimiento_inventario',
        'observaciones_movimiento_inventario',
        'registrado_por',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'estado_movimiento_inventario' => EstadoMovimientoInventario::Registrado->value,
    ];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleMovimientoInventario::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::InventoryMovements->value)
            ->logOnly([
                'codigo_movimiento_inventario',
                'almacen_id',
                'tipo_movimiento_inventario',
                'fecha_movimiento_inventario',
                'estado_movimiento_inventario',
                'tipo_referencia_movimiento_inventario',
                'referencia_id_movimiento_inventario',
                'observaciones_movimiento_inventario',
                'registrado_por',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Movimiento de inventario registrado',
                'updated' => 'Movimiento de inventario actualizado',
                default => 'Actividad de inventario',
            });
    }

    protected function fechaMovimientoLocal(): Attribute
    {
        return Attribute::get(
            fn () => $this->fecha_movimiento_inventario?->copy()->setTimezone(
                (string) config('app.display_timezone', 'America/La_Paz'),
            ),
        );
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tipo_movimiento_inventario' => TipoMovimientoInventario::class,
            'fecha_movimiento_inventario' => 'datetime',
            'estado_movimiento_inventario' => EstadoMovimientoInventario::class,
            'tipo_referencia_movimiento_inventario' => TipoReferenciaMovimientoInventario::class,
        ];
    }
}
