<?php

namespace App\Livewire\Panel\Articulos;

use App\Actions\Articulos\ActualizarArticuloAction;
use App\Actions\Articulos\CambiarEstadoArticuloAction;
use App\Actions\Articulos\CrearArticuloAction;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\FormularioArticulo;
use App\Models\Articulo;
use App\Models\CategoriaArticulo;
use App\Models\UnidadMedida;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class Index extends Component
{
    use InteractsWithToasts;
    use WithFileUploads;
    use WithPagination;

    public FormularioArticulo $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    public bool $showFormModal = false;

    public bool $showStatusModal = false;

    public ?int $editingArticleId = null;

    public ?int $statusArticleId = null;

    public string $statusArticleName = '';

    public bool $statusArticleIsActive = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Articulo::class);
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
        Gate::authorize('create', Articulo::class);

        $this->editingArticleId = null;
        $this->form->limpiar();
        $this->showFormModal = true;
        $this->dispatch('article-form-opened');
    }

    public function openEditModal(int $articleId): void
    {
        $articulo = Articulo::query()->findOrFail($articleId);
        Gate::authorize('update', $articulo);

        $this->editingArticleId = $articulo->id;
        $this->form->limpiar();
        $this->form->llenarDesde($articulo);
        $this->showFormModal = true;
        $this->dispatch('article-form-opened');
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingArticleId = null;
        $this->form->limpiar();
    }

    public function updatedFormImagenArticulo(): void
    {
        $this->form->eliminar_imagen = false;
        $this->resetValidation('form.imagen_articulo');
    }

    public function removeImage(): void
    {
        $this->form->imagen_articulo = null;
        $this->form->eliminar_imagen = true;
        $this->resetValidation('form.imagen_articulo');
        $this->dispatch('article-image-cleared');
    }

    public function save(
        CrearArticuloAction $crearArticulo,
        ActualizarArticuloAction $actualizarArticulo,
    ): void {
        if ($this->editingArticleId === null) {
            Gate::authorize('create', Articulo::class);

            $articulo = $crearArticulo($this->form->validarParaCrear());
            $title = 'Artículo registrado';
            $message = "El artículo {$articulo->nombre_articulo} fue registrado correctamente.";
            $type = 'success';
        } else {
            $articulo = Articulo::query()->findOrFail($this->editingArticleId);
            Gate::authorize('update', $articulo);

            if (Gate::denies('changeStatus', $articulo)) {
                $this->form->estado_articulo = $articulo->estado_articulo;
            }

            $articulo = $actualizarArticulo($articulo, $this->form->validarParaActualizar($articulo));
            $title = 'Artículo actualizado';
            $message = "Los datos de {$articulo->nombre_articulo} fueron actualizados.";
            $type = 'info';
        }

        $this->closeFormModal();
        $this->resetPage();
        $this->toast($title, $message, $type);
    }

    public function openStatusModal(int $articleId): void
    {
        $articulo = Articulo::query()->findOrFail($articleId);
        Gate::authorize('changeStatus', $articulo);

        $this->statusArticleId = $articulo->id;
        $this->statusArticleName = $articulo->nombre_articulo;
        $this->statusArticleIsActive = $articulo->estado_articulo;
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->reset('statusArticleId', 'statusArticleName', 'statusArticleIsActive');
    }

    public function changeStatus(CambiarEstadoArticuloAction $cambiarEstadoArticulo): void
    {
        $articulo = Articulo::query()->findOrFail($this->statusArticleId);
        Gate::authorize('changeStatus', $articulo);

        $articulo = $cambiarEstadoArticulo($articulo);

        $this->closeStatusModal();
        $this->toast(
            $articulo->estado_articulo ? 'Artículo activado' : 'Artículo desactivado',
            "El artículo {$articulo->nombre_articulo} fue ".($articulo->estado_articulo ? 'activado' : 'desactivado').'.',
            $articulo->estado_articulo ? 'success' : 'warning',
        );
    }

    #[Computed]
    public function articles(): LengthAwarePaginator
    {
        return Articulo::query()
            ->select([
                'id',
                'nombre_articulo',
                'categoria_articulo_id',
                'unidad_medida_id',
                'imagen_articulo',
                'estado_articulo',
                'created_at',
            ])
            ->with([
                'categoriaArticulo:id,nombre_categoria_articulo',
                'unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida',
            ])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('nombre_articulo', 'like', "%{$this->search}%")
                        ->orWhereHas('categoriaArticulo', fn ($query) => $query->where('nombre_categoria_articulo', 'like', "%{$this->search}%"))
                        ->orWhereHas('unidadMedida', function ($query): void {
                            $query
                                ->where('nombre_unidad_medida', 'like', "%{$this->search}%")
                                ->orWhere('abreviatura_unidad_medida', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('estado_articulo', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('estado_articulo', false))
            ->orderBy('nombre_articulo')
            ->paginate(10);
    }

    #[Computed]
    public function categoryOptions(): Collection
    {
        return CategoriaArticulo::query()
            ->select(['id', 'nombre_categoria_articulo'])
            ->where(function ($query): void {
                $query->where('estado_categoria_articulo', true);

                if ($this->form->categoria_articulo_id !== null) {
                    $query->orWhere('id', $this->form->categoria_articulo_id);
                }
            })
            ->orderBy('nombre_categoria_articulo')
            ->get();
    }

    #[Computed]
    public function measurementUnitOptions(): Collection
    {
        return UnidadMedida::query()
            ->select(['id', 'nombre_unidad_medida', 'abreviatura_unidad_medida'])
            ->where(function ($query): void {
                $query->where('estado_unidad_medida', true);

                if ($this->form->unidad_medida_id !== null) {
                    $query->orWhere('id', $this->form->unidad_medida_id);
                }
            })
            ->orderBy('nombre_unidad_medida')
            ->get();
    }

    #[Computed]
    public function totalArticles(): int
    {
        return Articulo::query()->count();
    }

    #[Computed]
    public function activeArticles(): int
    {
        return Articulo::query()->where('estado_articulo', true)->count();
    }

    #[Computed]
    public function currentImageUrl(): ?string
    {
        if ($this->form->imagen_actual === null || $this->form->eliminar_imagen) {
            return null;
        }

        return Storage::disk('public')->url($this->form->imagen_actual);
    }

    #[Computed]
    public function newImagePreviewUrl(): ?string
    {
        if ($this->form->imagen_articulo === null) {
            return null;
        }

        try {
            $mimeType = $this->form->imagen_articulo->getMimeType();

            if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                return null;
            }

            return $this->form->imagen_articulo->temporaryUrl();
        } catch (Throwable) {
            return null;
        }
    }

    public function render(): View
    {
        return view('livewire.panel.articulos.index');
    }
}
