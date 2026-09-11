<?php

namespace App\Livewire\Panel\Compras;

use App\Actions\Compras\EliminarDetalleCompraAction;
use App\Actions\Compras\GuardarDetalleCompraAction;
use App\Actions\Compras\RegistrarCompraAction;
use App\Enums\EstadoPedido;
use App\Enums\PrecioPorDetalleCompra;
use App\Enums\TipoEquivalenciaPresentacionArticulo;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\FormularioDetalleCompra;
use App\Models\Articulo;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\DetallePedido;
use App\Models\PresentacionArticulo;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Details extends Component
{
    use InteractsWithToasts;

    public FormularioDetalleCompra $form;

    #[Locked]
    public int $purchaseId;

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public bool $showRegisterModal = false;

    public ?int $editingDetailId = null;

    public ?int $deletingDetailId = null;

    public string $deletingArticleName = '';

    public function mount(Compra $compra): void
    {
        Gate::authorize('view', $compra);
        $this->purchaseId = $compra->id;
    }

    public function openCreateModal(): void
    {
        Gate::authorize('update', $this->purchase);
        $this->editingDetailId = null;
        $this->form->limpiar();
        $this->showFormModal = true;
        $this->dispatch('purchase-detail-form-opened');
    }

    public function openCreateModalFromNeed(int $articleId): void
    {
        Gate::authorize('update', $this->purchase);

        $necesidad = $this->purchaseNeeds->first(
            fn (array $fila): bool => $fila['articulo']->id === $articleId,
        );

        abort_if($necesidad === null || ! $necesidad['articulo']->estado_articulo, 404);

        $this->editingDetailId = null;
        $this->form->limpiar();
        $this->selectArticle((string) $articleId);
        $this->showFormModal = true;
        $this->dispatch('purchase-detail-form-opened');
    }

    public function openEditModal(int $detailId): void
    {
        Gate::authorize('update', $this->purchase);
        $detalle = $this->purchase->detalles()->findOrFail($detailId);
        $this->editingDetailId = $detalle->id;
        $this->form->limpiar();
        $this->form->llenarDesde($detalle);
        unset($this->articleOptions, $this->presentationOptions, $this->selectedPresentation, $this->selectedArticle, $this->hasFixedEquivalence);
        $this->showFormModal = true;
        $this->dispatch('purchase-detail-form-opened');
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingDetailId = null;
        $this->form->limpiar();
        unset($this->articleOptions, $this->presentationOptions, $this->selectedPresentation, $this->selectedArticle, $this->hasFixedEquivalence);
    }

    public function selectArticle(string $articleId): void
    {
        $this->form->articulo_id = $articleId;
        $this->form->presentacion_articulo_id = '';
        $this->form->cantidad_presentaciones_detalle_compra = '';
        $this->form->cantidad_real_detalle_compra = '';
        unset($this->presentationOptions, $this->selectedPresentation, $this->selectedArticle, $this->hasFixedEquivalence);

        if ($articleId === '') {
            $this->form->precio_por_detalle_compra = PrecioPorDetalleCompra::Presentacion->value;

            return;
        }

        $predeterminada = PresentacionArticulo::query()
            ->where('articulo_id', $articleId)
            ->where('estado_presentacion_articulo', true)
            ->where('predeterminada_compra_presentacion_articulo', true)
            ->whereIn('uso_presentacion_articulo', ['COMPRA', 'AMBOS'])
            ->value('id');

        $this->form->presentacion_articulo_id = $predeterminada === null ? '' : (string) $predeterminada;
        $this->form->precio_por_detalle_compra = $predeterminada === null
            ? PrecioPorDetalleCompra::UnidadBase->value
            : PrecioPorDetalleCompra::Presentacion->value;
        unset($this->selectedPresentation, $this->selectedArticle, $this->hasFixedEquivalence);
    }

    public function updatedFormPresentacionArticuloId(): void
    {
        $this->form->cantidad_presentaciones_detalle_compra = '';
        $this->form->cantidad_real_detalle_compra = '';
        $this->form->precio_por_detalle_compra = $this->form->presentacion_articulo_id === ''
            ? PrecioPorDetalleCompra::UnidadBase->value
            : PrecioPorDetalleCompra::Presentacion->value;
        unset($this->selectedPresentation, $this->hasFixedEquivalence);
    }

    public function save(GuardarDetalleCompraAction $guardarDetalle): void
    {
        $compra = $this->purchase;
        Gate::authorize('update', $compra);
        $detalle = $this->editingDetailId === null
            ? null
            : $compra->detalles()->findOrFail($this->editingDetailId);
        /** @var User $usuario */
        $usuario = auth()->user();
        $guardarDetalle($compra, $detalle, $this->form->validar(), $usuario);
        $titulo = $detalle === null ? 'Producto agregado' : 'Producto actualizado';
        $tipo = $detalle === null ? 'success' : 'info';
        $this->closeFormModal();
        $this->refreshPurchase();
        $this->toast($titulo, 'El total de la compra se recalculó correctamente.', $tipo);
    }

    public function openDeleteModal(int $detailId): void
    {
        Gate::authorize('update', $this->purchase);
        $detalle = $this->purchase->detalles()->with('articulo:id,nombre_articulo')->findOrFail($detailId);
        $this->deletingDetailId = $detalle->id;
        $this->deletingArticleName = $detalle->articulo->nombre_articulo;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->reset('deletingDetailId', 'deletingArticleName');
    }

    public function deleteDetail(EliminarDetalleCompraAction $eliminarDetalle): void
    {
        $compra = $this->purchase;
        Gate::authorize('update', $compra);
        $detalle = $compra->detalles()->findOrFail($this->deletingDetailId);
        /** @var User $usuario */
        $usuario = auth()->user();
        $eliminarDetalle($compra, $detalle, $usuario);
        $this->closeDeleteModal();
        $this->refreshPurchase();
        $this->toast('Producto eliminado', 'El producto fue retirado y el total se recalculó.', 'warning');
    }

    public function registerPurchase(RegistrarCompraAction $registrarCompra): void
    {
        $compra = $this->purchase;
        Gate::authorize('register', $compra);
        /** @var User $usuario */
        $usuario = auth()->user();
        $registrarCompra($compra, $usuario);
        $this->showRegisterModal = false;
        $this->refreshPurchase();
        $this->toast('Compra registrada', 'La compra quedó confirmada y ahora es de solo lectura.', 'success');
    }

    #[Computed]
    public function purchase(): Compra
    {
        $compra = Compra::query()
            ->with([
                'proveedor:id,nombre_proveedor,mercado_proveedor',
                'empleado:id,nombre_empleado,apellido_empleado',
                'detalles' => fn ($consulta) => $consulta->with([
                    'articulo:id,nombre_articulo,unidad_medida_id',
                    'presentacionArticulo:id,nombre_presentacion_articulo',
                    'unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida',
                ])->oldest('id'),
            ])
            ->findOrFail($this->purchaseId);
        Gate::authorize('view', $compra);

        return $compra;
    }

    /** @return EloquentCollection<int, Articulo> */
    #[Computed]
    public function articleOptions(): EloquentCollection
    {
        $articuloActual = $this->editingDetailId === null
            ? null
            : DetalleCompra::query()->whereKey($this->editingDetailId)->value('articulo_id');

        return Articulo::query()
            ->select(['id', 'nombre_articulo', 'categoria_articulo_id', 'unidad_medida_id', 'estado_articulo'])
            ->with([
                'categoriaArticulo:id,nombre_categoria_articulo',
                'unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida',
            ])
            ->where(fn ($consulta) => $consulta->where('estado_articulo', true)
                ->when($articuloActual !== null, fn ($consulta) => $consulta->orWhere('id', $articuloActual)))
            ->orderBy('nombre_articulo')
            ->get();
    }

    /** @return EloquentCollection<int, PresentacionArticulo> */
    #[Computed]
    public function presentationOptions(): EloquentCollection
    {
        if ($this->form->articulo_id === '') {
            return new EloquentCollection;
        }

        $presentacionActual = $this->editingDetailId === null
            ? null
            : DetalleCompra::query()->whereKey($this->editingDetailId)->value('presentacion_articulo_id');

        return PresentacionArticulo::query()
            ->where('articulo_id', $this->form->articulo_id)
            ->where(function ($consulta) use ($presentacionActual): void {
                $consulta->where(function ($consulta): void {
                    $consulta->where('estado_presentacion_articulo', true)
                        ->whereIn('uso_presentacion_articulo', ['COMPRA', 'AMBOS']);
                })->when($presentacionActual !== null, fn ($consulta) => $consulta->orWhere('id', $presentacionActual));
            })
            ->orderByDesc('predeterminada_compra_presentacion_articulo')
            ->orderBy('nombre_presentacion_articulo')
            ->get();
    }

    /**
     * @return Collection<int, array{
     *     key: string,
     *     articulo: Articulo,
     *     presentacion_pedido: PresentacionArticulo,
     *     presentacion_compra: ?PresentacionArticulo,
     *     sucursales: Collection<int, array{nombre: string, cantidad: float, pedidos: Collection<int, string>}>,
     *     cantidad_total: float,
     *     ya_agregado: bool
     * }>
     */
    #[Computed]
    public function purchaseNeeds(): Collection
    {
        $fechaRequerida = $this->purchase->fecha_compra_local->toDateString();
        $detallesCompra = $this->purchase->detalles;

        return DetallePedido::query()
            ->select([
                'id',
                'pedido_id',
                'articulo_id',
                'presentacion_articulo_id',
                'cantidad_solicitada_detalle_pedido',
            ])
            ->with([
                'pedido:id,codigo_pedido,sucursal_id,fecha_requerida_pedido,estado_pedido',
                'pedido.sucursal:id,nombre_sucursal',
                'articulo:id,nombre_articulo,categoria_articulo_id,unidad_medida_id,imagen_articulo,estado_articulo',
                'articulo.categoriaArticulo:id,nombre_categoria_articulo',
                'articulo.unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida',
                'articulo.presentaciones' => fn ($query) => $query
                    ->select([
                        'id',
                        'articulo_id',
                        'nombre_presentacion_articulo',
                        'tipo_equivalencia_presentacion_articulo',
                        'equivalencia_base_presentacion_articulo',
                    ])
                    ->where('estado_presentacion_articulo', true)
                    ->where('predeterminada_compra_presentacion_articulo', true)
                    ->whereIn('uso_presentacion_articulo', ['COMPRA', 'AMBOS'])
                    ->oldest('id'),
                'presentacionArticulo:id,nombre_presentacion_articulo',
            ])
            ->whereHas('pedido', fn (Builder $query): Builder => $query
                ->where('fecha_requerida_pedido', $fechaRequerida)
                ->whereIn('estado_pedido', [
                    EstadoPedido::Pendiente->value,
                    EstadoPedido::Preparado->value,
                ]))
            ->get()
            ->groupBy(fn (DetallePedido $detalle): string => $detalle->articulo_id.'-'.$detalle->presentacion_articulo_id)
            ->map(function (EloquentCollection $detalles) use ($detallesCompra): array {
                /** @var DetallePedido $primerDetalle */
                $primerDetalle = $detalles->first();
                $presentacionCompra = $primerDetalle->articulo->presentaciones->first();
                $sucursales = $detalles
                    ->groupBy(fn (DetallePedido $detalle): int => $detalle->pedido->sucursal_id)
                    ->map(function (EloquentCollection $detallesSucursal): array {
                        /** @var DetallePedido $primerDetalleSucursal */
                        $primerDetalleSucursal = $detallesSucursal->first();

                        return [
                            'nombre' => $primerDetalleSucursal->pedido->sucursal->nombre_sucursal,
                            'cantidad' => $detallesSucursal->sum(
                                fn (DetallePedido $detalle): float => (float) $detalle->cantidad_solicitada_detalle_pedido,
                            ),
                            'pedidos' => $detallesSucursal
                                ->pluck('pedido.codigo_pedido')
                                ->unique()
                                ->sort()
                                ->values(),
                        ];
                    })
                    ->sortBy('nombre')
                    ->values();

                return [
                    'key' => $primerDetalle->articulo_id.'-'.$primerDetalle->presentacion_articulo_id,
                    'articulo' => $primerDetalle->articulo,
                    'presentacion_pedido' => $primerDetalle->presentacionArticulo,
                    'presentacion_compra' => $presentacionCompra,
                    'sucursales' => $sucursales,
                    'cantidad_total' => $detalles->sum(
                        fn (DetallePedido $detalle): float => (float) $detalle->cantidad_solicitada_detalle_pedido,
                    ),
                    'ya_agregado' => $detallesCompra->contains(
                        fn (DetalleCompra $detalle): bool => $detalle->articulo_id === $primerDetalle->articulo_id
                            && $detalle->presentacion_articulo_id === $presentacionCompra?->id,
                    ),
                ];
            })
            ->sortBy(fn (array $fila): string => implode('|', [
                $fila['articulo']->categoriaArticulo->nombre_categoria_articulo,
                $fila['articulo']->nombre_articulo,
                $fila['presentacion_pedido']->nombre_presentacion_articulo,
            ]))
            ->values();
    }

    #[Computed]
    public function selectedPresentation(): ?PresentacionArticulo
    {
        return $this->presentationOptions->firstWhere('id', (int) $this->form->presentacion_articulo_id);
    }

    #[Computed]
    public function selectedArticle(): ?Articulo
    {
        return $this->articleOptions->firstWhere('id', (int) $this->form->articulo_id);
    }

    #[Computed]
    public function hasFixedEquivalence(): bool
    {
        return $this->selectedPresentation?->tipo_equivalencia_presentacion_articulo === TipoEquivalenciaPresentacionArticulo::Fija;
    }

    public function render(): View
    {
        return view('livewire.panel.compras.details');
    }

    private function refreshPurchase(): void
    {
        unset($this->purchase, $this->purchaseNeeds, $this->articleOptions, $this->presentationOptions, $this->selectedPresentation, $this->selectedArticle, $this->hasFixedEquivalence);
    }
}
