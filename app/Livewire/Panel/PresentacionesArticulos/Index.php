<?php

namespace App\Livewire\Panel\PresentacionesArticulos;

use App\Actions\PresentacionesArticulos\ActualizarPresentacionArticuloAction;
use App\Actions\PresentacionesArticulos\CambiarEstadoPresentacionArticuloAction;
use App\Actions\PresentacionesArticulos\CrearPresentacionArticuloAction;
use App\Enums\UsoPresentacionArticulo;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\FormularioPresentacionArticulo;
use App\Models\Articulo;
use App\Models\CategoriaArticulo;
use App\Models\PresentacionArticulo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    public FormularioPresentacionArticulo $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    public bool $showFormModal = false;

    public bool $showStatusModal = false;

    public ?int $editingPresentationId = null;

    public string $selectedCategoryId = '';

    public ?int $statusPresentationId = null;

    public string $statusPresentationName = '';

    public bool $statusPresentationIsActive = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', PresentacionArticulo::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status');
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', PresentacionArticulo::class);

        $this->editingPresentationId = null;
        $this->selectedCategoryId = '';
        $this->form->limpiar();
        $this->showFormModal = true;
        $this->dispatch('article-presentation-form-opened');
    }

    public function openEditModal(int $presentationId): void
    {
        $presentacionArticulo = PresentacionArticulo::query()
            ->with('articulo:id,categoria_articulo_id')
            ->findOrFail($presentationId);
        Gate::authorize('update', $presentacionArticulo);

        $this->editingPresentationId = $presentacionArticulo->id;
        $this->selectedCategoryId = (string) $presentacionArticulo->articulo->categoria_articulo_id;
        $this->form->limpiar();
        $this->form->llenarDesde($presentacionArticulo);
        $this->showFormModal = true;
        $this->dispatch('article-presentation-form-opened');
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingPresentationId = null;
        $this->selectedCategoryId = '';
        $this->form->limpiar();
    }

    public function updatedSelectedCategoryId(): void
    {
        $this->form->articulo_id = null;
        $this->resetValidation('form.articulo_id');
    }

    public function save(
        CrearPresentacionArticuloAction $crearPresentacionArticulo,
        ActualizarPresentacionArticuloAction $actualizarPresentacionArticulo,
    ): void {
        if ($this->editingPresentationId === null) {
            Gate::authorize('create', PresentacionArticulo::class);

            $presentacionArticulo = $crearPresentacionArticulo($this->form->validarParaCrear());
            $title = 'Presentación registrada';
            $message = "La presentación {$presentacionArticulo->nombre_presentacion_articulo} fue registrada correctamente.";
            $type = 'success';
        } else {
            $presentacionArticulo = PresentacionArticulo::query()->findOrFail($this->editingPresentationId);
            Gate::authorize('update', $presentacionArticulo);

            if (Gate::denies('changeStatus', $presentacionArticulo)) {
                $this->form->estado_presentacion_articulo = $presentacionArticulo->estado_presentacion_articulo;
            }

            $presentacionArticulo = $actualizarPresentacionArticulo(
                $presentacionArticulo,
                $this->form->validarParaActualizar($presentacionArticulo),
            );
            $title = 'Presentación actualizada';
            $message = "Los datos de {$presentacionArticulo->nombre_presentacion_articulo} fueron actualizados.";
            $type = 'info';
        }

        $this->closeFormModal();
        $this->resetPage();
        $this->toast($title, $message, $type);
    }

    public function openStatusModal(int $presentationId): void
    {
        $presentacionArticulo = PresentacionArticulo::query()->findOrFail($presentationId);
        Gate::authorize('changeStatus', $presentacionArticulo);

        $this->statusPresentationId = $presentacionArticulo->id;
        $this->statusPresentationName = $presentacionArticulo->nombre_presentacion_articulo;
        $this->statusPresentationIsActive = $presentacionArticulo->estado_presentacion_articulo;
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->reset('statusPresentationId', 'statusPresentationName', 'statusPresentationIsActive');
    }

    public function changeStatus(CambiarEstadoPresentacionArticuloAction $cambiarEstadoPresentacionArticulo): void
    {
        $presentacionArticulo = PresentacionArticulo::query()->findOrFail($this->statusPresentationId);
        Gate::authorize('changeStatus', $presentacionArticulo);

        $presentacionArticulo = $cambiarEstadoPresentacionArticulo($presentacionArticulo);

        $this->closeStatusModal();
        $this->toast(
            $presentacionArticulo->estado_presentacion_articulo ? 'Presentación activada' : 'Presentación desactivada',
            "La presentación {$presentacionArticulo->nombre_presentacion_articulo} fue ".($presentacionArticulo->estado_presentacion_articulo ? 'activada' : 'desactivada').'.',
            $presentacionArticulo->estado_presentacion_articulo ? 'success' : 'warning',
        );
    }

    #[Computed]
    public function presentations(): LengthAwarePaginator
    {
        return PresentacionArticulo::query()
            ->select([
                'id',
                'articulo_id',
                'nombre_presentacion_articulo',
                'uso_presentacion_articulo',
                'tipo_equivalencia_presentacion_articulo',
                'equivalencia_base_presentacion_articulo',
                'permite_fraccion_presentacion_articulo',
                'predeterminada_pedido_presentacion_articulo',
                'predeterminada_compra_presentacion_articulo',
                'estado_presentacion_articulo',
                'created_at',
            ])
            ->with([
                'articulo:id,nombre_articulo,unidad_medida_id,imagen_articulo',
                'articulo.unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida',
            ])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('nombre_presentacion_articulo', 'like', "%{$this->search}%")
                        ->orWhere('uso_presentacion_articulo', 'like', "%{$this->search}%")
                        ->orWhere('tipo_equivalencia_presentacion_articulo', 'like', "%{$this->search}%")
                        ->orWhereHas('articulo', function ($query): void {
                            $query
                                ->where('nombre_articulo', 'like', "%{$this->search}%")
                                ->orWhereHas('unidadMedida', function ($query): void {
                                    $query
                                        ->where('nombre_unidad_medida', 'like', "%{$this->search}%")
                                        ->orWhere('abreviatura_unidad_medida', 'like', "%{$this->search}%");
                                });
                        });
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('estado_presentacion_articulo', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('estado_presentacion_articulo', false))
            ->when($this->status === 'order', fn ($query) => $query->whereIn('uso_presentacion_articulo', [
                UsoPresentacionArticulo::Pedido->value,
                UsoPresentacionArticulo::Ambos->value,
            ]))
            ->when($this->status === 'purchase', fn ($query) => $query->whereIn('uso_presentacion_articulo', [
                UsoPresentacionArticulo::Compra->value,
                UsoPresentacionArticulo::Ambos->value,
            ]))
            ->orderBy('nombre_presentacion_articulo')
            ->paginate(10);
    }

    #[Computed]
    public function categoryOptions(): Collection
    {
        if (! $this->showFormModal) {
            return new Collection;
        }

        return CategoriaArticulo::query()
            ->select(['id', 'nombre_categoria_articulo'])
            ->where(function ($query): void {
                $query->where('estado_categoria_articulo', true);

                if ($this->selectedCategoryId !== '') {
                    $query->orWhere('id', (int) $this->selectedCategoryId);
                }
            })
            ->orderBy('nombre_categoria_articulo')
            ->get();
    }

    #[Computed]
    public function articleOptions(): Collection
    {
        if (! $this->showFormModal || $this->selectedCategoryId === '') {
            return new Collection;
        }

        return Articulo::query()
            ->select(['id', 'nombre_articulo', 'categoria_articulo_id', 'unidad_medida_id'])
            ->with('unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida')
            ->where('categoria_articulo_id', (int) $this->selectedCategoryId)
            ->where(function ($query): void {
                $query->where('estado_articulo', true);

                if ($this->form->articulo_id !== null) {
                    $query->orWhere('id', $this->form->articulo_id);
                }
            })
            ->orderBy('nombre_articulo')
            ->get();
    }

    #[Computed]
    public function totalPresentations(): int
    {
        return PresentacionArticulo::query()->count();
    }

    #[Computed]
    public function activePresentations(): int
    {
        return PresentacionArticulo::query()->where('estado_presentacion_articulo', true)->count();
    }

    #[Computed]
    public function orderPresentations(): int
    {
        return PresentacionArticulo::query()
            ->whereIn('uso_presentacion_articulo', [
                UsoPresentacionArticulo::Pedido->value,
                UsoPresentacionArticulo::Ambos->value,
            ])
            ->count();
    }

    #[Computed]
    public function purchasePresentations(): int
    {
        return PresentacionArticulo::query()
            ->whereIn('uso_presentacion_articulo', [
                UsoPresentacionArticulo::Compra->value,
                UsoPresentacionArticulo::Ambos->value,
            ])
            ->count();
    }

    public function render(): View
    {
        return view('livewire.panel.presentaciones-articulos.index');
    }
}
