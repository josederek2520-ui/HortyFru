<?php

namespace App\Models;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity as BaseActivity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Activity extends BaseActivity
{
    protected static function booted(): void
    {
        static::creating(function (Activity $activity): void {
            if (! app()->bound('request')) {
                return;
            }

            $request = request();
            $properties = $activity->properties instanceof Collection
                ? $activity->properties
                : collect($activity->properties ?? []);

            $activity->properties = $properties->merge(array_filter([
                'ip_address' => $request->ip(),
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent() === null
                    ? null
                    : Str::limit($request->userAgent(), 500, ''),
            ], fn (mixed $value): bool => $value !== null && $value !== ''));
        });
    }

    protected function moduleLabel(): Attribute
    {
        return Attribute::get(
            fn (): string => ActivityLogName::tryFrom($this->log_name ?? '')?->label()
                ?? Str::headline($this->log_name ?? ActivityLogName::System->value),
        );
    }

    protected function localCreatedAt(): Attribute
    {
        return Attribute::get(
            fn () => $this->created_at?->copy()->setTimezone(
                (string) config('app.display_timezone', 'America/La_Paz')
            ),
        );
    }

    protected function eventLabel(): Attribute
    {
        return Attribute::get(
            fn (): string => ActivityEvent::tryFrom($this->event ?? '')?->label()
                ?? Str::headline($this->event ?? 'evento'),
        );
    }

    protected function causerName(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->causer instanceof User
                ? $this->causer->display_name
                : 'Sistema',
        );
    }

    protected function subjectLabel(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->subject_type === null || $this->subject_id === null) {
                return 'Sin registro asociado';
            }

            $subjectName = match ($this->subject_type) {
                User::class => 'Usuario',
                Empleado::class => 'Empleado',
                Cliente::class => 'Cliente',
                Sucursal::class => 'Sucursal',
                Proveedor::class => 'Proveedor',
                Vehiculo::class => 'Vehículo',
                Almacen::class => 'Almacén',
                CategoriaArticulo::class => 'Categoría de artículo',
                UnidadMedida::class => 'Unidad de medida',
                Articulo::class => 'Artículo',
                PresentacionArticulo::class => 'Presentación de artículo',
                Role::class => 'Rol',
                Permission::class => 'Permiso',
                default => 'Registro',
            };

            return $subjectName.' #'.$this->subject_id;
        });
    }
}
