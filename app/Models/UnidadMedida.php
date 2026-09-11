<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use Database\Factories\UnidadMedidaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UnidadMedida extends Model
{
    /** @use HasFactory<UnidadMedidaFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'unidades_medida';

    /** @var list<string> */
    protected $fillable = [
        'nombre_unidad_medida',
        'abreviatura_unidad_medida',
        'estado_unidad_medida',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'estado_unidad_medida' => true,
    ];

    public function detallesCompra(): HasMany
    {
        return $this->hasMany(DetalleCompra::class);
    }

    public function detallesRecepcion(): HasMany
    {
        return $this->hasMany(DetalleRecepcion::class);
    }

    public function detallesMovimientoInventario(): HasMany
    {
        return $this->hasMany(DetalleMovimientoInventario::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::MeasurementUnits->value)
            ->logOnly([
                'nombre_unidad_medida',
                'abreviatura_unidad_medida',
                'estado_unidad_medida',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Unidad de medida registrada',
                'updated' => 'Unidad de medida actualizada',
                default => 'Actividad de unidad de medida',
            });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'estado_unidad_medida' => 'boolean',
        ];
    }
}
