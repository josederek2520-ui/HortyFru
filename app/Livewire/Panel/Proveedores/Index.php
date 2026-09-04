<?php

namespace App\Livewire\Panel\Proveedores;

use App\Actions\Proveedores\ActualizarProveedorAction;
use App\Actions\Proveedores\CambiarEstadoProveedorAction;
use App\Actions\Proveedores\CrearProveedorAction;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\FormularioProveedor;
use App\Models\Proveedor;
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

    public FormularioProveedor $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    public bool $showFormModal = false;

    public bool $showStatusModal = false;

    public ?int $editingProviderId = null;

    public ?int $statusProviderId = null;

    public string $statusProviderName = '';

    public bool $statusProviderIsActive = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Proveedor::class);
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
        Gate::authorize('create', Proveedor::class);

        $this->editingProviderId = null;
        $this->form->limpiar();
        $this->showFormModal = true;
        $this->dispatch('provider-form-opened');
    }

    public function openEditModal(int $providerId): void
    {
        $proveedor = Proveedor::query()->findOrFail($providerId);
        Gate::authorize('update', $proveedor);

        $this->editingProviderId = $proveedor->id;
        $this->form->limpiar();
        $this->form->llenarDesde($proveedor);
        $this->showFormModal = true;
        $this->dispatch('provider-form-opened');
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingProviderId = null;
        $this->form->limpiar();
    }

    public function save(
        CrearProveedorAction $crearProveedor,
        ActualizarProveedorAction $actualizarProveedor,
    ): void {
        if ($this->editingProviderId === null) {
            Gate::authorize('create', Proveedor::class);

            $proveedor = $crearProveedor($this->form->validarParaCrear());
            $title = 'Proveedor registrado';
            $message = "El proveedor {$proveedor->nombre_proveedor} fue registrado correctamente.";
            $type = 'success';
        } else {
            $proveedor = Proveedor::query()->findOrFail($this->editingProviderId);
            Gate::authorize('update', $proveedor);

            if (Gate::denies('changeStatus', $proveedor)) {
                $this->form->estado_proveedor = $proveedor->estado_proveedor;
            }

            $proveedor = $actualizarProveedor($proveedor, $this->form->validarParaActualizar($proveedor));
            $title = 'Proveedor actualizado';
            $message = "Los datos de {$proveedor->nombre_proveedor} fueron actualizados.";
            $type = 'info';
        }

        $this->closeFormModal();
        $this->resetPage();
        $this->toast($title, $message, $type);
    }

    public function openStatusModal(int $providerId): void
    {
        $proveedor = Proveedor::query()->findOrFail($providerId);
        Gate::authorize('changeStatus', $proveedor);

        $this->statusProviderId = $proveedor->id;
        $this->statusProviderName = $proveedor->nombre_proveedor;
        $this->statusProviderIsActive = $proveedor->estado_proveedor;
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->reset('statusProviderId', 'statusProviderName', 'statusProviderIsActive');
    }

    public function changeStatus(CambiarEstadoProveedorAction $cambiarEstadoProveedor): void
    {
        $proveedor = Proveedor::query()->findOrFail($this->statusProviderId);
        Gate::authorize('changeStatus', $proveedor);

        $proveedor = $cambiarEstadoProveedor($proveedor);

        $this->closeStatusModal();
        $this->toast(
            $proveedor->estado_proveedor ? 'Proveedor activado' : 'Proveedor desactivado',
            "El proveedor {$proveedor->nombre_proveedor} fue ".($proveedor->estado_proveedor ? 'activado' : 'desactivado').'.',
            $proveedor->estado_proveedor ? 'success' : 'warning',
        );
    }

    #[Computed]
    public function providers(): LengthAwarePaginator
    {
        return Proveedor::query()
            ->select([
                'id',
                'nombre_proveedor',
                'telefono_proveedor',
                'mercado_proveedor',
                'direccion_proveedor',
                'observacion_proveedor',
                'estado_proveedor',
                'created_at',
            ])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('nombre_proveedor', 'like', "%{$this->search}%")
                        ->orWhere('telefono_proveedor', 'like', "%{$this->search}%")
                        ->orWhere('mercado_proveedor', 'like', "%{$this->search}%")
                        ->orWhere('direccion_proveedor', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('estado_proveedor', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('estado_proveedor', false))
            ->orderBy('nombre_proveedor')
            ->paginate(10);
    }

    #[Computed]
    public function totalProviders(): int
    {
        return Proveedor::query()->count();
    }

    #[Computed]
    public function activeProviders(): int
    {
        return Proveedor::query()->where('estado_proveedor', true)->count();
    }

    public function render(): View
    {
        return view('livewire.panel.proveedores.index');
    }
}
