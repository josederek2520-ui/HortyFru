<?php

namespace App\Livewire\Panel\Pedidos;

use App\Actions\Pedidos\CancelarPedidoAction;
use App\Enums\EstadoPedido;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    #[Url(as: 'fecha', except: '')]
    public string $requiredDate = '';

    public bool $showDetailModal = false;

    public bool $showCancelModal = false;

    public ?int $viewingOrderId = null;

    public ?int $cancellingOrderId = null;

    public string $cancellingOrderCode = '';

    public string $cancellationReason = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Pedido::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedRequiredDate(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'requiredDate');
        $this->resetPage();
    }

    public function openDetailModal(int $orderId): void
    {
        $pedido = Pedido::query()->findOrFail($orderId);
        Gate::authorize('view', $pedido);
        $this->viewingOrderId = $pedido->id;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->viewingOrderId = null;
    }

    public function openCancelModal(int $orderId): void
    {
        $pedido = Pedido::query()->findOrFail($orderId);
        Gate::authorize('cancel', $pedido);
        $this->cancellingOrderId = $pedido->id;
        $this->cancellingOrderCode = $pedido->codigo_pedido;
        $this->cancellationReason = '';
        $this->resetValidation('cancellationReason');
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
        $this->reset('cancellingOrderId', 'cancellingOrderCode', 'cancellationReason');
        $this->resetValidation('cancellationReason');
    }

    public function cancelOrder(CancelarPedidoAction $cancelar): void
    {
        $this->cancellationReason = Str::squish($this->cancellationReason);
        $this->validate([
            'cancellationReason' => ['required', 'string', 'min:5', 'max:500', 'not_regex:/[<>\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
        ], [
            'cancellationReason.required' => 'Explica por qué se cancela el pedido.',
            'cancellationReason.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $pedido = Pedido::query()->findOrFail($this->cancellingOrderId);
        Gate::authorize('cancel', $pedido);
        /** @var User $usuario */
        $usuario = auth()->user();
        $cancelar($pedido, $this->cancellationReason, $usuario);
        $codigo = $pedido->codigo_pedido;
        $this->closeCancelModal();
        $this->toast('Pedido cancelado', "El pedido {$codigo} fue cancelado y conserva su historial.", 'warning');
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return Pedido::query()
            ->with([
                'sucursal:id,cliente_id,nombre_sucursal',
                'sucursal.cliente:id,razon_social',
                'registradoPor:id,name,email',
                'registradoPor.empleado:id,user_id,nombre_empleado,apellido_empleado',
            ])
            ->withCount([
                'detalles',
                'detalles as detalles_preparados_count' => fn (Builder $query): Builder => $query
                    ->where('preparado_detalle_pedido', true),
            ])
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('codigo_pedido', 'like', "%{$this->search}%")
                        ->orWhereHas('sucursal', function (Builder $query): void {
                            $query->where('nombre_sucursal', 'like', "%{$this->search}%")
                                ->orWhereHas('cliente', fn (Builder $query): Builder => $query->where('razon_social', 'like', "%{$this->search}%"));
                        });
                });
            })
            ->when($this->status !== 'all', fn (Builder $query): Builder => $query->where('estado_pedido', $this->status))
            ->when($this->requiredDate !== '', fn (Builder $query): Builder => $query->whereDate('fecha_requerida_pedido', $this->requiredDate))
            ->latest('fecha_pedido')
            ->paginate(10);
    }

    /** @return array{all: int, PENDIENTE: int, PREPARADO: int, CANCELADO: int} */
    #[Computed]
    public function statusCounts(): array
    {
        $counts = Pedido::query()
            ->selectRaw('estado_pedido, COUNT(*) as total')
            ->groupBy('estado_pedido')
            ->pluck('total', 'estado_pedido');

        return [
            'all' => (int) $counts->sum(),
            EstadoPedido::Pendiente->value => (int) $counts->get(EstadoPedido::Pendiente->value, 0),
            EstadoPedido::Preparado->value => (int) $counts->get(EstadoPedido::Preparado->value, 0),
            EstadoPedido::Cancelado->value => (int) $counts->get(EstadoPedido::Cancelado->value, 0),
        ];
    }

    #[Computed]
    public function viewingOrder(): ?Pedido
    {
        if ($this->viewingOrderId === null) {
            return null;
        }

        return Pedido::query()->with([
            'sucursal.cliente', 'registradoPor.empleado', 'canceladoPor.empleado',
            'detalles.articulo.unidadMedida', 'detalles.presentacionArticulo',
        ])->find($this->viewingOrderId);
    }

    /** @return list<EstadoPedido> */
    public function statuses(): array
    {
        return EstadoPedido::cases();
    }

    public function render(): View
    {
        return view('livewire.panel.pedidos.index');
    }
}
