<?php

namespace App\Livewire\Forms;

use App\Models\Vehiculo;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

class FormularioVehiculo extends Form
{
    public string $placa_vehiculo = '';

    public string $marca_vehiculo = '';

    public string $tipo_vehiculo = '';

    public bool $estado_vehiculo = true;

    /** @return array<string, mixed> */
    public function validarParaCrear(): array
    {
        return $this->datosValidados();
    }

    /** @return array<string, mixed> */
    public function validarParaActualizar(Vehiculo $vehiculo): array
    {
        return $this->datosValidados($vehiculo);
    }

    public function llenarDesde(Vehiculo $vehiculo): void
    {
        $this->placa_vehiculo = $vehiculo->placa_vehiculo;
        $this->marca_vehiculo = $vehiculo->marca_vehiculo ?? '';
        $this->tipo_vehiculo = $vehiculo->tipo_vehiculo;
        $this->estado_vehiculo = $vehiculo->estado_vehiculo;
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    private function datosValidados(?Vehiculo $vehiculo = null): array
    {
        $this->normalizar();

        $validated = $this->validate([
            'placa_vehiculo' => [
                'required',
                'string',
                'min:3',
                'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique(Vehiculo::class, 'placa_vehiculo')->ignore($vehiculo),
            ],
            'marca_vehiculo' => ['nullable', 'string', 'max:100', 'not_regex:/[<>\x00-\x1F\x7F]/u'],
            'tipo_vehiculo' => ['required', 'string', 'min:3', 'max:50', 'not_regex:/[<>\x00-\x1F\x7F]/u'],
            'estado_vehiculo' => ['required', 'boolean'],
        ], $this->messages(), $this->validationAttributes());

        $validated['marca_vehiculo'] = $validated['marca_vehiculo'] === '' ? null : $validated['marca_vehiculo'];

        return $validated;
    }

    private function normalizar(): void
    {
        $this->placa_vehiculo = Str::upper(preg_replace('/\s+/u', '', $this->placa_vehiculo) ?? '');
        $this->marca_vehiculo = Str::squish($this->marca_vehiculo);
        $this->tipo_vehiculo = Str::squish($this->tipo_vehiculo);
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'placa_vehiculo.required' => 'Ingresa la placa del vehículo.',
            'placa_vehiculo.min' => 'La placa debe tener al menos 3 caracteres.',
            'placa_vehiculo.regex' => 'La placa solo puede contener letras, números y guiones.',
            'placa_vehiculo.unique' => 'Esta placa ya está registrada.',
            'marca_vehiculo.not_regex' => 'La marca contiene caracteres no permitidos.',
            'tipo_vehiculo.required' => 'Ingresa el tipo de vehículo.',
            'tipo_vehiculo.min' => 'El tipo debe tener al menos 3 caracteres.',
            'tipo_vehiculo.not_regex' => 'El tipo contiene caracteres no permitidos.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'placa_vehiculo' => 'placa',
            'marca_vehiculo' => 'marca',
            'tipo_vehiculo' => 'tipo de vehículo',
            'estado_vehiculo' => 'estado',
        ];
    }
}
