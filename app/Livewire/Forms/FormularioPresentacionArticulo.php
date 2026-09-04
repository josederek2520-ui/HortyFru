<?php

namespace App\Livewire\Forms;

use App\Enums\TipoEquivalenciaPresentacionArticulo;
use App\Enums\UsoPresentacionArticulo;
use App\Models\Articulo;
use App\Models\PresentacionArticulo;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

class FormularioPresentacionArticulo extends Form
{
    public ?int $articulo_id = null;

    public string $nombre_presentacion_articulo = '';

    public string $uso_presentacion_articulo = UsoPresentacionArticulo::Pedido->value;

    public string $tipo_equivalencia_presentacion_articulo = TipoEquivalenciaPresentacionArticulo::Variable->value;

    public ?string $equivalencia_base_presentacion_articulo = null;

    public bool $permite_fraccion_presentacion_articulo = false;

    public bool $predeterminada_pedido_presentacion_articulo = false;

    public bool $predeterminada_compra_presentacion_articulo = false;

    public bool $estado_presentacion_articulo = true;

    /** @return array<string, mixed> */
    public function validarParaCrear(): array
    {
        return $this->datosValidados();
    }

    /** @return array<string, mixed> */
    public function validarParaActualizar(PresentacionArticulo $presentacionArticulo): array
    {
        return $this->datosValidados($presentacionArticulo);
    }

    public function llenarDesde(PresentacionArticulo $presentacionArticulo): void
    {
        $this->articulo_id = $presentacionArticulo->articulo_id;
        $this->nombre_presentacion_articulo = $presentacionArticulo->nombre_presentacion_articulo;
        $this->uso_presentacion_articulo = $presentacionArticulo->uso_presentacion_articulo->value;
        $this->tipo_equivalencia_presentacion_articulo = $presentacionArticulo->tipo_equivalencia_presentacion_articulo->value;
        $this->equivalencia_base_presentacion_articulo = $presentacionArticulo->equivalencia_base_presentacion_articulo;
        $this->permite_fraccion_presentacion_articulo = $presentacionArticulo->permite_fraccion_presentacion_articulo;
        $this->predeterminada_pedido_presentacion_articulo = $presentacionArticulo->predeterminada_pedido_presentacion_articulo;
        $this->predeterminada_compra_presentacion_articulo = $presentacionArticulo->predeterminada_compra_presentacion_articulo;
        $this->estado_presentacion_articulo = $presentacionArticulo->estado_presentacion_articulo;
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    private function datosValidados(?PresentacionArticulo $presentacionArticulo = null): array
    {
        $this->nombre_presentacion_articulo = Str::squish($this->nombre_presentacion_articulo);
        $this->equivalencia_base_presentacion_articulo = filled($this->equivalencia_base_presentacion_articulo)
            ? trim($this->equivalencia_base_presentacion_articulo)
            : null;

        $this->normalizarReglasDeNegocio();

        return $this->validate([
            'articulo_id' => [
                'required',
                'integer',
                Rule::exists(Articulo::class, 'id')->where(function ($query) use ($presentacionArticulo): void {
                    $query->where('estado_articulo', true);

                    if ($presentacionArticulo !== null) {
                        $query->orWhere('id', $presentacionArticulo->articulo_id);
                    }
                }),
            ],
            'nombre_presentacion_articulo' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'not_regex:/[<>\x00-\x1F\x7F]/u',
                Rule::unique(PresentacionArticulo::class, 'nombre_presentacion_articulo')
                    ->where(fn ($query) => $query->where('articulo_id', $this->articulo_id))
                    ->ignore($presentacionArticulo),
            ],
            'uso_presentacion_articulo' => [
                'required',
                Rule::enum(UsoPresentacionArticulo::class),
            ],
            'tipo_equivalencia_presentacion_articulo' => [
                'required',
                Rule::enum(TipoEquivalenciaPresentacionArticulo::class),
            ],
            'equivalencia_base_presentacion_articulo' => [
                Rule::requiredIf($this->tipo_equivalencia_presentacion_articulo !== TipoEquivalenciaPresentacionArticulo::Variable->value),
                'nullable',
                'numeric',
                'decimal:0,3',
                'between:0.001,9999999.999',
            ],
            'permite_fraccion_presentacion_articulo' => ['required', 'boolean'],
            'predeterminada_pedido_presentacion_articulo' => ['required', 'boolean'],
            'predeterminada_compra_presentacion_articulo' => ['required', 'boolean'],
            'estado_presentacion_articulo' => ['required', 'boolean'],
        ], $this->messages(), $this->validationAttributes());
    }

    private function normalizarReglasDeNegocio(): void
    {
        if ($this->tipo_equivalencia_presentacion_articulo === TipoEquivalenciaPresentacionArticulo::Variable->value) {
            $this->equivalencia_base_presentacion_articulo = null;
        }

        if ($this->uso_presentacion_articulo === UsoPresentacionArticulo::Pedido->value) {
            $this->predeterminada_compra_presentacion_articulo = false;
        }

        if ($this->uso_presentacion_articulo === UsoPresentacionArticulo::Compra->value) {
            $this->predeterminada_pedido_presentacion_articulo = false;
        }

        if (! $this->estado_presentacion_articulo) {
            $this->predeterminada_pedido_presentacion_articulo = false;
            $this->predeterminada_compra_presentacion_articulo = false;
        }
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'articulo_id.required' => 'Selecciona un artículo.',
            'articulo_id.exists' => 'El artículo seleccionado no está disponible.',
            'nombre_presentacion_articulo.required' => 'Ingresa el nombre de la presentación.',
            'nombre_presentacion_articulo.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre_presentacion_articulo.unique' => 'Este artículo ya tiene una presentación con el mismo nombre.',
            'nombre_presentacion_articulo.not_regex' => 'El nombre contiene caracteres no permitidos.',
            'uso_presentacion_articulo.required' => 'Selecciona dónde se utilizará la presentación.',
            'uso_presentacion_articulo.enum' => 'El uso seleccionado no es válido.',
            'tipo_equivalencia_presentacion_articulo.required' => 'Selecciona el tipo de equivalencia.',
            'tipo_equivalencia_presentacion_articulo.enum' => 'El tipo de equivalencia seleccionado no es válido.',
            'equivalencia_base_presentacion_articulo.required' => 'Ingresa la equivalencia con la unidad base.',
            'equivalencia_base_presentacion_articulo.numeric' => 'La equivalencia debe ser un número.',
            'equivalencia_base_presentacion_articulo.decimal' => 'La equivalencia admite hasta 3 decimales.',
            'equivalencia_base_presentacion_articulo.between' => 'La equivalencia debe estar entre 0.001 y 9999999.999.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'articulo_id' => 'artículo',
            'nombre_presentacion_articulo' => 'nombre de la presentación',
            'uso_presentacion_articulo' => 'uso',
            'tipo_equivalencia_presentacion_articulo' => 'tipo de equivalencia',
            'equivalencia_base_presentacion_articulo' => 'equivalencia base',
            'permite_fraccion_presentacion_articulo' => 'permiso de fracciones',
            'predeterminada_pedido_presentacion_articulo' => 'presentación predeterminada para pedido',
            'predeterminada_compra_presentacion_articulo' => 'presentación predeterminada para compra',
            'estado_presentacion_articulo' => 'estado',
        ];
    }
}
