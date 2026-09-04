<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ActivityLogName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'activo_usuario',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'activo_usuario' => true,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function empleado(): HasOne
    {
        return $this->hasOne(Empleado::class);
    }

    public function pedidosRegistrados(): HasMany
    {
        return $this->hasMany(Pedido::class, 'registrado_por');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::Users->value)
            ->logOnly(['email', 'activo_usuario'])
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['ultimo_acceso_usuario'])
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Cuenta de usuario creada',
                'updated' => 'Cuenta de usuario actualizada',
                'deleted' => 'Cuenta de usuario eliminada',
                default => 'Actividad de usuario',
            });
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->empleado?->nombre_completo
                ?? $this->name
                ?? $this->email,
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo_usuario' => 'boolean',
            'ultimo_acceso_usuario' => 'datetime',
        ];
    }
}
