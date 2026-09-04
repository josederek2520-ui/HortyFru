<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory, LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'razon_social',
        'nit',
        'telefono_cliente',
        'email_cliente',
        'activo_cliente',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'activo_cliente' => true,
    ];

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::Clients->value)
            ->logOnly([
                'razon_social',
                'nit',
                'telefono_cliente',
                'email_cliente',
                'activo_cliente',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Cliente registrado',
                'updated' => 'Cliente actualizado',
                default => 'Actividad de cliente',
            });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'activo_cliente' => 'boolean',
        ];
    }
}
