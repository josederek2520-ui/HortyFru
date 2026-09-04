<?php

namespace App\Livewire\Forms;

use App\Models\Cliente;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ClientForm extends Form
{
    public string $razon_social = '';

    public string $nit = '';

    public string $telefono_cliente = '';

    public string $email_cliente = '';

    public bool $activo_cliente = true;

    /** @return array<string, mixed> */
    public function validateForCreate(): array
    {
        return $this->validatedData();
    }

    /** @return array<string, mixed> */
    public function validateForUpdate(Cliente $cliente): array
    {
        return $this->validatedData($cliente);
    }

    public function fillFrom(Cliente $cliente): void
    {
        $this->razon_social = $cliente->razon_social;
        $this->nit = $cliente->nit;
        $this->telefono_cliente = $cliente->telefono_cliente ?? '';
        $this->email_cliente = $cliente->email_cliente ?? '';
        $this->activo_cliente = $cliente->activo_cliente;
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    private function validatedData(?Cliente $cliente = null): array
    {
        $this->normalize();

        $validated = $this->validate([
            'razon_social' => [
                'required',
                'string',
                'min:3',
                'max:150',
                'not_regex:/[<>\x00-\x1F\x7F]/u',
            ],
            'nit' => [
                'required',
                'string',
                'digits_between:5,20',
                Rule::unique(Cliente::class, 'nit')->ignore($cliente),
            ],
            'telefono_cliente' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'email_cliente' => ['nullable', 'string', 'email:rfc', 'max:150'],
            'activo_cliente' => ['required', 'boolean'],
        ], $this->messages(), $this->validationAttributes());

        foreach (['telefono_cliente', 'email_cliente'] as $nullableField) {
            $validated[$nullableField] = $validated[$nullableField] === '' ? null : $validated[$nullableField];
        }

        return $validated;
    }

    private function normalize(): void
    {
        $this->razon_social = Str::squish($this->razon_social);
        $this->nit = preg_replace('/[\s-]+/u', '', $this->nit) ?? '';
        $this->telefono_cliente = trim($this->telefono_cliente);
        $this->email_cliente = Str::lower(trim($this->email_cliente));
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'razon_social.required' => 'Ingresa la razón social del cliente.',
            'razon_social.min' => 'La razón social debe tener al menos 3 caracteres.',
            'razon_social.not_regex' => 'La razón social contiene caracteres no permitidos.',
            'nit.required' => 'Ingresa el NIT del cliente.',
            'nit.digits_between' => 'El NIT debe contener entre 5 y 20 dígitos.',
            'nit.unique' => 'Este NIT ya está registrado.',
            'telefono_cliente.regex' => 'El teléfono contiene caracteres no permitidos.',
            'email_cliente.email' => 'Ingresa un correo electrónico válido.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'razon_social' => 'razón social',
            'nit' => 'NIT',
            'telefono_cliente' => 'teléfono',
            'email_cliente' => 'correo electrónico',
            'activo_cliente' => 'estado',
        ];
    }
}
