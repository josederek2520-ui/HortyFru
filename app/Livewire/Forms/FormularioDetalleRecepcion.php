<?php

namespace App\Livewire\Forms;

use App\Models\DetalleRecepcion;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

class FormularioDetalleRecepcion extends Form
{
    public string $detalle_compra_id = '';

    public string $articulo_id = '';

    public string $presentacion_articulo_id = '';

    public string $cantidad_presentaciones_detalle_recepcion = '';

    public string $cantidad_base_detalle_recepcion = '';

    public string $fecha_vencimiento_detalle_recepcion = '';

    public string $calidad_detalle_recepcion = '';

    public string $observaciones_detalle_recepcion = '';

    /** @return array<string, mixed> */
    public function validar(): array
    {
        $this->normalizar();

        $datos = $this->validate([
            'detalle_compra_id' => ['nullable', 'integer', Rule::exists('detalles_compras', 'id')],
            'articulo_id' => ['required', 'integer', Rule::exists('articulos', 'id')],
            'presentacion_articulo_id' => ['nullable', 'integer', Rule::exists('presentaciones_articulos', 'id')],
            'cantidad_presentaciones_detalle_recepcion' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999.999'],
            'cantidad_base_detalle_recepcion' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:99999999999.999'],
            'fecha_vencimiento_detalle_recepcion' => ['nullable', 'date_format:Y-m-d'],
            'calidad_detalle_recepcion' => ['nullable', 'string', 'max:100', 'not_regex:/[<>\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
            'observaciones_detalle_recepcion' => ['nullable', 'string', 'max:500', 'not_regex:/[<>\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
        ], [
            'detalle_compra_id.exists' => 'El producto de la compra ya no existe.',
            'articulo_id.required' => 'Selecciona un producto.',
            'articulo_id.exists' => 'El producto seleccionado ya no existe.',
            'presentacion_articulo_id.exists' => 'La presentación seleccionada ya no existe.',
            'cantidad_presentaciones_detalle_recepcion.numeric' => 'La cantidad de presentaciones debe ser numérica.',
            'cantidad_presentaciones_detalle_recepcion.decimal' => 'Usa como máximo 3 decimales.',
            'cantidad_presentaciones_detalle_recepcion.gt' => 'La cantidad de presentaciones debe ser mayor que cero.',
            'cantidad_base_detalle_recepcion.numeric' => 'La cantidad recibida debe ser numérica.',
            'cantidad_base_detalle_recepcion.decimal' => 'Usa como máximo 3 decimales para la cantidad recibida.',
            'cantidad_base_detalle_recepcion.gt' => 'La cantidad recibida debe ser mayor que cero.',
            'fecha_vencimiento_detalle_recepcion.date_format' => 'La fecha de vencimiento no es válida.',
            'calidad_detalle_recepcion.not_regex' => 'La calidad contiene caracteres no permitidos.',
            'observaciones_detalle_recepcion.not_regex' => 'La observación contiene caracteres no permitidos.',
        ]);

        foreach (['detalle_compra_id', 'presentacion_articulo_id'] as $campo) {
            $datos[$campo] = $datos[$campo] === '' || $datos[$campo] === null
                ? null
                : (int) $datos[$campo];
        }

        $datos['articulo_id'] = (int) $datos['articulo_id'];

        foreach ([
            'cantidad_presentaciones_detalle_recepcion',
            'cantidad_base_detalle_recepcion',
            'fecha_vencimiento_detalle_recepcion',
            'calidad_detalle_recepcion',
            'observaciones_detalle_recepcion',
        ] as $campo) {
            $datos[$campo] = $datos[$campo] ?: null;
        }

        return $datos;
    }

    public function llenarDesde(DetalleRecepcion $detalle): void
    {
        $this->detalle_compra_id = $detalle->detalle_compra_id === null ? '' : (string) $detalle->detalle_compra_id;
        $this->articulo_id = (string) $detalle->articulo_id;
        $this->presentacion_articulo_id = $detalle->presentacion_articulo_id === null ? '' : (string) $detalle->presentacion_articulo_id;
        $this->cantidad_presentaciones_detalle_recepcion = $detalle->cantidad_presentaciones_detalle_recepcion ?? '';
        $this->cantidad_base_detalle_recepcion = $detalle->cantidad_base_detalle_recepcion;
        $this->fecha_vencimiento_detalle_recepcion = $detalle->fecha_vencimiento_detalle_recepcion?->format('Y-m-d') ?? '';
        $this->calidad_detalle_recepcion = $detalle->calidad_detalle_recepcion ?? '';
        $this->observaciones_detalle_recepcion = $detalle->observaciones_detalle_recepcion ?? '';
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    private function normalizar(): void
    {
        $this->detalle_compra_id = trim($this->detalle_compra_id);
        $this->articulo_id = trim($this->articulo_id);
        $this->presentacion_articulo_id = trim($this->presentacion_articulo_id);
        $this->cantidad_presentaciones_detalle_recepcion = trim(str_replace(',', '.', $this->cantidad_presentaciones_detalle_recepcion));
        $this->cantidad_base_detalle_recepcion = trim(str_replace(',', '.', $this->cantidad_base_detalle_recepcion));
        $this->fecha_vencimiento_detalle_recepcion = trim($this->fecha_vencimiento_detalle_recepcion);
        $this->calidad_detalle_recepcion = Str::squish($this->calidad_detalle_recepcion);
        $this->observaciones_detalle_recepcion = Str::squish($this->observaciones_detalle_recepcion);
    }
}
