<?php

namespace App\Livewire\Forms;

use App\Models\Proveedor;
use Illuminate\Support\Str;
use Livewire\Form;

class FormularioProveedor extends Form
{
    public string $nombre_proveedor = '';

    public string $telefono_proveedor = '';

    public string $mercado_proveedor = '';

    public string $direccion_proveedor = '';

    public string $observacion_proveedor = '';

    public bool $estado_proveedor = true;

    /** @return array<string, mixed> */
    public function validarParaCrear(): array
    {
        return $this->datosValidados();
    }

    /** @return array<string, mixed> */
    public function validarParaActualizar(Proveedor $proveedor): array
    {
        return $this->datosValidados();
    }

    public function llenarDesde(Proveedor $proveedor): void
    {
        $this->nombre_proveedor = $proveedor->nombre_proveedor;
        $this->telefono_proveedor = $proveedor->telefono_proveedor ?? '';
        $this->mercado_proveedor = $proveedor->mercado_proveedor ?? '';
        $this->direccion_proveedor = $proveedor->direccion_proveedor ?? '';
        $this->observacion_proveedor = $proveedor->observacion_proveedor ?? '';
        $this->estado_proveedor = $proveedor->estado_proveedor;
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    private function datosValidados(): array
    {
        $this->normalizar();

        $validated = $this->validate([
            'nombre_proveedor' => [
                'required',
                'string',
                'min:2',
                'max:150',
                'not_regex:/[<>\x00-\x1F\x7F]/u',
            ],
            'telefono_proveedor' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'mercado_proveedor' => ['nullable', 'string', 'max:150', 'not_regex:/[<>\x00-\x1F\x7F]/u'],
            'direccion_proveedor' => ['nullable', 'string', 'max:255', 'not_regex:/[<>\x00-\x1F\x7F]/u'],
            'observacion_proveedor' => ['nullable', 'string', 'max:2000', 'not_regex:/[<>\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
            'estado_proveedor' => ['required', 'boolean'],
        ], $this->messages(), $this->validationAttributes());

        foreach (['telefono_proveedor', 'mercado_proveedor', 'direccion_proveedor', 'observacion_proveedor'] as $nullableField) {
            $validated[$nullableField] = $validated[$nullableField] === '' ? null : $validated[$nullableField];
        }

        return $validated;
    }

    private function normalizar(): void
    {
        $this->nombre_proveedor = Str::squish($this->nombre_proveedor);
        $this->telefono_proveedor = trim($this->telefono_proveedor);
        $this->mercado_proveedor = Str::squish($this->mercado_proveedor);
        $this->direccion_proveedor = Str::squish($this->direccion_proveedor);
        $this->observacion_proveedor = trim($this->observacion_proveedor);
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'nombre_proveedor.required' => 'Ingresa el nombre o referencia del proveedor.',
            'nombre_proveedor.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre_proveedor.not_regex' => 'El nombre contiene caracteres no permitidos.',
            'telefono_proveedor.regex' => 'El teléfono contiene caracteres no permitidos.',
            'mercado_proveedor.not_regex' => 'El mercado contiene caracteres no permitidos.',
            'direccion_proveedor.not_regex' => 'La dirección contiene caracteres no permitidos.',
            'observacion_proveedor.not_regex' => 'La observación contiene caracteres no permitidos.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'nombre_proveedor' => 'nombre del proveedor',
            'telefono_proveedor' => 'teléfono',
            'mercado_proveedor' => 'mercado',
            'direccion_proveedor' => 'dirección',
            'observacion_proveedor' => 'observación',
            'estado_proveedor' => 'estado',
        ];
    }
}
