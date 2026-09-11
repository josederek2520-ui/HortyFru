<?php

namespace App\Livewire\Panel\Pedidos;

use App\Actions\Pedidos\ActualizarPedidoAction;
use App\Livewire\Forms\FormularioPedido;
use App\Models\Articulo;
use App\Models\Pedido;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Edit extends Component
{
    public FormularioPedido $form;

    #[Locked]
    public int $pedidoId;

    #[Locked]
    public string $codigoPedido = '';

    public function mount(int $pedidoId): void
    {
        $pedido = Pedido::query()->with('detalles')->findOrFail($pedidoId);
        Gate::authorize('update', $pedido);
        $this->pedidoId = $pedido->id;
        $this->codigoPedido = $pedido->codigo_pedido;
        $this->form->limpiar();
        $this->form->llenarDesde($pedido);
    }

    public function addDetail(): void
    {
        if (count($this->form->detalles) < 100) {
            $this->form->detalles[] = $this->form->detalleVacio();
        }
    }

    public function removeDetail(int $index): void
    {
        if (count($this->form->detalles) === 1) {
            return;
        }

        unset($this->form->detalles[$index]);
        $this->form->detalles = array_values($this->form->detalles);
        $this->form->resetValidation();
    }

    public function selectArticle(int $index, string $articleId): void
    {
        $articulo = $this->articleOptions->firstWhere('id', (int) $articleId);
        $this->form->detalles[$index]['articulo_id'] = $articulo?->id;
        $this->form->detalles[$index]['presentacion_articulo_id'] = $articulo?->presentaciones
            ->firstWhere('predeterminada_pedido_presentacion_articulo', true)?->id
            ?? $articulo?->presentaciones->first()?->id;
        $this->form->resetValidation();
    }

    public function save(ActualizarPedidoAction $actualizarPedido): void
    {
        $pedido = Pedido::query()->with('detalles')->findOrFail($this->pedidoId);
        Gate::authorize('update', $pedido);
        /** @var User $usuario */
        $usuario = auth()->user();
        $pedido = $actualizarPedido($pedido, $this->form->validar($pedido), $usuario);

        session()->flash('toast', [
            'title' => 'Pedido actualizado',
            'message' => "Los datos de {$pedido->codigo_pedido} fueron actualizados.",
            'type' => 'info',
        ]);

        $this->redirectRoute('panel.pedidos.index', navigate: true);
    }

    #[Computed]
    public function branchOptions(): Collection
    {
        $pedido = Pedido::query()->select(['id', 'sucursal_id'])->findOrFail($this->pedidoId);

        return Sucursal::query()
            ->select(['id', 'cliente_id', 'nombre_sucursal', 'activo_sucursal'])
            ->with('cliente:id,razon_social,activo_cliente')
            ->where(function (Builder $query) use ($pedido): void {
                $query->availableForOperations()->orWhere('id', $pedido->sucursal_id);
            })
            ->orderBy('nombre_sucursal')
            ->get();
    }

    #[Computed]
    public function articleOptions(): Collection
    {
        $idsPresentacionesActuales = Pedido::query()
            ->findOrFail($this->pedidoId)
            ->detalles()
            ->pluck('presentacion_articulo_id')
            ->all();

        return Articulo::query()
            ->select(['id', 'nombre_articulo', 'unidad_medida_id', 'estado_articulo', 'categoria_articulo_id'])
            ->with([
                'categoriaArticulo:id,nombre_categoria_articulo',
                'unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida',
                'presentaciones' => function ($query) use ($idsPresentacionesActuales): void {
                    $query->where(function (Builder $query) use ($idsPresentacionesActuales): void {
                        $query->where(function (Builder $query): void {
                            $query->where('estado_presentacion_articulo', true)
                                ->whereIn('uso_presentacion_articulo', ['PEDIDO', 'AMBOS']);
                        })->orWhereIn('id', $idsPresentacionesActuales);
                    })->orderByDesc('predeterminada_pedido_presentacion_articulo')
                        ->orderBy('nombre_presentacion_articulo');
                },
            ])
            ->where(function (Builder $query) use ($idsPresentacionesActuales): void {
                $query->where(function (Builder $query): void {
                    $query->where('estado_articulo', true)
                        ->whereHas('presentaciones', fn (Builder $query): Builder => $query
                            ->where('estado_presentacion_articulo', true)
                            ->whereIn('uso_presentacion_articulo', ['PEDIDO', 'AMBOS']));
                })->orWhereHas('presentaciones', fn (Builder $query): Builder => $query->whereIn('id', $idsPresentacionesActuales));
            })
            ->orderBy('nombre_articulo')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.panel.pedidos.create', [
            'editing' => true,
            'codigoPedido' => $this->codigoPedido,
        ]);
    }
}
