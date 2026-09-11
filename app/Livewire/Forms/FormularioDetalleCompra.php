<?php

namespace App\Livewire\Forms;

use App\Enums\PrecioPorDetalleCompra;
use App\Models\DetalleCompra;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

class FormularioDetalleCompra extends Form
{
    public string $articulo_id = '';

    public string $presentacion_articulo_id = '';

    public string $cantidad_presentaciones_detalle_compra = '';

    public string $cantidad_real_detalle_compra = '';

    public string $precio_unitario_detalle_compra = '';

    public string $precio_por_detalle_compra = 'PRESENTACION';

    public string $total_pagado_detalle_compra = '';

    public string $observaciones_detalle_compra = '';

    /** @return array<string, mixed> */
    public function validar(): array
    {
        $this->normalizar();

        $datos = $this->validate([
            'articulo_id' => ['required', 'integer', Rule::exists('articulos', 'id')],
            'presentacion_articulo_id' => ['nullable', 'integer', Rule::exists('presentaciones_articulos', 'id')],
            'cantidad_presentaciones_detalle_compra' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999.999'],
            'cantidad_real_detalle_compra' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:99999999999.999'],
            'total_pagado_detalle_compra' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999999999.99'],
            'observaciones_detalle_compra' => ['nullable', 'string', 'max:500', 'not_regex:/[<>\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
        ], [
            'articulo_id.required' => 'Selecciona un producto.',
            'articulo_id.exists' => 'El producto seleccionado ya no existe.',
            'presentacion_articulo_id.exists' => 'La presentación seleccionada ya no existe.',
            'cantidad_presentaciones_detalle_compra.numeric' => 'La cantidad de presentaciones debe ser numérica.',
            'cantidad_presentaciones_detalle_compra.decimal' => 'Usa como máximo 3 decimales.',
            'cantidad_presentaciones_detalle_compra.gt' => 'La cantidad de presentaciones debe ser mayor que cero.',
            'cantidad_real_detalle_compra.numeric' => 'La cantidad real debe ser numérica.',
            'cantidad_real_detalle_compra.decimal' => 'Usa como máximo 3 decimales para la cantidad real.',
            'cantidad_real_detalle_compra.gt' => 'La cantidad real debe ser mayor que cero.',
            'total_pagado_detalle_compra.required' => 'Ingresa el total pagado por este producto.',
            'total_pagado_detalle_compra.numeric' => 'El total pagado debe ser numérico.',
            'total_pagado_detalle_compra.decimal' => 'Usa como máximo 2 decimales para el total.',
            'total_pagado_detalle_compra.gt' => 'El total pagado debe ser mayor que cero.',
            'observaciones_detalle_compra.not_regex' => 'La observación contiene caracteres no permitidos.',
        ]);

        $datos['articulo_id'] = (int) $datos['articulo_id'];
        $datos['presentacion_articulo_id'] = $datos['presentacion_articulo_id'] === '' || $datos['presentacion_articulo_id'] === null
            ? null
            : (int) $datos['presentacion_articulo_id'];
        $datos['cantidad_presentaciones_detalle_compra'] = $datos['cantidad_presentaciones_detalle_compra'] ?: null;
        $datos['cantidad_real_detalle_compra'] = $datos['cantidad_real_detalle_compra'] ?: null;
        $datos['observaciones_detalle_compra'] = $datos['observaciones_detalle_compra'] ?: null;

        return $datos;
    }

    public function llenarDesde(DetalleCompra $detalle): void
    {
        $this->articulo_id = (string) $detalle->articulo_id;
        $this->presentacion_articulo_id = $detalle->presentacion_articulo_id === null ? '' : (string) $detalle->presentacion_articulo_id;
        $this->cantidad_presentaciones_detalle_compra = $detalle->cantidad_presentaciones_detalle_compra ?? '';
        $this->cantidad_real_detalle_compra = $detalle->cantidad_real_detalle_compra ?? '';
        $this->precio_unitario_detalle_compra = $detalle->precio_unitario_detalle_compra;
        $this->precio_por_detalle_compra = $detalle->precio_por_detalle_compra->value;
        $this->total_pagado_detalle_compra = $detalle->subtotal_detalle_compra;
        $this->observaciones_detalle_compra = $detalle->observaciones_detalle_compra ?? '';
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->precio_por_detalle_compra = PrecioPorDetalleCompra::Presentacion->value;
        $this->resetValidation();
    }

    private function normalizar(): void
    {
        $this->articulo_id = trim($this->articulo_id);
        $this->presentacion_articulo_id = trim($this->presentacion_articulo_id);
        $this->cantidad_presentaciones_detalle_compra = trim(str_replace(',', '.', $this->cantidad_presentaciones_detalle_compra));
        $this->cantidad_real_detalle_compra = trim(str_replace(',', '.', $this->cantidad_real_detalle_compra));
        $this->precio_unitario_detalle_compra = trim(str_replace(',', '.', $this->precio_unitario_detalle_compra));
        $this->total_pagado_detalle_compra = trim(str_replace(',', '.', $this->total_pagado_detalle_compra));
        $this->observaciones_detalle_compra = Str::squish($this->observaciones_detalle_compra);
    }
}
