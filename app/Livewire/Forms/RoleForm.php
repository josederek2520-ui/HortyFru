<?php

namespace App\Livewire\Forms;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleForm extends Form
{
    public string $name = '';

    /** @var list<int|string> */
    public array $permissions = [];

    /** @return array{name: string, permissions: list<int>} */
    public function validateForCreate(): array
    {
        return $this->validatedData();
    }

    /** @return array{name: string, permissions: list<int>} */
    public function validateForUpdate(Role $role): array
    {
        return $this->validatedData($role);
    }

    public function fillFrom(Role $role): void
    {
        $this->name = $role->name;
        $this->permissions = $role->permissions
            ->pluck('id')
            ->map(fn (int $permissionId): int => $permissionId)
            ->values()
            ->all();
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    /** @return array{name: string, permissions: list<int>} */
    private function validatedData(?Role $role = null): array
    {
        $this->name = Str::squish($this->name);
        $this->permissions = collect($this->permissions)
            ->map(fn (int|string $permissionId): int => (int) $permissionId)
            ->unique()
            ->values()
            ->all();

        /** @var array{name: string, permissions: list<int>} $validated */
        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:125',
                Rule::unique(Role::class, 'name')
                    ->where('guard_name', 'web')
                    ->ignore($role),
            ],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => [
                'integer',
                'distinct',
                Rule::exists(Permission::class, 'id')->where('guard_name', 'web'),
            ],
        ], [
            'name.required' => 'Ingresa un nombre para el rol.',
            'name.unique' => 'Ya existe un rol con este nombre.',
            'permissions.required' => 'Selecciona al menos un permiso.',
            'permissions.min' => 'Selecciona al menos un permiso.',
            'permissions.*.exists' => 'Uno de los permisos seleccionados ya no está disponible.',
        ]);

        return $validated;
    }
}
