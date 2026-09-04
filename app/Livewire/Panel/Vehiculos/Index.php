<?php

namespace App\Livewire\Panel\Vehiculos;

use App\Actions\Vehiculos\ActualizarVehiculoAction;
use App\Actions\Vehiculos\CambiarEstadoVehiculoAction;
use App\Actions\Vehiculos\CrearVehiculoAction;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\FormularioVehiculo;
use App\Models\Vehiculo;
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

    public FormularioVehiculo $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    public bool $showFormModal = false;

    public bool $showStatusModal = false;

    public ?int $editingVehicleId = null;

    public ?int $statusVehicleId = null;

    public string $statusVehiclePlate = '';

    public bool $statusVehicleIsActive = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Vehiculo::class);
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
        Gate::authorize('create', Vehiculo::class);

        $this->editingVehicleId = null;
        $this->form->limpiar();
        $this->showFormModal = true;
        $this->dispatch('vehicle-form-opened');
    }

    public function openEditModal(int $vehicleId): void
    {
        $vehiculo = Vehiculo::query()->findOrFail($vehicleId);
        Gate::authorize('update', $vehiculo);

        $this->editingVehicleId = $vehiculo->id;
        $this->form->limpiar();
        $this->form->llenarDesde($vehiculo);
        $this->showFormModal = true;
        $this->dispatch('vehicle-form-opened');
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingVehicleId = null;
        $this->form->limpiar();
    }

    public function save(
        CrearVehiculoAction $crearVehiculo,
        ActualizarVehiculoAction $actualizarVehiculo,
    ): void {
        if ($this->editingVehicleId === null) {
            Gate::authorize('create', Vehiculo::class);

            $vehiculo = $crearVehiculo($this->form->validarParaCrear());
            $title = 'Vehículo registrado';
            $message = "El vehículo {$vehiculo->placa_vehiculo} fue registrado correctamente.";
            $type = 'success';
        } else {
            $vehiculo = Vehiculo::query()->findOrFail($this->editingVehicleId);
            Gate::authorize('update', $vehiculo);

            if (Gate::denies('changeStatus', $vehiculo)) {
                $this->form->estado_vehiculo = $vehiculo->estado_vehiculo;
            }

            $vehiculo = $actualizarVehiculo($vehiculo, $this->form->validarParaActualizar($vehiculo));
            $title = 'Vehículo actualizado';
            $message = "Los datos del vehículo {$vehiculo->placa_vehiculo} fueron actualizados.";
            $type = 'info';
        }

        $this->closeFormModal();
        $this->resetPage();
        $this->toast($title, $message, $type);
    }

    public function openStatusModal(int $vehicleId): void
    {
        $vehiculo = Vehiculo::query()->findOrFail($vehicleId);
        Gate::authorize('changeStatus', $vehiculo);

        $this->statusVehicleId = $vehiculo->id;
        $this->statusVehiclePlate = $vehiculo->placa_vehiculo;
        $this->statusVehicleIsActive = $vehiculo->estado_vehiculo;
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->reset('statusVehicleId', 'statusVehiclePlate', 'statusVehicleIsActive');
    }

    public function changeStatus(CambiarEstadoVehiculoAction $cambiarEstadoVehiculo): void
    {
        $vehiculo = Vehiculo::query()->findOrFail($this->statusVehicleId);
        Gate::authorize('changeStatus', $vehiculo);

        $vehiculo = $cambiarEstadoVehiculo($vehiculo);

        $this->closeStatusModal();
        $this->toast(
            $vehiculo->estado_vehiculo ? 'Vehículo activado' : 'Vehículo desactivado',
            "El vehículo {$vehiculo->placa_vehiculo} fue ".($vehiculo->estado_vehiculo ? 'activado' : 'desactivado').'.',
            $vehiculo->estado_vehiculo ? 'success' : 'warning',
        );
    }

    #[Computed]
    public function vehicles(): LengthAwarePaginator
    {
        return Vehiculo::query()
            ->select([
                'id',
                'placa_vehiculo',
                'marca_vehiculo',
                'tipo_vehiculo',
                'estado_vehiculo',
                'created_at',
            ])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('placa_vehiculo', 'like', "%{$this->search}%")
                        ->orWhere('marca_vehiculo', 'like', "%{$this->search}%")
                        ->orWhere('tipo_vehiculo', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('estado_vehiculo', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('estado_vehiculo', false))
            ->orderBy('placa_vehiculo')
            ->paginate(10);
    }

    #[Computed]
    public function totalVehicles(): int
    {
        return Vehiculo::query()->count();
    }

    #[Computed]
    public function activeVehicles(): int
    {
        return Vehiculo::query()->where('estado_vehiculo', true)->count();
    }

    public function render(): View
    {
        return view('livewire.panel.vehiculos.index');
    }
}
