<?php

namespace App\Livewire\Forms;

use App\Models\UnidadMedida;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

class FormularioUnidadMedida extends Form
{
    public string $nombre_unidad_medida = '';

    public string $abreviatura_unidad_medida = '';

    public bool $estado_unidad_medida = true;

    /** @return array<string, mixed> */
    public function validarParaCrear(): array
    {
        return $this->datosValidados();
    }

    /** @return array<string, mixed> */
    public function validarParaActualizar(UnidadMedida $unidadMedida): array
    {
        return $this->datosValidados($unidadMedida);
    }

    public function llenarDesde(UnidadMedida $unidadMedida): void
    {
        $this->nombre_unidad_medida = $unidadMedida->nombre_unidad_medida;
        $this->abreviatura_unidad_medida = $unidadMedida->abreviatura_unidad_medida;
        $this->estado_unidad_medida = $unidadMedida->estado_unidad_medida;
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    private function datosValidados(?UnidadMedida $unidadMedida = null): array
    {
        $this->nombre_unidad_medida = Str::squish($this->nombre_unidad_medida);
        $this->abreviatura_unidad_medida = Str::squish($this->abreviatura_unidad_medida);

        return $this->validate([
            'nombre_unidad_medida' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'not_regex:/[<>\x00-\x1F\x7F]/u',
                Rule::unique(UnidadMedida::class, 'nombre_unidad_medida')->ignore($unidadMedida),
            ],
            'abreviatura_unidad_medida' => [
                'required',
                'string',
                'max:10',
                'not_regex:/[<>\x00-\x1F\x7F]/u',
                Rule::unique(UnidadMedida::class, 'abreviatura_unidad_medida')->ignore($unidadMedida),
            ],
            'estado_unidad_medida' => ['required', 'boolean'],
        ], $this->messages(), $this->validationAttributes());
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'nombre_unidad_medida.required' => 'Ingresa el nombre de la unidad de medida.',
            'nombre_unidad_medida.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre_unidad_medida.unique' => 'Ya existe una unidad de medida con este nombre.',
            'nombre_unidad_medida.not_regex' => 'El nombre contiene caracteres no permitidos.',
            'abreviatura_unidad_medida.required' => 'Ingresa la abreviatura de la unidad de medida.',
            'abreviatura_unidad_medida.unique' => 'Ya existe una unidad de medida con esta abreviatura.',
            'abreviatura_unidad_medida.not_regex' => 'La abreviatura contiene caracteres no permitidos.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'nombre_unidad_medida' => 'nombre de la unidad de medida',
            'abreviatura_unidad_medida' => 'abreviatura',
            'estado_unidad_medida' => 'estado',
        ];
    }
}
