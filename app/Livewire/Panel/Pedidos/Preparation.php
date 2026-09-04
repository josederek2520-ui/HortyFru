<?php

namespace App\Livewire\Panel\Pedidos;

use App\Actions\Pedidos\CambiarPreparacionDetallePedidoAction;
use App\Enums\EstadoPedido;
use App\Enums\PermissionName;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class Preparation extends Component
{
    use InteractsWithToasts;

    #[Url(as: 'fecha')]
    public string $requiredDate = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'estado', except: 'all')]
    public string $preparationStatus = 'all';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(PermissionName::OrdersPrepare->value), 403);
        $this->requiredDate = $this->requiredDate ?: today()->addDay()->toDateString();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'preparationStatus');
    }

    public function setPrepared(
        int $detailId,
        bool $prepared,
        CambiarPreparacionDetallePedidoAction $cambiarPreparacion,
    ): void {
        $detallePedido = DetallePedido::query()->with('pedido')->findOrFail($detailId);
        Gate::authorize('prepare', $detallePedido->pedido);
        /** @var User $usuario */
        $usuario = auth()->user();
        $pedido = $cambiarPreparacion($detallePedido, $prepared, $usuario);

        unset($this->allDetails, $this->details, $this->orders, $this->preparationOrders, $this->preparationRows);

        if ($pedido->estado_pedido === EstadoPedido::Preparado) {
            $this->toast(
                'Pedido preparado',
                "Todas las líneas de {$pedido->codigo_pedido} quedaron listas.",
                'success',
            );
        }
    }

    #[Computed]
    public function allDetails(): EloquentCollection
    {
        return DetallePedido::query()
            ->with([
                'pedido:id,codigo_pedido,sucursal_id,fecha_requerida_pedido,estado_pedido',
                'pedido.sucursal:id,cliente_id,nombre_sucursal',
                'pedido.sucursal.cliente:id,razon_social',
                'articulo:id,nombre_articulo,unidad_medida_id,imagen_articulo',
                'articulo.unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida',
                'presentacionArticulo:id,nombre_presentacion_articulo',
            ])
            ->whereHas('pedido', fn (Builder $query): Builder => $query
                ->whereDate('fecha_requerida_pedido', $this->requiredDate)
                ->whereIn('estado_pedido', [EstadoPedido::Pendiente->value, EstadoPedido::Preparado->value]))
            ->orderBy('articulo_id')
            ->orderBy('presentacion_articulo_id')
            ->get();
    }

    #[Computed]
    public function details(): EloquentCollection
    {
        $detalles = match ($this->preparationStatus) {
            'pending' => $this->allDetails->where('preparado_detalle_pedido', false),
            'prepared' => $this->allDetails->where('preparado_detalle_pedido', true),
            default => $this->allDetails,
        };

        if ($this->search === '') {
            return new EloquentCollection($detalles->values());
        }

        $busqueda = Str::lower($this->search);

        return new EloquentCollection($detalles
            ->filter(fn (DetallePedido $detalle): bool => Str::contains(Str::lower(implode(' ', [
                $detalle->articulo->nombre_articulo,
                $detalle->presentacionArticulo->nombre_presentacion_articulo,
                $detalle->pedido->codigo_pedido,
                $detalle->pedido->sucursal->nombre_sucursal,
                $detalle->pedido->sucursal->cliente->razon_social,
            ])), $busqueda))
            ->values());
    }

    #[Computed]
    public function orders(): Collection
    {
        return $this->allDetails
            ->groupBy('pedido_id')
            ->map(function (EloquentCollection $detalles): Pedido {
                $pedido = $detalles->first()->pedido;
                $pedido->setRelation('detalles', $detalles);

                return $pedido;
            })
            ->sortBy(fn (Pedido $pedido): string => $pedido->sucursal->nombre_sucursal)
            ->values();
    }

    #[Computed]
    public function preparationOrders(): Collection
    {
        $allDetailsByOrder = $this->allDetails->groupBy('pedido_id');

        return $this->details
            ->groupBy('pedido_id')
            ->map(function (EloquentCollection $visibleDetails, int $orderId) use ($allDetailsByOrder): array {
                $allOrderDetails = $allDetailsByOrder->get($orderId, collect());

                return [
                    'pedido' => $visibleDetails->first()->pedido,
                    'detalles' => $visibleDetails,
                    'total' => $allOrderDetails->count(),
                    'preparados' => $allOrderDetails->where('preparado_detalle_pedido', true)->count(),
                ];
            })
            ->sortBy(fn (array $group): string => $group['pedido']->sucursal->nombre_sucursal)
            ->values();
    }

    #[Computed]
    public function preparationRows(): Collection
    {
        return $this->details
            ->groupBy(fn (DetallePedido $detalle): string => $detalle->articulo_id.'-'.$detalle->presentacion_articulo_id)
            ->map(function (Collection $detalles): array {
                /** @var DetallePedido $primero */
                $primero = $detalles->first();

                return [
                    'key' => $primero->articulo_id.'-'.$primero->presentacion_articulo_id,
                    'articulo' => $primero->articulo,
                    'presentacion' => $primero->presentacionArticulo,
                    'detalles' => $detalles->keyBy('pedido_id'),
                    'cantidad_total' => $detalles->sum(
                        fn (DetallePedido $detalle): float => (float) $detalle->cantidad_solicitada_detalle_pedido,
                    ),
                    'preparados' => $detalles->where('preparado_detalle_pedido', true)->count(),
                ];
            })
            ->sortBy(fn (array $fila): string => $fila['articulo']->nombre_articulo)
            ->values();
    }

    #[Computed]
    public function totalDetails(): int
    {
        return $this->allDetails->count();
    }

    #[Computed]
    public function preparedDetails(): int
    {
        return $this->allDetails->where('preparado_detalle_pedido', true)->count();
    }

    #[Computed]
    public function pendingDetails(): int
    {
        return $this->totalDetails - $this->preparedDetails;
    }

    #[Computed]
    public function progressPercentage(): int
    {
        return $this->totalDetails === 0
            ? 0
            : (int) round(($this->preparedDetails / $this->totalDetails) * 100);
    }

    public function render(): View
    {
        return view('livewire.panel.pedidos.preparation');
    }
}
