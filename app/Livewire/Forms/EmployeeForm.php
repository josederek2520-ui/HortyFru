<?php

namespace App\Livewire\Forms;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

class EmployeeForm extends Form
{
    public ?int $user_id = null;

    public string $nombre_empleado = '';

    public string $apellido_empleado = '';

    public string $ci_empleado = '';

    public string $telefono_empleado = '';

    public string $direccion_empleado = '';

    public string $cargo_empleado = '';

    public string $fecha_ingreso_empleado = '';

    public bool $activo_empleado = true;

    /** @return array<string, mixed> */
    public function validateForCreate(): array
    {
        return $this->validatedData();
    }

    /** @return array<string, mixed> */
    public function validateForUpdate(Empleado $empleado): array
    {
        return $this->validatedData($empleado);
    }

    public function fillFrom(Empleado $empleado): void
    {
        $this->user_id = $empleado->user_id;
        $this->nombre_empleado = $empleado->nombre_empleado;
        $this->apellido_empleado = $empleado->apellido_empleado;
        $this->ci_empleado = $empleado->ci_empleado;
        $this->telefono_empleado = $empleado->telefono_empleado ?? '';
        $this->direccion_empleado = $empleado->direccion_empleado ?? '';
        $this->cargo_empleado = $empleado->cargo_empleado;
        $this->fecha_ingreso_empleado = $empleado->fecha_ingreso_empleado?->format('Y-m-d') ?? '';
        $this->activo_empleado = $empleado->activo_empleado;
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    private function validatedData(?Empleado $empleado = null): array
    {
        $this->normalize();

        $validated = $this->validate([
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists(User::class, 'id'),
                Rule::unique(Empleado::class, 'user_id')->ignore($empleado),
            ],
            'nombre_empleado' => ['required', 'string', 'max:100'],
            'apellido_empleado' => ['required', 'string', 'max:100'],
            'ci_empleado' => [
                'required',
                'string',
                'max:30',
                Rule::unique(Empleado::class, 'ci_empleado')->ignore($empleado),
            ],
            'telefono_empleado' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'direccion_empleado' => ['nullable', 'string', 'max:255'],
            'cargo_empleado' => ['required', 'string', 'max:100'],
            'fecha_ingreso_empleado' => ['nullable', 'date', 'before_or_equal:today'],
            'activo_empleado' => ['required', 'boolean'],
        ], $this->messages());

        foreach (['telefono_empleado', 'direccion_empleado', 'fecha_ingreso_empleado'] as $nullableField) {
            $validated[$nullableField] = $validated[$nullableField] === '' ? null : $validated[$nullableField];
        }

        return $validated;
    }

    private function normalize(): void
    {
        $this->nombre_empleado = Str::squish($this->nombre_empleado);
        $this->apellido_empleado = Str::squish($this->apellido_empleado);
        $this->ci_empleado = Str::upper(Str::squish($this->ci_empleado));
        $this->telefono_empleado = trim($this->telefono_empleado);
        $this->direccion_empleado = Str::squish($this->direccion_empleado);
        $this->cargo_empleado = Str::squish($this->cargo_empleado);
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'user_id.exists' => 'La cuenta seleccionada ya no existe.',
            'user_id.unique' => 'Esta cuenta ya está asociada con otro empleado.',
            'nombre_empleado.required' => 'Ingresa el nombre del empleado.',
            'apellido_empleado.required' => 'Ingresa el apellido del empleado.',
            'ci_empleado.required' => 'Ingresa el carnet de identidad.',
            'ci_empleado.unique' => 'Este carnet de identidad ya está registrado.',
            'telefono_empleado.regex' => 'El teléfono contiene caracteres no permitidos.',
            'cargo_empleado.required' => 'Ingresa el cargo del empleado.',
            'fecha_ingreso_empleado.date' => 'Ingresa una fecha de ingreso válida.',
            'fecha_ingreso_empleado.before_or_equal' => 'La fecha de ingreso no puede estar en el futuro.',
        ];
    }
}
