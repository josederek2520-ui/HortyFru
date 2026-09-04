<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use Database\Factories\EmpleadoFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Empleado extends Model
{
    /** @use HasFactory<EmpleadoFactory> */
    use HasFactory, LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'nombre_empleado',
        'apellido_empleado',
        'ci_empleado',
        'telefono_empleado',
        'direccion_empleado',
        'cargo_empleado',
        'fecha_ingreso_empleado',
        'activo_empleado',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'activo_empleado' => true,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::Employees->value)
            ->logOnly([
                'user_id',
                'nombre_empleado',
                'apellido_empleado',
                'ci_empleado',
                'telefono_empleado',
                'direccion_empleado',
                'cargo_empleado',
                'fecha_ingreso_empleado',
                'activo_empleado',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Empleado registrado',
                'updated' => 'Empleado actualizado',
                'deleted' => 'Empleado eliminado',
                default => 'Actividad de empleado',
            });
    }

    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(
            fn (): string => "{$this->nombre_empleado} {$this->apellido_empleado}",
        );
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'fecha_ingreso_empleado' => 'date',
            'activo_empleado' => 'boolean',
        ];
    }
}
