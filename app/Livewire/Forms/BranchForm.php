<?php

namespace App\Livewire\Forms;

use App\Models\Cliente;
use App\Models\Sucursal;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

class BranchForm extends Form
{
    public ?int $cliente_id = null;

    public string $nombre_sucursal = '';

    public string $direccion_sucursal = '';

    public string $telefono_sucursal = '';

    public string $referencia_sucursal = '';

    public bool $activo_sucursal = true;

    /** @return array<string, mixed> */
    public function validateForCreate(): array
    {
        return $this->validatedData();
    }

    /** @return array<string, mixed> */
    public function validateForUpdate(Sucursal $sucursal): array
    {
        return $this->validatedData($sucursal);
    }

    public function fillFrom(Sucursal $sucursal): void
    {
        $this->cliente_id = $sucursal->cliente_id;
        $this->nombre_sucursal = $sucursal->nombre_sucursal;
        $this->direccion_sucursal = $sucursal->direccion_sucursal;
        $this->telefono_sucursal = $sucursal->telefono_sucursal ?? '';
        $this->referencia_sucursal = $sucursal->referencia_sucursal ?? '';
        $this->activo_sucursal = $sucursal->activo_sucursal;
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    private function validatedData(?Sucursal $sucursal = null): array
    {
        $this->normalize();

        $validated = $this->validate([
            'cliente_id' => ['required', 'integer', Rule::exists(Cliente::class, 'id')],
            'nombre_sucursal' => [
                'required',
                'string',
                'min:2',
                'max:150',
                'not_regex:/[<>\x00-\x1F\x7F]/u',
                Rule::unique(Sucursal::class, 'nombre_sucursal')
                    ->where(fn (Builder $query): Builder => $query->where('cliente_id', $this->cliente_id))
                    ->ignore($sucursal),
            ],
            'direccion_sucursal' => [
                'required',
                'string',
                'min:5',
                'max:255',
                'not_regex:/[<>\x00-\x1F\x7F]/u',
            ],
            'telefono_sucursal' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'referencia_sucursal' => ['nullable', 'string', 'max:255', 'not_regex:/[<>\x00-\x1F\x7F]/u'],
            'activo_sucursal' => ['required', 'boolean'],
        ], $this->messages(), $this->validationAttributes());

        foreach (['telefono_sucursal', 'referencia_sucursal'] as $nullableField) {
            $validated[$nullableField] = $validated[$nullableField] === '' ? null : $validated[$nullableField];
        }

        return $validated;
    }

    private function normalize(): void
    {
        $this->nombre_sucursal = Str::squish($this->nombre_sucursal);
        $this->direccion_sucursal = Str::squish($this->direccion_sucursal);
        $this->telefono_sucursal = trim($this->telefono_sucursal);
        $this->referencia_sucursal = Str::squish($this->referencia_sucursal);
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'cliente_id.required' => 'Selecciona el cliente al que pertenece la sucursal.',
            'cliente_id.exists' => 'El cliente seleccionado ya no está disponible.',
            'nombre_sucursal.required' => 'Ingresa un nombre para identificar la sucursal.',
            'nombre_sucursal.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre_sucursal.unique' => 'Este cliente ya tiene una sucursal con el mismo nombre.',
            'nombre_sucursal.not_regex' => 'El nombre contiene caracteres no permitidos.',
            'direccion_sucursal.required' => 'Ingresa la dirección de la sucursal.',
            'direccion_sucursal.min' => 'La dirección debe tener al menos 5 caracteres.',
            'direccion_sucursal.not_regex' => 'La dirección contiene caracteres no permitidos.',
            'telefono_sucursal.regex' => 'El teléfono contiene caracteres no permitidos.',
            'referencia_sucursal.not_regex' => 'La referencia contiene caracteres no permitidos.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'cliente_id' => 'cliente',
            'nombre_sucursal' => 'nombre de la sucursal',
            'direccion_sucursal' => 'dirección',
            'telefono_sucursal' => 'teléfono',
            'referencia_sucursal' => 'referencia',
            'activo_sucursal' => 'estado',
        ];
    }
}
