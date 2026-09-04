<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use Database\Factories\VehiculoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vehiculo extends Model
{
    /** @use HasFactory<VehiculoFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'vehiculos';

    /** @var list<string> */
    protected $fillable = [
        'placa_vehiculo',
        'marca_vehiculo',
        'tipo_vehiculo',
        'estado_vehiculo',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'estado_vehiculo' => true,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::Vehicles->value)
            ->logOnly([
                'placa_vehiculo',
                'marca_vehiculo',
                'tipo_vehiculo',
                'estado_vehiculo',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Vehículo registrado',
                'updated' => 'Vehículo actualizado',
                default => 'Actividad de vehículo',
            });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'estado_vehiculo' => 'boolean',
        ];
    }
}
