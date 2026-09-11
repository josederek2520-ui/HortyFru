<?php

namespace App\Livewire\Panel\Recepciones;

use App\Actions\Recepciones\ConfirmarRecepcionAction;
use App\Actions\Recepciones\EliminarDetalleRecepcionAction;
use App\Actions\Recepciones\GuardarDetalleRecepcionAction;
use App\Enums\TipoEquivalenciaPresentacionArticulo;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\FormularioDetalleRecepcion;
use App\Models\Articulo;
use App\Models\DetalleCompra;
use App\Models\DetalleRecepcion;
use App\Models\PresentacionArticulo;
use App\Models\Recepcion;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Details extends Component
{
    use InteractsWithToasts;

    public FormularioDetalleRecepcion $form;

    #[Locked]
    public int $receptionId;

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public bool $showConfirmModal = false;

    public ?int $editingDetailId = null;

    public ?int $deletingDetailId = null;

    public string $deletingArticleName = '';

    public function mount(Recepcion $recepcion): void
    {
        Gate::authorize('view', $recepcion);
        $this->receptionId = $recepcion->id;
    }

    public function openCreateModal(): void
    {
        Gate::authorize('update', $this->reception);
        $this->editingDetailId = null;
        $this->form->limpiar();
        $this->showFormModal = true;
        $this->dispatch('reception-detail-form-opened');
        $this->syncExpirationPicker();
    }

    public function openEditModal(int $detailId): void
    {
        Gate::authorize('update', $this->reception);
        $detalle = $this->reception->detalles()->findOrFail($detailId);
        $this->editingDetailId = $detalle->id;
        $this->form->limpiar();
        $this->form->llenarDesde($detalle);
        $this->forgetComputedOptions();
        $this->showFormModal = true;
        $this->dispatch('reception-detail-form-opened');
        $this->syncExpirationPicker();
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingDetailId = null;
        $this->form->limpiar();
        $this->forgetComputedOptions();
    }

    public function selectPurchaseDetail(string $purchaseDetailId): void
    {
        $this->form->detalle_compra_id = $purchaseDetailId;
        $this->form->articulo_id = '';
        $this->form->presentacion_articulo_id = '';
        $this->form->cantidad_presentaciones_detalle_recepcion = '';
        $this->form->cantidad_base_detalle_recepcion = '';

        if ($purchaseDetailId !== '') {
            $detalleCompra = $this->purchaseDetailOptions->firstWhere('id', (int) $purchaseDetailId);

            if ($detalleCompra !== null) {
                $this->form->articulo_id = (string) $detalleCompra->articulo_id;
                $this->form->presentacion_articulo_id = $detalleCompra->presentacion_articulo_id === null
                    ? ''
                    : (string) $detalleCompra->presentacion_articulo_id;
                $this->form->cantidad_presentaciones_detalle_recepcion = $detalleCompra->cantidad_presentaciones_detalle_compra ?? '';
                $this->form->cantidad_base_detalle_recepcion = $detalleCompra->cantidad_real_detalle_compra ?? '';
            }
        }

        $this->forgetComputedOptions();
    }

    public function selectArticle(string $articleId): void
    {
        $this->form->articulo_id = $articleId;
        $this->form->presentacion_articulo_id = '';
        $this->form->cantidad_presentaciones_detalle_recepcion = '';
        $this->form->cantidad_base_detalle_recepcion = '';
        $this->forgetComputedOptions();

        if ($articleId === '') {
            return;
        }

        $predeterminada = PresentacionArticulo::query()
            ->where('articulo_id', $articleId)
            ->where('estado_presentacion_articulo', true)
            ->where('predeterminada_compra_presentacion_articulo', true)
            ->whereIn('uso_presentacion_articulo', ['COMPRA', 'AMBOS'])
            ->value('id');
        $this->form->presentacion_articulo_id = $predeterminada === null ? '' : (string) $predeterminada;
        unset($this->selectedPresentation, $this->hasFixedEquivalence);
    }

    public function updatedFormPresentacionArticuloId(): void
    {
        $this->form->cantidad_presentaciones_detalle_recepcion = '';
        $this->form->cantidad_base_detalle_recepcion = '';
        unset($this->selectedPresentation, $this->hasFixedEquivalence);
    }

    public function save(GuardarDetalleRecepcionAction $guardarDetalle): void
    {
        $recepcion = $this->reception;
        Gate::authorize('update', $recepcion);
        $detalle = $this->editingDetailId === null
            ? null
            : $recepcion->detalles()->findOrFail($this->editingDetailId);
        /** @var User $usuario */
        $usuario = auth()->user();
        $guardarDetalle($recepcion, $detalle, $this->form->validar(), $usuario);
        $titulo = $detalle === null ? 'Producto recibido agregado' : 'Producto recibido actualizado';
        $tipo = $detalle === null ? 'success' : 'info';
        $this->closeFormModal();
        $this->refreshReception();
        $this->toast($titulo, 'La recepción continúa en borrador hasta que la confirmes.', $tipo);
    }

    public function openDeleteModal(int $detailId): void
    {
        Gate::authorize('update', $this->reception);
        $detalle = $this->reception->detalles()->with('articulo:id,nombre_articulo')->findOrFail($detailId);
        $this->deletingDetailId = $detalle->id;
        $this->deletingArticleName = $detalle->articulo->nombre_articulo;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->reset('deletingDetailId', 'deletingArticleName');
    }

    public function deleteDetail(EliminarDetalleRecepcionAction $eliminarDetalle): void
    {
        $recepcion = $this->reception;
        Gate::authorize('update', $recepcion);
        $detalle = $recepcion->detalles()->findOrFail($this->deletingDetailId);
        /** @var User $usuario */
        $usuario = auth()->user();
        $eliminarDetalle($recepcion, $detalle, $usuario);
        $this->closeDeleteModal();
        $this->refreshReception();
        $this->toast('Producto eliminado', 'El producto fue retirado de la recepción.', 'warning');
    }

    public function confirmReception(ConfirmarRecepcionAction $confirmarRecepcion): void
    {
        $recepcion = $this->reception;
        Gate::authorize('confirm', $recepcion);
        /** @var User $usuario */
        $usuario = auth()->user();
        $confirmarRecepcion($recepcion, $usuario);
        $this->showConfirmModal = false;
        $this->refreshReception();
        $this->toast(
            'Recepción confirmada',
            'Se generaron los lotes y la entrada de inventario con las cantidades recibidas.',
            'success',
        );
    }

    #[Computed]
    public function reception(): Recepcion
    {
        $recepcion = Recepcion::query()
            ->with([
                'compra.proveedor:id,nombre_proveedor',
                'almacen:id,nombre_almacen,direccion_almacen,estado_almacen',
                'empleado:id,nombre_empleado,apellido_empleado',
                'detalles' => fn ($consulta) => $consulta->with([
                    'articulo:id,nombre_articulo,unidad_medida_id,categoria_articulo_id',
                    'articulo.categoriaArticulo:id,nombre_categoria_articulo',
                    'presentacionArticulo:id,nombre_presentacion_articulo',
                    'unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida',
                    'lote:id,codigo_lote,estado_lote',
                ])->oldest('id'),
            ])
            ->findOrFail($this->receptionId);
        Gate::authorize('view', $recepcion);

        return $recepcion;
    }

    /** @return Collection<int, DetalleCompra> */
    #[Computed]
    public function purchaseDetailOptions(): Collection
    {
        if ($this->reception->compra_id === null) {
            return new Collection;
        }

        return DetalleCompra::query()
            ->where('compra_id', $this->reception->compra_id)
            ->with([
                'articulo:id,nombre_articulo,unidad_medida_id',
                'articulo.unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida',
                'presentacionArticulo:id,nombre_presentacion_articulo,tipo_equivalencia_presentacion_articulo,equivalencia_base_presentacion_articulo',
            ])
            ->oldest('id')
            ->get();
    }

    /** @return Collection<int, Articulo> */
    #[Computed]
    public function articleOptions(): Collection
    {
        $articuloActual = $this->editingDetailId === null
            ? null
            : DetalleRecepcion::query()->whereKey($this->editingDetailId)->value('articulo_id');
        $articulosCompra = $this->reception->compra_id === null
            ? collect()
            : DetalleCompra::query()
                ->where('compra_id', $this->reception->compra_id)
                ->pluck('articulo_id');

        return Articulo::query()
            ->select(['id', 'nombre_articulo', 'categoria_articulo_id', 'unidad_medida_id', 'estado_articulo'])
            ->with([
                'categoriaArticulo:id,nombre_categoria_articulo',
                'unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida',
            ])
            ->where(fn ($consulta) => $consulta->where('estado_articulo', true)
                ->when($articuloActual !== null, fn ($consulta) => $consulta->orWhere('id', $articuloActual))
                ->when($articulosCompra->isNotEmpty(), fn ($consulta) => $consulta->orWhereIn('id', $articulosCompra)))
            ->orderBy('nombre_articulo')
            ->get();
    }

    /** @return Collection<int, PresentacionArticulo> */
    #[Computed]
    public function presentationOptions(): Collection
    {
        if ($this->form->articulo_id === '') {
            return new Collection;
        }

        $presentacionActual = $this->editingDetailId === null
            ? null
            : DetalleRecepcion::query()->whereKey($this->editingDetailId)->value('presentacion_articulo_id');
        $presentacionesCompra = $this->reception->compra_id === null
            ? collect()
            : DetalleCompra::query()
                ->where('compra_id', $this->reception->compra_id)
                ->whereNotNull('presentacion_articulo_id')
                ->pluck('presentacion_articulo_id');

        return PresentacionArticulo::query()
            ->where('articulo_id', $this->form->articulo_id)
            ->where(function ($consulta) use ($presentacionActual, $presentacionesCompra): void {
                $consulta->where(function ($consulta): void {
                    $consulta->where('estado_presentacion_articulo', true)
                        ->whereIn('uso_presentacion_articulo', ['COMPRA', 'AMBOS']);
                })
                    ->when($presentacionActual !== null, fn ($consulta) => $consulta->orWhere('id', $presentacionActual))
                    ->when($presentacionesCompra->isNotEmpty(), fn ($consulta) => $consulta->orWhereIn('id', $presentacionesCompra));
            })
            ->orderByDesc('predeterminada_compra_presentacion_articulo')
            ->orderBy('nombre_presentacion_articulo')
            ->get();
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
        return view('livewire.panel.recepciones.details');
    }

    private function refreshReception(): void
    {
        unset($this->reception);
        $this->forgetComputedOptions();
    }

    private function forgetComputedOptions(): void
    {
        unset(
            $this->purchaseDetailOptions,
            $this->articleOptions,
            $this->presentationOptions,
            $this->selectedPresentation,
            $this->selectedArticle,
            $this->hasFixedEquivalence,
        );
    }

    private function syncExpirationPicker(): void
    {
        $this->dispatch(
            'reception-detail-expiration-sync',
            value: $this->form->fecha_vencimiento_detalle_recepcion,
            minDate: $this->reception->fecha_recepcion_local->format('Y-m-d'),
        );
    }
}
