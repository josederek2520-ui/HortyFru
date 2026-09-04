<?php

namespace App\Actions\Pedidos;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Models\PresentacionArticulo;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrearPedidoAction
{
    /** @param array{sucursal_id: int, fecha_requerida_pedido: string, observaciones_pedido: ?string, detalles: list<array{articulo_id: int, presentacion_articulo_id: int, cantidad_solicitada_detalle_pedido: string, observaciones_detalle_pedido: ?string}>} $datos */
    public function __invoke(array $datos, User $usuario): Pedido
    {
        return DB::transaction(function () use ($datos, $usuario): Pedido {
            $detalles = $this->prepararDetalles($datos['detalles']);
            $pedido = new Pedido;
            $pedido->disableLogging();
            $pedido->fill([
                'sucursal_id' => $datos['sucursal_id'],
                'fecha_pedido' => now(),
                'fecha_requerida_pedido' => $datos['fecha_requerida_pedido'],
                'estado_pedido' => EstadoPedido::Pendiente,
                'observaciones_pedido' => $datos['observaciones_pedido'],
                'registrado_por' => $usuario->id,
            ]);
            $pedido->save();
            $pedido->update([
                'codigo_pedido' => sprintf('PED-%06d', $pedido->id),
            ]);
            $pedido->detalles()->createMany($detalles);
            $pedido->enableLogging();

            activity(ActivityLogName::Orders->value)
                ->event(ActivityEvent::Created->value)
                ->performedOn($pedido)
                ->causedBy($usuario)
                ->withProperties(['attributes' => $this->datosAuditoria($pedido, $detalles)])
                ->log('Pedido registrado');

            return $pedido->load(['sucursal.cliente', 'detalles.articulo', 'detalles.presentacionArticulo']);
        });
    }

    /** @param list<array<string, mixed>> $detalles
     * @return list<array<string, mixed>>
     */
    private function prepararDetalles(array $detalles): array
    {
        $presentaciones = PresentacionArticulo::query()
            ->whereIn('id', collect($detalles)->pluck('presentacion_articulo_id')->all())
            ->where('estado_presentacion_articulo', true)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        return collect($detalles)->map(function (array $detalle, int $indice) use ($presentaciones): array {
            $presentacion = $presentaciones->get($detalle['presentacion_articulo_id']);

            if ($presentacion === null
                || $presentacion->articulo_id !== $detalle['articulo_id']
                || ! $presentacion->uso_presentacion_articulo->permitePedido()) {
                throw ValidationException::withMessages([
                    "form.detalles.{$indice}.presentacion_articulo_id" => 'La presentación seleccionada no está disponible para pedidos.',
                ]);
            }

            return [
                'articulo_id' => $detalle['articulo_id'],
                'presentacion_articulo_id' => $presentacion->id,
                'cantidad_solicitada_detalle_pedido' => $detalle['cantidad_solicitada_detalle_pedido'],
                'equivalencia_base_aplicada_detalle_pedido' => $presentacion->equivalencia_base_presentacion_articulo,
                'observaciones_detalle_pedido' => $detalle['observaciones_detalle_pedido'],
            ];
        })->all();
    }

    /** @param list<array<string, mixed>> $detalles
     * @return array<string, mixed>
     */
    private function datosAuditoria(Pedido $pedido, array $detalles): array
    {
        return [
            'codigo_pedido' => $pedido->codigo_pedido,
            'sucursal_id' => $pedido->sucursal_id,
            'fecha_pedido' => $pedido->fecha_pedido->toDateTimeString(),
            'fecha_requerida_pedido' => $pedido->fecha_requerida_pedido->toDateString(),
            'estado_pedido' => $pedido->estado_pedido->value,
            'observaciones_pedido' => $pedido->observaciones_pedido,
            'registrado_por' => $pedido->registrado_por,
            'cantidad_items_pedido' => count($detalles),
            'detalles_pedido' => collect($detalles)->map(fn (array $detalle): array => [
                'articulo_id' => $detalle['articulo_id'],
                'presentacion_articulo_id' => $detalle['presentacion_articulo_id'],
                'cantidad' => $detalle['cantidad_solicitada_detalle_pedido'],
                'equivalencia_base_aplicada' => $detalle['equivalencia_base_aplicada_detalle_pedido'],
            ])->all(),
        ];
    }
}
