<?php

namespace App\Livewire\Forms;

use App\Models\Lote;
use Illuminate\Support\Str;
use Livewire\Form;

class FormularioLote extends Form
{
    public string $fecha_vencimiento_lote = '';

    public string $calidad_lote = '';

    public string $observaciones_lote = '';

    /** @return array<string, mixed> */
    public function validarParaActualizar(Lote $lote): array
    {
        $this->normalizar();
        $fechaIngreso = $lote->fecha_ingreso_local->format('Y-m-d');
        $datos = $this->validate([
            'fecha_vencimiento_lote' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.$fechaIngreso],
            'calidad_lote' => ['nullable', 'string', 'max:100', 'not_regex:/[<>\x00-\x1F\x7F]/u'],
            'observaciones_lote' => ['nullable', 'string', 'max:1000', 'not_regex:/[<>\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
        ], [
            'fecha_vencimiento_lote.date_format' => 'Selecciona una fecha de vencimiento válida.',
            'fecha_vencimiento_lote.after_or_equal' => 'La fecha de vencimiento no puede ser anterior al ingreso del lote.',
            'calidad_lote.max' => 'La calidad no debe superar los 100 caracteres.',
            'calidad_lote.not_regex' => 'La calidad contiene caracteres no permitidos.',
            'observaciones_lote.max' => 'La observación no debe superar los 1000 caracteres.',
            'observaciones_lote.not_regex' => 'La observación contiene caracteres no permitidos.',
        ]);

        $datos['fecha_vencimiento_lote'] = $datos['fecha_vencimiento_lote'] ?: null;
        $datos['calidad_lote'] = $datos['calidad_lote'] ?: null;
        $datos['observaciones_lote'] = $datos['observaciones_lote'] ?: null;

        return $datos;
    }

    public function llenarDesde(Lote $lote): void
    {
        $this->fecha_vencimiento_lote = $lote->fecha_vencimiento_lote?->format('Y-m-d') ?? '';
        $this->calidad_lote = $lote->calidad_lote ?? '';
        $this->observaciones_lote = $lote->observaciones_lote ?? '';
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    private function normalizar(): void
    {
        $this->fecha_vencimiento_lote = trim($this->fecha_vencimiento_lote);
        $this->calidad_lote = Str::squish($this->calidad_lote);
        $this->observaciones_lote = Str::squish($this->observaciones_lote);
    }
}
