<?php

namespace App\Livewire\Forms;

use App\Models\Compra;
use App\Models\Empleado;
use App\Models\Proveedor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class FormularioCompra extends Form
{
    public ?int $proveedor_id = null;

    public ?int $empleado_id = null;

    public string $fecha_compra = '';

    public string $observaciones_compra = '';

    /** @return array<string, mixed> */
    public function validarParaCrear(): array
    {
        return $this->datosValidados();
    }

    /** @return array<string, mixed> */
    public function validarParaActualizar(Compra $compra): array
    {
        return $this->datosValidados($compra);
    }

    public function llenarDesde(Compra $compra): void
    {
        $this->proveedor_id = $compra->proveedor_id;
        $this->empleado_id = $compra->empleado_id;
        $this->fecha_compra = $compra->fecha_compra_local?->format('Y-m-d\TH:i') ?? '';
        $this->observaciones_compra = $compra->observaciones_compra ?? '';
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->fecha_compra = now((string) config('app.display_timezone', 'America/La_Paz'))->format('Y-m-d\TH:i');
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    private function datosValidados(?Compra $compra = null): array
    {
        $this->observaciones_compra = Str::squish($this->observaciones_compra);

        $datos = $this->validate([
            'proveedor_id' => ['required', 'integer', Rule::exists(Proveedor::class, 'id')],
            'fecha_compra' => ['required', 'date_format:Y-m-d\TH:i'],
            'observaciones_compra' => ['nullable', 'string', 'max:2000', 'not_regex:/[<>\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
        ], $this->messages(), $this->validationAttributes());

        $errores = [];
        $proveedorActualPermitido = $compra?->proveedor_id === $this->proveedor_id;
        if (! $proveedorActualPermitido && ! Proveedor::query()->whereKey($this->proveedor_id)->where('estado_proveedor', true)->exists()) {
            $errores['form.proveedor_id'] = 'Selecciona un proveedor activo.';
        }

        if ($compra === null && ! Empleado::query()
            ->where('user_id', auth()->id())
            ->where('activo_empleado', true)
            ->exists()) {
            $errores['form.empleado_id'] = 'Tu cuenta debe estar vinculada a un empleado activo para registrar compras.';
        }

        $zonaHoraria = (string) config('app.display_timezone', 'America/La_Paz');
        $fechaCompra = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $this->fecha_compra, $zonaHoraria);

        if ($fechaCompra->isAfter(now($zonaHoraria)->endOfMinute())) {
            $errores['form.fecha_compra'] = 'La fecha de compra no puede estar en el futuro.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }

        $datos['observaciones_compra'] = $datos['observaciones_compra'] === '' ? null : $datos['observaciones_compra'];

        return $datos;
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'proveedor_id.required' => 'Selecciona el proveedor de la compra.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'fecha_compra.required' => 'Indica la fecha y hora de la compra.',
            'fecha_compra.date_format' => 'La fecha y hora de la compra no es válida.',
            'observaciones_compra.max' => 'Las observaciones no pueden superar los 2000 caracteres.',
            'observaciones_compra.not_regex' => 'Las observaciones contienen caracteres no permitidos.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'proveedor_id' => 'proveedor',
            'fecha_compra' => 'fecha de compra',
            'observaciones_compra' => 'observaciones',
        ];
    }
}
