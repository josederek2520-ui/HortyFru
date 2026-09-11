<?php

namespace App\Livewire\Panel\Pedidos;

use App\Actions\Pedidos\CrearPedidoAction;
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
use Livewire\Component;

class Create extends Component
{
    public FormularioPedido $form;

    public function mount(): void
    {
        Gate::authorize('create', Pedido::class);
        $this->form->limpiar();
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

    public function save(CrearPedidoAction $crearPedido): void
    {
        Gate::authorize('create', Pedido::class);
        /** @var User $usuario */
        $usuario = auth()->user();
        $pedido = $crearPedido($this->form->validar(), $usuario);

        session()->flash('toast', [
            'title' => 'Pedido registrado',
            'message' => "El pedido {$pedido->codigo_pedido} fue registrado correctamente.",
            'type' => 'success',
        ]);

        $this->redirectRoute('panel.pedidos.index', navigate: true);
    }

    #[Computed]
    public function branchOptions(): Collection
    {
        return Sucursal::query()
            ->select(['id', 'cliente_id', 'nombre_sucursal'])
            ->with('cliente:id,razon_social')
            ->availableForOperations()
            ->orderBy('nombre_sucursal')
            ->get();
    }

    #[Computed]
    public function articleOptions(): Collection
    {
        return Articulo::query()
            ->select(['id', 'nombre_articulo', 'unidad_medida_id', 'categoria_articulo_id'])
            ->with([
                'categoriaArticulo:id,nombre_categoria_articulo',
                'unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida',
                'presentaciones' => function ($query): void {
                    $query->where('estado_presentacion_articulo', true)
                        ->whereIn('uso_presentacion_articulo', ['PEDIDO', 'AMBOS'])
                        ->orderByDesc('predeterminada_pedido_presentacion_articulo')
                        ->orderBy('nombre_presentacion_articulo');
                },
            ])
            ->where('estado_articulo', true)
            ->whereHas('presentaciones', fn (Builder $query): Builder => $query
                ->where('estado_presentacion_articulo', true)
                ->whereIn('uso_presentacion_articulo', ['PEDIDO', 'AMBOS']))
            ->orderBy('nombre_articulo')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.panel.pedidos.create', [
            'editing' => false,
            'codigoPedido' => null,
        ]);
    }
}
