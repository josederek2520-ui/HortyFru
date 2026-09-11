<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use App\Enums\EstadoCompra;
use Database\Factories\CompraFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Compra extends Model
{
    /** @use HasFactory<CompraFactory> */
    use HasFactory, LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'codigo_compra',
        'proveedor_id',
        'empleado_id',
        'fecha_compra',
        'estado_compra',
        'total_compra',
        'observaciones_compra',
        'registrado_por',
        'cancelado_por',
        'cancelado_en',
        'motivo_cancelacion_compra',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'estado_compra' => EstadoCompra::Borrador->value,
        'total_compra' => 0,
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCompra::class);
    }

    public function recepciones(): HasMany
    {
        return $this->hasMany(Recepcion::class);
    }

    public function estaEnBorrador(): bool
    {
        return $this->estado_compra === EstadoCompra::Borrador;
    }

    public function sePuedeCancelar(): bool
    {
        return in_array($this->estado_compra, [EstadoCompra::Borrador, EstadoCompra::Registrada], true);
    }

    protected function fechaCompraLocal(): Attribute
    {
        return Attribute::get(
            fn () => $this->fecha_compra?->copy()->setTimezone(
                (string) config('app.display_timezone', 'America/La_Paz')
            ),
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::Purchases->value)
            ->logOnly([
                'codigo_compra',
                'proveedor_id',
                'empleado_id',
                'fecha_compra',
                'estado_compra',
                'total_compra',
                'observaciones_compra',
                'registrado_por',
                'cancelado_por',
                'cancelado_en',
                'motivo_cancelacion_compra',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Compra registrada como borrador',
                'updated' => 'Compra actualizada',
                default => 'Actividad de compra',
            });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'fecha_compra' => 'datetime',
            'estado_compra' => EstadoCompra::class,
            'total_compra' => 'decimal:2',
            'cancelado_en' => 'datetime',
        ];
    }
}
