<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use Database\Factories\SucursalFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Sucursal extends Model
{
    /** @use HasFactory<SucursalFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'sucursales';

    /** @var list<string> */
    protected $fillable = [
        'cliente_id',
        'nombre_sucursal',
        'direccion_sucursal',
        'telefono_sucursal',
        'referencia_sucursal',
        'activo_sucursal',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'activo_sucursal' => true,
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::Branches->value)
            ->logOnly([
                'cliente_id',
                'nombre_sucursal',
                'direccion_sucursal',
                'telefono_sucursal',
                'referencia_sucursal',
                'activo_sucursal',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Sucursal registrada',
                'updated' => 'Sucursal actualizada',
                default => 'Actividad de sucursal',
            });
    }

    #[Scope]
    protected function availableForOperations(Builder $query): Builder
    {
        return $query
            ->where('activo_sucursal', true)
            ->whereHas('cliente', fn (Builder $query): Builder => $query->where('activo_cliente', true));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'activo_sucursal' => 'boolean',
        ];
    }
}
