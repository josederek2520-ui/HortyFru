<?php

namespace App\Livewire\Forms;

use App\Models\Articulo;
use App\Models\Pedido;
use App\Models\PresentacionArticulo;
use App\Models\Sucursal;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class FormularioPedido extends Form
{
    public ?int $sucursal_id = null;

    public string $fecha_requerida_pedido = '';

    public string $observaciones_pedido = '';

    /** @var list<array{articulo_id: int|null, presentacion_articulo_id: int|null, cantidad_solicitada_detalle_pedido: string, observaciones_detalle_pedido: string}> */
    public array $detalles = [];

    /** @return array<string, mixed> */
    public function validar(?Pedido $pedido = null): array
    {
        $this->normalizar();

        $datos = $this->validate([
            'sucursal_id' => ['required', 'integer', Rule::exists(Sucursal::class, 'id')],
            'fecha_requerida_pedido' => ['required', 'date_format:Y-m-d'],
            'observaciones_pedido' => ['nullable', 'string', 'max:2000', 'not_regex:/[<>\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
            'detalles' => ['required', 'array', 'min:1', 'max:100'],
            'detalles.*.articulo_id' => ['required', 'integer', Rule::exists(Articulo::class, 'id')],
            'detalles.*.presentacion_articulo_id' => ['required', 'integer', Rule::exists(PresentacionArticulo::class, 'id')],
            'detalles.*.cantidad_solicitada_detalle_pedido' => ['required', 'numeric', 'decimal:0,3', 'between:0.001,999999999.999'],
            'detalles.*.observaciones_detalle_pedido' => ['nullable', 'string', 'max:500', 'not_regex:/[<>\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
        ], $this->messages(), $this->validationAttributes());

        $this->validarReglasDeNegocio($pedido);
        $datos['observaciones_pedido'] = $datos['observaciones_pedido'] ?: null;

        foreach ($datos['detalles'] as &$detalle) {
            $detalle['observaciones_detalle_pedido'] = $detalle['observaciones_detalle_pedido'] ?: null;
        }

        return $datos;
    }

    public function llenarDesde(Pedido $pedido): void
    {
        $pedido->loadMissing('detalles');
        $this->sucursal_id = $pedido->sucursal_id;
        $this->fecha_requerida_pedido = $pedido->fecha_requerida_pedido->toDateString();
        $this->observaciones_pedido = $pedido->observaciones_pedido ?? '';
        $this->detalles = $pedido->detalles->map(fn ($detalle): array => [
            'articulo_id' => $detalle->articulo_id,
            'presentacion_articulo_id' => $detalle->presentacion_articulo_id,
            'cantidad_solicitada_detalle_pedido' => $detalle->cantidad_solicitada_detalle_pedido,
            'observaciones_detalle_pedido' => $detalle->observaciones_detalle_pedido ?? '',
        ])->all();
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->fecha_requerida_pedido = today()->addDay()->toDateString();
        $this->detalles = [$this->detalleVacio()];
        $this->resetValidation();
    }

    /** @return array{articulo_id: null, presentacion_articulo_id: null, cantidad_solicitada_detalle_pedido: string, observaciones_detalle_pedido: string} */
    public function detalleVacio(): array
    {
        return [
            'articulo_id' => null,
            'presentacion_articulo_id' => null,
            'cantidad_solicitada_detalle_pedido' => '',
            'observaciones_detalle_pedido' => '',
        ];
    }

    private function normalizar(): void
    {
        $this->observaciones_pedido = Str::squish($this->observaciones_pedido);

        foreach ($this->detalles as &$detalle) {
            $detalle['cantidad_solicitada_detalle_pedido'] = trim((string) ($detalle['cantidad_solicitada_detalle_pedido'] ?? ''));
            $detalle['observaciones_detalle_pedido'] = Str::squish((string) ($detalle['observaciones_detalle_pedido'] ?? ''));
        }
    }

    private function validarReglasDeNegocio(?Pedido $pedido): void
    {
        $errores = [];
        $sucursalActualPermitida = $pedido?->sucursal_id === $this->sucursal_id;

        if (! $sucursalActualPermitida && ! Sucursal::query()->availableForOperations()->whereKey($this->sucursal_id)->exists()) {
            $errores['form.sucursal_id'] = 'La sucursal o su cliente ya no están activos.';
        }

        if ($this->fecha_requerida_pedido < today()->toDateString()) {
            $errores['form.fecha_requerida_pedido'] = 'La fecha requerida no puede ser anterior a hoy.';
        }

        $idsActuales = $pedido?->detalles()->pluck('presentacion_articulo_id')->all() ?? [];
        $presentaciones = PresentacionArticulo::query()
            ->whereIn('id', collect($this->detalles)->pluck('presentacion_articulo_id')->filter()->all())
            ->get()
            ->keyBy('id');
        $combinaciones = [];

        foreach ($this->detalles as $indice => $detalle) {
            $presentacion = $presentaciones->get($detalle['presentacion_articulo_id']);
            $esPresentacionActual = in_array($detalle['presentacion_articulo_id'], $idsActuales, true);

            if ($presentacion === null
                || $presentacion->articulo_id !== $detalle['articulo_id']
                || ((! $presentacion->uso_presentacion_articulo->permitePedido()
                    || ! $presentacion->estado_presentacion_articulo) && ! $esPresentacionActual)) {
                $errores["form.detalles.{$indice}.presentacion_articulo_id"] = 'Selecciona una presentación activa para pedidos del producto indicado.';

                continue;
            }

            if (! $presentacion->permite_fraccion_presentacion_articulo
                && str_contains((string) $detalle['cantidad_solicitada_detalle_pedido'], '.')
                && (float) $detalle['cantidad_solicitada_detalle_pedido'] !== floor((float) $detalle['cantidad_solicitada_detalle_pedido'])) {
                $errores["form.detalles.{$indice}.cantidad_solicitada_detalle_pedido"] = 'Esta presentación solo admite cantidades enteras.';
            }

            $clave = $detalle['articulo_id'].'-'.$detalle['presentacion_articulo_id'];

            if (isset($combinaciones[$clave])) {
                $errores["form.detalles.{$indice}.presentacion_articulo_id"] = 'Este producto y presentación ya están agregados al pedido.';
            }

            $combinaciones[$clave] = true;
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'sucursal_id.required' => 'Selecciona la sucursal que realizó el pedido.',
            'sucursal_id.exists' => 'La sucursal seleccionada no existe.',
            'fecha_requerida_pedido.required' => 'Indica para qué fecha se requiere el pedido.',
            'fecha_requerida_pedido.date_format' => 'La fecha requerida no es válida.',
            'detalles.required' => 'Agrega al menos un producto al pedido.',
            'detalles.min' => 'Agrega al menos un producto al pedido.',
            'detalles.*.articulo_id.required' => 'Selecciona un producto.',
            'detalles.*.presentacion_articulo_id.required' => 'Selecciona una presentación.',
            'detalles.*.cantidad_solicitada_detalle_pedido.required' => 'Ingresa la cantidad solicitada.',
            'detalles.*.cantidad_solicitada_detalle_pedido.numeric' => 'La cantidad debe ser numérica.',
            'detalles.*.cantidad_solicitada_detalle_pedido.decimal' => 'La cantidad admite hasta 3 decimales.',
            'detalles.*.cantidad_solicitada_detalle_pedido.between' => 'La cantidad debe ser mayor que cero.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'sucursal_id' => 'sucursal',
            'fecha_requerida_pedido' => 'fecha requerida',
            'observaciones_pedido' => 'observaciones',
            'detalles' => 'productos',
            'detalles.*.articulo_id' => 'producto',
            'detalles.*.presentacion_articulo_id' => 'presentación',
            'detalles.*.cantidad_solicitada_detalle_pedido' => 'cantidad',
            'detalles.*.observaciones_detalle_pedido' => 'observación del producto',
        ];
    }
}
