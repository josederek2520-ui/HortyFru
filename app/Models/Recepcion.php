<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use App\Enums\EstadoRecepcion;
use Database\Factories\RecepcionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Recepcion extends Model
{
    /** @use HasFactory<RecepcionFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'recepciones';

    /** @var list<string> */
    protected $fillable = [
        'codigo_recepcion',
        'compra_id',
        'almacen_id',
        'empleado_id',
        'fecha_recepcion',
        'estado_recepcion',
        'observaciones_recepcion',
        'registrado_por',
        'confirmado_por',
        'confirmado_en',
        'cancelado_por',
        'cancelado_en',
        'motivo_cancelacion_recepcion',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'estado_recepcion' => EstadoRecepcion::Borrador->value,
    ];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function confirmadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmado_por');
    }

    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleRecepcion::class);
    }

    public function estaEnBorrador(): bool
    {
        return $this->estado_recepcion === EstadoRecepcion::Borrador;
    }

    public function sePuedeCancelar(): bool
    {
        return $this->estaEnBorrador();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::Receptions->value)
            ->logOnly([
                'codigo_recepcion',
                'compra_id',
                'almacen_id',
                'empleado_id',
                'fecha_recepcion',
                'estado_recepcion',
                'observaciones_recepcion',
                'registrado_por',
                'confirmado_por',
                'confirmado_en',
                'cancelado_por',
                'cancelado_en',
                'motivo_cancelacion_recepcion',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Recepción registrada como borrador',
                'updated' => 'Recepción actualizada',
                default => 'Actividad de recepción',
            });
    }

    protected function fechaRecepcionLocal(): Attribute
    {
        return Attribute::get(
            fn () => $this->fecha_recepcion?->copy()->setTimezone(
                (string) config('app.display_timezone', 'America/La_Paz'),
            ),
        );
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'fecha_recepcion' => 'datetime',
            'estado_recepcion' => EstadoRecepcion::class,
            'confirmado_en' => 'datetime',
            'cancelado_en' => 'datetime',
        ];
    }
}
