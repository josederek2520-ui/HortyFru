<?php

namespace App\Livewire\Forms;

use App\Models\Almacen;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

class FormularioAlmacen extends Form
{
    public string $nombre_almacen = '';

    public string $direccion_almacen = '';

    public bool $estado_almacen = true;

    /** @return array<string, mixed> */
    public function validarParaCrear(): array
    {
        return $this->datosValidados();
    }

    /** @return array<string, mixed> */
    public function validarParaActualizar(Almacen $almacen): array
    {
        return $this->datosValidados($almacen);
    }

    public function llenarDesde(Almacen $almacen): void
    {
        $this->nombre_almacen = $almacen->nombre_almacen;
        $this->direccion_almacen = $almacen->direccion_almacen ?? '';
        $this->estado_almacen = $almacen->estado_almacen;
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    private function datosValidados(?Almacen $almacen = null): array
    {
        $this->normalizar();

        $validated = $this->validate([
            'nombre_almacen' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'not_regex:/[<>\x00-\x1F\x7F]/u',
                Rule::unique(Almacen::class, 'nombre_almacen')->ignore($almacen),
            ],
            'direccion_almacen' => ['nullable', 'string', 'max:255', 'not_regex:/[<>\x00-\x1F\x7F]/u'],
            'estado_almacen' => ['required', 'boolean'],
        ], $this->messages(), $this->validationAttributes());

        $validated['direccion_almacen'] = $validated['direccion_almacen'] === '' ? null : $validated['direccion_almacen'];

        return $validated;
    }

    private function normalizar(): void
    {
        $this->nombre_almacen = Str::squish($this->nombre_almacen);
        $this->direccion_almacen = Str::squish($this->direccion_almacen);
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'nombre_almacen.required' => 'Ingresa el nombre del almacén.',
            'nombre_almacen.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre_almacen.unique' => 'Ya existe un almacén con este nombre.',
            'nombre_almacen.not_regex' => 'El nombre contiene caracteres no permitidos.',
            'direccion_almacen.not_regex' => 'La dirección contiene caracteres no permitidos.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'nombre_almacen' => 'nombre del almacén',
            'direccion_almacen' => 'dirección',
            'estado_almacen' => 'estado',
        ];
    }
}
