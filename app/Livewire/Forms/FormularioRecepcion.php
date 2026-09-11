<?php

namespace App\Livewire\Forms;

use App\Enums\EstadoCompra;
use App\Models\Almacen;
use App\Models\Compra;
use App\Models\Empleado;
use App\Models\Recepcion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class FormularioRecepcion extends Form
{
    public ?int $compra_id = null;

    public ?int $almacen_id = null;

    public ?int $empleado_id = null;

    public string $fecha_recepcion = '';

    public string $observaciones_recepcion = '';

    /** @return array<string, mixed> */
    public function validarParaCrear(): array
    {
        return $this->datosValidados();
    }

    /** @return array<string, mixed> */
    public function validarParaActualizar(Recepcion $recepcion): array
    {
        return $this->datosValidados($recepcion);
    }

    public function llenarDesde(Recepcion $recepcion): void
    {
        $this->compra_id = $recepcion->compra_id;
        $this->almacen_id = $recepcion->almacen_id;
        $this->empleado_id = $recepcion->empleado_id;
        $this->fecha_recepcion = $recepcion->fecha_recepcion_local?->format('Y-m-d\TH:i') ?? '';
        $this->observaciones_recepcion = $recepcion->observaciones_recepcion ?? '';
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->fecha_recepcion = now(
            (string) config('app.display_timezone', 'America/La_Paz'),
        )->format('Y-m-d\TH:i');
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    private function datosValidados(?Recepcion $recepcion = null): array
    {
        $this->observaciones_recepcion = Str::squish($this->observaciones_recepcion);

        $datos = $this->validate([
            'compra_id' => ['nullable', 'integer', Rule::exists(Compra::class, 'id')],
            'almacen_id' => ['required', 'integer', Rule::exists(Almacen::class, 'id')],
            'fecha_recepcion' => ['required', 'date_format:Y-m-d\TH:i'],
            'observaciones_recepcion' => ['nullable', 'string', 'max:2000', 'not_regex:/[<>\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
        ], $this->messages(), $this->validationAttributes());

        $errores = [];
        $almacenActualPermitido = $recepcion?->almacen_id === $this->almacen_id;

        if (! $almacenActualPermitido && ! Almacen::query()
            ->whereKey($this->almacen_id)
            ->where('estado_almacen', true)
            ->exists()) {
            $errores['form.almacen_id'] = 'Selecciona un almacén activo.';
        }

        if ($this->compra_id !== null) {
            $compra = Compra::query()->find($this->compra_id);

            if ($compra?->estado_compra !== EstadoCompra::Registrada) {
                $errores['form.compra_id'] = 'Solo se pueden recibir compras registradas.';
            }
        }

        if ($recepcion === null && ! Empleado::query()
            ->where('user_id', auth()->id())
            ->where('activo_empleado', true)
            ->exists()) {
            $errores['form.empleado_id'] = 'Tu cuenta debe estar vinculada a un empleado activo para registrar recepciones.';
        }

        $zonaHoraria = (string) config('app.display_timezone', 'America/La_Paz');
        $fechaRecepcion = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $this->fecha_recepcion, $zonaHoraria);

        if ($fechaRecepcion->isAfter(now($zonaHoraria)->endOfMinute())) {
            $errores['form.fecha_recepcion'] = 'La fecha de recepción no puede estar en el futuro.';
        }

        if ($this->compra_id !== null) {
            $fechaCompra = Compra::query()->whereKey($this->compra_id)->value('fecha_compra');

            if ($fechaCompra !== null && $fechaRecepcion->utc()->isBefore(CarbonImmutable::parse($fechaCompra, 'UTC'))) {
                $errores['form.fecha_recepcion'] = 'La recepción no puede ser anterior a la fecha de la compra.';
            }
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }

        $datos['observaciones_recepcion'] = $datos['observaciones_recepcion'] === ''
            ? null
            : $datos['observaciones_recepcion'];

        return $datos;
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'compra_id.exists' => 'La compra seleccionada no existe.',
            'almacen_id.required' => 'Selecciona el almacén donde llegó la mercadería.',
            'almacen_id.exists' => 'El almacén seleccionado no existe.',
            'fecha_recepcion.required' => 'Indica la fecha y hora de la recepción.',
            'fecha_recepcion.date_format' => 'La fecha y hora de la recepción no es válida.',
            'observaciones_recepcion.max' => 'Las observaciones no pueden superar los 2000 caracteres.',
            'observaciones_recepcion.not_regex' => 'Las observaciones contienen caracteres no permitidos.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'compra_id' => 'compra',
            'almacen_id' => 'almacén',
            'fecha_recepcion' => 'fecha de recepción',
            'observaciones_recepcion' => 'observaciones',
        ];
    }
}
