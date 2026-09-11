<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use Database\Factories\AlmacenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Almacen extends Model
{
    /** @use HasFactory<AlmacenFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'almacenes';

    /** @var list<string> */
    protected $fillable = [
        'nombre_almacen',
        'direccion_almacen',
        'estado_almacen',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'estado_almacen' => true,
    ];

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function recepciones(): HasMany
    {
        return $this->hasMany(Recepcion::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::Warehouses->value)
            ->logOnly([
                'nombre_almacen',
                'direccion_almacen',
                'estado_almacen',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Almacén registrado',
                'updated' => 'Almacén actualizado',
                default => 'Actividad de almacén',
            });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'estado_almacen' => 'boolean',
        ];
    }
}
