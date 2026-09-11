<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use App\Enums\EstadoLote;
use App\Enums\TipoOrigenLote;
use Database\Factories\LoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Lote extends Model
{
    /** @use HasFactory<LoteFactory> */
    use HasFactory, LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'codigo_lote',
        'articulo_id',
        'tipo_origen_lote',
        'fecha_ingreso_lote',
        'fecha_vencimiento_lote',
        'calidad_lote',
        'estado_lote',
        'motivo_bloqueo_lote',
        'observaciones_lote',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'estado_lote' => EstadoLote::Disponible->value,
    ];

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }

    public function detallesMovimientoInventario(): HasMany
    {
        return $this->hasMany(DetalleMovimientoInventario::class);
    }

    public function detalleRecepcion(): HasOne
    {
        return $this->hasOne(DetalleRecepcion::class);
    }

    public function estaVencido(): bool
    {
        return $this->fecha_vencimiento_lote !== null
            && $this->fecha_vencimiento_lote->toDateString() < today(
                (string) config('app.display_timezone', 'America/La_Paz'),
            )->toDateString();
    }

    public function esElegibleParaFefo(): bool
    {
        return $this->estado_lote === EstadoLote::Disponible && ! $this->estaVencido();
    }

    public function scopeEnOrdenFefo(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN fecha_vencimiento_lote IS NULL THEN 1 ELSE 0 END')
            ->orderBy('fecha_vencimiento_lote')
            ->orderBy('fecha_ingreso_lote')
            ->orderBy('id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::Lots->value)
            ->logOnly([
                'codigo_lote',
                'articulo_id',
                'tipo_origen_lote',
                'fecha_ingreso_lote',
                'fecha_vencimiento_lote',
                'calidad_lote',
                'estado_lote',
                'motivo_bloqueo_lote',
                'observaciones_lote',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Lote generado',
                'updated' => 'Información del lote actualizada',
                default => 'Actividad de lote',
            });
    }

    protected function fechaIngresoLocal(): Attribute
    {
        return Attribute::get(
            fn () => $this->fecha_ingreso_lote?->copy()->setTimezone(
                (string) config('app.display_timezone', 'America/La_Paz'),
            ),
        );
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tipo_origen_lote' => TipoOrigenLote::class,
            'fecha_ingreso_lote' => 'datetime',
            'fecha_vencimiento_lote' => 'date',
            'estado_lote' => EstadoLote::class,
        ];
    }
}
