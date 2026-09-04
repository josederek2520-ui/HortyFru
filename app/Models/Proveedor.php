<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use Database\Factories\ProveedorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Proveedor extends Model
{
    /** @use HasFactory<ProveedorFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'proveedores';

    /** @var list<string> */
    protected $fillable = [
        'nombre_proveedor',
        'telefono_proveedor',
        'mercado_proveedor',
        'direccion_proveedor',
        'observacion_proveedor',
        'estado_proveedor',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'estado_proveedor' => true,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::Providers->value)
            ->logOnly([
                'nombre_proveedor',
                'telefono_proveedor',
                'mercado_proveedor',
                'direccion_proveedor',
                'observacion_proveedor',
                'estado_proveedor',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Proveedor registrado',
                'updated' => 'Proveedor actualizado',
                default => 'Actividad de proveedor',
            });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'estado_proveedor' => 'boolean',
        ];
    }
}
