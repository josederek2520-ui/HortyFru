<?php

namespace App\Livewire\Forms;

use App\Models\CategoriaArticulo;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

class FormularioCategoriaArticulo extends Form
{
    public string $nombre_categoria_articulo = '';

    public bool $estado_categoria_articulo = true;

    /** @return array<string, mixed> */
    public function validarParaCrear(): array
    {
        return $this->datosValidados();
    }

    /** @return array<string, mixed> */
    public function validarParaActualizar(CategoriaArticulo $categoriaArticulo): array
    {
        return $this->datosValidados($categoriaArticulo);
    }

    public function llenarDesde(CategoriaArticulo $categoriaArticulo): void
    {
        $this->nombre_categoria_articulo = $categoriaArticulo->nombre_categoria_articulo;
        $this->estado_categoria_articulo = $categoriaArticulo->estado_categoria_articulo;
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    private function datosValidados(?CategoriaArticulo $categoriaArticulo = null): array
    {
        $this->nombre_categoria_articulo = Str::squish($this->nombre_categoria_articulo);

        return $this->validate([
            'nombre_categoria_articulo' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'not_regex:/[<>\x00-\x1F\x7F]/u',
                Rule::unique(CategoriaArticulo::class, 'nombre_categoria_articulo')->ignore($categoriaArticulo),
            ],
            'estado_categoria_articulo' => ['required', 'boolean'],
        ], $this->messages(), $this->validationAttributes());
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'nombre_categoria_articulo.required' => 'Ingresa el nombre de la categoría.',
            'nombre_categoria_articulo.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre_categoria_articulo.unique' => 'Ya existe una categoría con este nombre.',
            'nombre_categoria_articulo.not_regex' => 'El nombre contiene caracteres no permitidos.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'nombre_categoria_articulo' => 'nombre de la categoría',
            'estado_categoria_articulo' => 'estado',
        ];
    }
}
