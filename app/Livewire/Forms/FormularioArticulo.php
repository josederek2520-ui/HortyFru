<?php

namespace App\Livewire\Forms;

use App\Models\Articulo;
use App\Models\CategoriaArticulo;
use App\Models\UnidadMedida;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class FormularioArticulo extends Form
{
    public string $nombre_articulo = '';

    public ?int $categoria_articulo_id = null;

    public ?int $unidad_medida_id = null;

    public ?TemporaryUploadedFile $imagen_articulo = null;

    public ?string $imagen_actual = null;

    public bool $eliminar_imagen = false;

    public bool $estado_articulo = true;

    /** @return array<string, mixed> */
    public function validarParaCrear(): array
    {
        return $this->datosValidados();
    }

    /** @return array<string, mixed> */
    public function validarParaActualizar(Articulo $articulo): array
    {
        return $this->datosValidados($articulo);
    }

    public function llenarDesde(Articulo $articulo): void
    {
        $this->nombre_articulo = $articulo->nombre_articulo;
        $this->categoria_articulo_id = $articulo->categoria_articulo_id;
        $this->unidad_medida_id = $articulo->unidad_medida_id;
        $this->imagen_actual = $articulo->imagen_articulo;
        $this->estado_articulo = $articulo->estado_articulo;
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    private function datosValidados(?Articulo $articulo = null): array
    {
        $this->nombre_articulo = Str::squish($this->nombre_articulo);

        return $this->validate([
            'nombre_articulo' => [
                'required',
                'string',
                'min:2',
                'max:150',
                'not_regex:/[<>\x00-\x1F\x7F]/u',
                Rule::unique(Articulo::class, 'nombre_articulo')->ignore($articulo),
            ],
            'categoria_articulo_id' => [
                'required',
                'integer',
                Rule::exists(CategoriaArticulo::class, 'id')->where(function ($query) use ($articulo): void {
                    $query->where('estado_categoria_articulo', true);

                    if ($articulo !== null) {
                        $query->orWhere('id', $articulo->categoria_articulo_id);
                    }
                }),
            ],
            'unidad_medida_id' => [
                'required',
                'integer',
                Rule::exists(UnidadMedida::class, 'id')->where(function ($query) use ($articulo): void {
                    $query->where('estado_unidad_medida', true);

                    if ($articulo !== null) {
                        $query->orWhere('id', $articulo->unidad_medida_id);
                    }
                }),
            ],
            'imagen_articulo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
                'dimensions:max_width=4000,max_height=4000',
            ],
            'eliminar_imagen' => ['required', 'boolean'],
            'estado_articulo' => ['required', 'boolean'],
        ], $this->messages(), $this->validationAttributes());
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'nombre_articulo.required' => 'Ingresa el nombre del producto.',
            'nombre_articulo.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre_articulo.unique' => 'Ya existe un producto con este nombre.',
            'nombre_articulo.not_regex' => 'El nombre contiene caracteres no permitidos.',
            'categoria_articulo_id.required' => 'Selecciona una categoría.',
            'categoria_articulo_id.exists' => 'La categoría seleccionada no está disponible.',
            'unidad_medida_id.required' => 'Selecciona una unidad de medida.',
            'unidad_medida_id.exists' => 'La unidad de medida seleccionada no está disponible.',
            'imagen_articulo.image' => 'Selecciona una imagen válida.',
            'imagen_articulo.mimes' => 'La imagen debe ser JPG, JPEG, PNG o WebP.',
            'imagen_articulo.max' => 'La imagen no debe superar los 3 MB.',
            'imagen_articulo.dimensions' => 'La imagen no debe superar los 4000 píxeles de ancho o alto.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'nombre_articulo' => 'nombre del producto',
            'categoria_articulo_id' => 'categoría',
            'unidad_medida_id' => 'unidad de medida',
            'imagen_articulo' => 'imagen del producto',
            'eliminar_imagen' => 'eliminación de imagen',
            'estado_articulo' => 'estado',
        ];
    }
}
