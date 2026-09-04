<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use App\Enums\EstadoPedido;
use Database\Factories\PedidoFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pedido extends Model
{
    /** @use HasFactory<PedidoFactory> */
    use HasFactory, LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'codigo_pedido', 'sucursal_id', 'fecha_pedido', 'fecha_requerida_pedido',
        'estado_pedido', 'observaciones_pedido', 'registrado_por', 'preparado_por',
        'preparado_en', 'cancelado_por', 'cancelado_en', 'motivo_cancelacion_pedido',
    ];

    /** @var array<string, mixed> */
    protected $attributes = ['estado_pedido' => EstadoPedido::Pendiente->value];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function preparadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'preparado_por');
    }

    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }

    protected function fechaPedidoLocal(): Attribute
    {
        return Attribute::get(
            fn () => $this->fecha_pedido?->copy()->setTimezone(
                (string) config('app.display_timezone', 'America/La_Paz')
            ),
        );
    }

    public function estaPendiente(): bool
    {
        return $this->estado_pedido === EstadoPedido::Pendiente;
    }

    public function preparacionIniciada(): bool
    {
        if (array_key_exists('detalles_preparados_count', $this->attributes)) {
            return (int) $this->attributes['detalles_preparados_count'] > 0;
        }

        if ($this->relationLoaded('detalles')) {
            return $this->detalles->contains('preparado_detalle_pedido', true);
        }

        return $this->detalles()->where('preparado_detalle_pedido', true)->exists();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::Orders->value)
            ->logOnly([
                'codigo_pedido', 'sucursal_id', 'fecha_pedido', 'fecha_requerida_pedido',
                'estado_pedido', 'observaciones_pedido', 'registrado_por', 'preparado_por',
                'preparado_en', 'cancelado_por', 'cancelado_en', 'motivo_cancelacion_pedido',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Pedido registrado',
                'updated' => 'Pedido actualizado',
                default => 'Actividad de pedido',
            });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'fecha_pedido' => 'datetime',
            'fecha_requerida_pedido' => 'date',
            'estado_pedido' => EstadoPedido::class,
            'preparado_en' => 'datetime',
            'cancelado_en' => 'datetime',
        ];
    }
}
