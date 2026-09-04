<?php

namespace App\Livewire\Panel\Almacenes;

use App\Actions\Almacenes\ActualizarAlmacenAction;
use App\Actions\Almacenes\CambiarEstadoAlmacenAction;
use App\Actions\Almacenes\CrearAlmacenAction;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\FormularioAlmacen;
use App\Models\Almacen;
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

    public FormularioAlmacen $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    public bool $showFormModal = false;

    public bool $showStatusModal = false;

    public ?int $editingWarehouseId = null;

    public ?int $statusWarehouseId = null;

    public string $statusWarehouseName = '';

    public bool $statusWarehouseIsActive = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Almacen::class);
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
        Gate::authorize('create', Almacen::class);

        $this->editingWarehouseId = null;
        $this->form->limpiar();
        $this->showFormModal = true;
        $this->dispatch('warehouse-form-opened');
    }

    public function openEditModal(int $warehouseId): void
    {
        $almacen = Almacen::query()->findOrFail($warehouseId);
        Gate::authorize('update', $almacen);

        $this->editingWarehouseId = $almacen->id;
        $this->form->limpiar();
        $this->form->llenarDesde($almacen);
        $this->showFormModal = true;
        $this->dispatch('warehouse-form-opened');
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingWarehouseId = null;
        $this->form->limpiar();
    }

    public function save(
        CrearAlmacenAction $crearAlmacen,
        ActualizarAlmacenAction $actualizarAlmacen,
    ): void {
        if ($this->editingWarehouseId === null) {
            Gate::authorize('create', Almacen::class);

            $almacen = $crearAlmacen($this->form->validarParaCrear());
            $title = 'Almacén registrado';
            $message = "El almacén {$almacen->nombre_almacen} fue registrado correctamente.";
            $type = 'success';
        } else {
            $almacen = Almacen::query()->findOrFail($this->editingWarehouseId);
            Gate::authorize('update', $almacen);

            if (Gate::denies('changeStatus', $almacen)) {
                $this->form->estado_almacen = $almacen->estado_almacen;
            }

            $almacen = $actualizarAlmacen($almacen, $this->form->validarParaActualizar($almacen));
            $title = 'Almacén actualizado';
            $message = "Los datos de {$almacen->nombre_almacen} fueron actualizados.";
            $type = 'info';
        }

        $this->closeFormModal();
        $this->resetPage();
        $this->toast($title, $message, $type);
    }

    public function openStatusModal(int $warehouseId): void
    {
        $almacen = Almacen::query()->findOrFail($warehouseId);
        Gate::authorize('changeStatus', $almacen);

        $this->statusWarehouseId = $almacen->id;
        $this->statusWarehouseName = $almacen->nombre_almacen;
        $this->statusWarehouseIsActive = $almacen->estado_almacen;
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->reset('statusWarehouseId', 'statusWarehouseName', 'statusWarehouseIsActive');
    }

    public function changeStatus(CambiarEstadoAlmacenAction $cambiarEstadoAlmacen): void
    {
        $almacen = Almacen::query()->findOrFail($this->statusWarehouseId);
        Gate::authorize('changeStatus', $almacen);

        $almacen = $cambiarEstadoAlmacen($almacen);

        $this->closeStatusModal();
        $this->toast(
            $almacen->estado_almacen ? 'Almacén activado' : 'Almacén desactivado',
            "El almacén {$almacen->nombre_almacen} fue ".($almacen->estado_almacen ? 'activado' : 'desactivado').'.',
            $almacen->estado_almacen ? 'success' : 'warning',
        );
    }

    #[Computed]
    public function warehouses(): LengthAwarePaginator
    {
        return Almacen::query()
            ->select([
                'id',
                'nombre_almacen',
                'direccion_almacen',
                'estado_almacen',
                'created_at',
            ])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('nombre_almacen', 'like', "%{$this->search}%")
                        ->orWhere('direccion_almacen', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('estado_almacen', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('estado_almacen', false))
            ->orderBy('nombre_almacen')
            ->paginate(10);
    }

    #[Computed]
    public function totalWarehouses(): int
    {
        return Almacen::query()->count();
    }

    #[Computed]
    public function activeWarehouses(): int
    {
        return Almacen::query()->where('estado_almacen', true)->count();
    }

    public function render(): View
    {
        return view('livewire.panel.almacenes.index');
    }
}
