<?php

namespace App\Actions\Pedidos;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Pedido;
use App\Models\PresentacionArticulo;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActualizarPedidoAction
{
    /** @param array{sucursal_id: int, fecha_requerida_pedido: string, observaciones_pedido: ?string, detalles: list<array{articulo_id: int, presentacion_articulo_id: int, cantidad_solicitada_detalle_pedido: string, observaciones_detalle_pedido: ?string}>} $datos */
    public function __invoke(Pedido $pedido, array $datos, User $usuario): Pedido
    {
        return DB::transaction(function () use ($pedido, $datos, $usuario): Pedido {
            $pedido = Pedido::query()->with('detalles')->lockForUpdate()->findOrFail($pedido->id);

            if (! $pedido->estaPendiente()) {
                throw ValidationException::withMessages(['form.pedido' => 'Solo se pueden editar pedidos pendientes.']);
            }

            if ($pedido->preparacionIniciada()) {
                throw ValidationException::withMessages([
                    'form.pedido' => 'No se puede editar porque la preparación del pedido ya comenzó.',
                ]);
            }

            $anteriores = $this->datosAuditoria($pedido);
            $detalles = $this->prepararDetalles($datos['detalles'], $pedido);
            $pedido->disableLogging();
            $pedido->update([
                'sucursal_id' => $datos['sucursal_id'],
                'fecha_requerida_pedido' => $datos['fecha_requerida_pedido'],
                'observaciones_pedido' => $datos['observaciones_pedido'],
            ]);
            $pedido->detalles()->delete();
            $pedido->detalles()->createMany($detalles);
            $pedido->enableLogging();
            $pedido->load('detalles');

            activity(ActivityLogName::Orders->value)
                ->event(ActivityEvent::Updated->value)
                ->performedOn($pedido)
                ->causedBy($usuario)
                ->withProperties(['old' => $anteriores, 'attributes' => $this->datosAuditoria($pedido)])
                ->log('Pedido actualizado');

            return $pedido->load(['sucursal.cliente', 'detalles.articulo', 'detalles.presentacionArticulo']);
        });
    }

    /** @param list<array<string, mixed>> $detalles
     * @return list<array<string, mixed>>
     */
    private function prepararDetalles(array $detalles, Pedido $pedido): array
    {
        $presentacionesActuales = $pedido->detalles->pluck('presentacion_articulo_id')->all();
        $detallesActuales = $pedido->detalles->keyBy(
            fn ($detalle): string => $detalle->articulo_id.'-'.$detalle->presentacion_articulo_id,
        );
        $presentaciones = PresentacionArticulo::query()
            ->whereIn('id', collect($detalles)->pluck('presentacion_articulo_id')->all())
            ->where(function ($query) use ($presentacionesActuales): void {
                $query->where('estado_presentacion_articulo', true)
                    ->orWhereIn('id', $presentacionesActuales);
            })
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        return collect($detalles)->map(function (array $detalle, int $indice) use ($detallesActuales, $presentaciones, $presentacionesActuales): array {
            $presentacion = $presentaciones->get($detalle['presentacion_articulo_id']);
            $esPresentacionActual = in_array($detalle['presentacion_articulo_id'], $presentacionesActuales, true);

            if ($presentacion === null
                || $presentacion->articulo_id !== $detalle['articulo_id']
                || (! $presentacion->uso_presentacion_articulo->permitePedido() && ! $esPresentacionActual)) {
                throw ValidationException::withMessages([
                    "form.detalles.{$indice}.presentacion_articulo_id" => 'La presentación seleccionada no está disponible para pedidos.',
                ]);
            }

            $detalleActual = $detallesActuales->get($detalle['articulo_id'].'-'.$presentacion->id);

            return [
                'articulo_id' => $detalle['articulo_id'],
                'presentacion_articulo_id' => $presentacion->id,
                'cantidad_solicitada_detalle_pedido' => $detalle['cantidad_solicitada_detalle_pedido'],
                'equivalencia_base_aplicada_detalle_pedido' => $detalleActual?->equivalencia_base_aplicada_detalle_pedido
                    ?? $presentacion->equivalencia_base_presentacion_articulo,
                'observaciones_detalle_pedido' => $detalle['observaciones_detalle_pedido'],
            ];
        })->all();
    }

    /** @return array<string, mixed> */
    private function datosAuditoria(Pedido $pedido): array
    {
        return [
            'sucursal_id' => $pedido->sucursal_id,
            'fecha_requerida_pedido' => $pedido->fecha_requerida_pedido->toDateString(),
            'observaciones_pedido' => $pedido->observaciones_pedido,
            'cantidad_items_pedido' => $pedido->detalles->count(),
            'detalles_pedido' => $pedido->detalles->map(fn ($detalle): array => [
                'articulo_id' => $detalle->articulo_id,
                'presentacion_articulo_id' => $detalle->presentacion_articulo_id,
                'cantidad' => $detalle->cantidad_solicitada_detalle_pedido,
                'equivalencia_base_aplicada' => $detalle->equivalencia_base_aplicada_detalle_pedido,
            ])->all(),
        ];
    }
}
