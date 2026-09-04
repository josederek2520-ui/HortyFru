<?php

namespace App\Livewire\Panel\CategoriasArticulos;

use App\Actions\CategoriasArticulos\ActualizarCategoriaArticuloAction;
use App\Actions\CategoriasArticulos\CambiarEstadoCategoriaArticuloAction;
use App\Actions\CategoriasArticulos\CrearCategoriaArticuloAction;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\FormularioCategoriaArticulo;
use App\Models\CategoriaArticulo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    public FormularioCategoriaArticulo $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    public bool $showFormModal = false;

    public bool $showStatusModal = false;

    public ?int $editingCategoryId = null;

    public ?int $statusCategoryId = null;

    public string $statusCategoryName = '';

    public bool $statusCategoryIsActive = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', CategoriaArticulo::class);
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
        Gate::authorize('create', CategoriaArticulo::class);

        $this->editingCategoryId = null;
        $this->form->limpiar();
        $this->showFormModal = true;
        $this->dispatch('article-category-form-opened');
    }

    public function openEditModal(int $categoryId): void
    {
        $categoriaArticulo = CategoriaArticulo::query()->findOrFail($categoryId);
        Gate::authorize('update', $categoriaArticulo);

        $this->editingCategoryId = $categoriaArticulo->id;
        $this->form->limpiar();
        $this->form->llenarDesde($categoriaArticulo);
        $this->showFormModal = true;
        $this->dispatch('article-category-form-opened');
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingCategoryId = null;
        $this->form->limpiar();
    }

    public function save(
        CrearCategoriaArticuloAction $crearCategoriaArticulo,
        ActualizarCategoriaArticuloAction $actualizarCategoriaArticulo,
    ): void {
        if ($this->editingCategoryId === null) {
            Gate::authorize('create', CategoriaArticulo::class);

            $categoriaArticulo = $crearCategoriaArticulo($this->form->validarParaCrear());
            $title = 'Categoría registrada';
            $message = "La categoría {$categoriaArticulo->nombre_categoria_articulo} fue registrada correctamente.";
            $type = 'success';
        } else {
            $categoriaArticulo = CategoriaArticulo::query()->findOrFail($this->editingCategoryId);
            Gate::authorize('update', $categoriaArticulo);

            if (Gate::denies('changeStatus', $categoriaArticulo)) {
                $this->form->estado_categoria_articulo = $categoriaArticulo->estado_categoria_articulo;
            }

            $categoriaArticulo = $actualizarCategoriaArticulo(
                $categoriaArticulo,
                $this->form->validarParaActualizar($categoriaArticulo),
            );
            $title = 'Categoría actualizada';
            $message = "Los datos de {$categoriaArticulo->nombre_categoria_articulo} fueron actualizados.";
            $type = 'info';
        }

        $this->closeFormModal();
        $this->resetPage();
        $this->toast($title, $message, $type);
    }

    public function openStatusModal(int $categoryId): void
    {
        $categoriaArticulo = CategoriaArticulo::query()->findOrFail($categoryId);
        Gate::authorize('changeStatus', $categoriaArticulo);

        $this->statusCategoryId = $categoriaArticulo->id;
        $this->statusCategoryName = $categoriaArticulo->nombre_categoria_articulo;
        $this->statusCategoryIsActive = $categoriaArticulo->estado_categoria_articulo;
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->reset('statusCategoryId', 'statusCategoryName', 'statusCategoryIsActive');
    }

    public function changeStatus(CambiarEstadoCategoriaArticuloAction $cambiarEstadoCategoriaArticulo): void
    {
        $categoriaArticulo = CategoriaArticulo::query()->findOrFail($this->statusCategoryId);
        Gate::authorize('changeStatus', $categoriaArticulo);

        $categoriaArticulo = $cambiarEstadoCategoriaArticulo($categoriaArticulo);

        $this->closeStatusModal();
        $this->toast(
            $categoriaArticulo->estado_categoria_articulo ? 'Categoría activada' : 'Categoría desactivada',
            "La categoría {$categoriaArticulo->nombre_categoria_articulo} fue ".($categoriaArticulo->estado_categoria_articulo ? 'activada' : 'desactivada').'.',
            $categoriaArticulo->estado_categoria_articulo ? 'success' : 'warning',
        );
    }

    #[Computed]
    public function categories(): LengthAwarePaginator
    {
        return CategoriaArticulo::query()
            ->select([
                'id',
                'nombre_categoria_articulo',
                'estado_categoria_articulo',
                'created_at',
            ])
            ->when($this->search !== '', fn ($query) => $query->where('nombre_categoria_articulo', 'like', "%{$this->search}%"))
            ->when($this->status === 'active', fn ($query) => $query->where('estado_categoria_articulo', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('estado_categoria_articulo', false))
            ->orderBy('nombre_categoria_articulo')
            ->paginate(10);
    }

    #[Computed]
    public function totalCategories(): int
    {
        return CategoriaArticulo::query()->count();
    }

    #[Computed]
    public function activeCategories(): int
    {
        return CategoriaArticulo::query()->where('estado_categoria_articulo', true)->count();
    }

    public function render(): View
    {
        return view('livewire.panel.categorias-articulos.index');
    }
}
