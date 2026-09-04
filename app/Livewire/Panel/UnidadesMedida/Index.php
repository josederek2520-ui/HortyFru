<?php

namespace App\Livewire\Panel\UnidadesMedida;

use App\Actions\UnidadesMedida\ActualizarUnidadMedidaAction;
use App\Actions\UnidadesMedida\CambiarEstadoUnidadMedidaAction;
use App\Actions\UnidadesMedida\CrearUnidadMedidaAction;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\FormularioUnidadMedida;
use App\Models\UnidadMedida;
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

    public FormularioUnidadMedida $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    public bool $showFormModal = false;

    public bool $showStatusModal = false;

    public ?int $editingMeasurementUnitId = null;

    public ?int $statusMeasurementUnitId = null;

    public string $statusMeasurementUnitName = '';

    public bool $statusMeasurementUnitIsActive = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', UnidadMedida::class);
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
        Gate::authorize('create', UnidadMedida::class);

        $this->editingMeasurementUnitId = null;
        $this->form->limpiar();
        $this->showFormModal = true;
        $this->dispatch('measurement-unit-form-opened');
    }

    public function openEditModal(int $measurementUnitId): void
    {
        $unidadMedida = UnidadMedida::query()->findOrFail($measurementUnitId);
        Gate::authorize('update', $unidadMedida);

        $this->editingMeasurementUnitId = $unidadMedida->id;
        $this->form->limpiar();
        $this->form->llenarDesde($unidadMedida);
        $this->showFormModal = true;
        $this->dispatch('measurement-unit-form-opened');
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingMeasurementUnitId = null;
        $this->form->limpiar();
    }

    public function save(
        CrearUnidadMedidaAction $crearUnidadMedida,
        ActualizarUnidadMedidaAction $actualizarUnidadMedida,
    ): void {
        if ($this->editingMeasurementUnitId === null) {
            Gate::authorize('create', UnidadMedida::class);

            $unidadMedida = $crearUnidadMedida($this->form->validarParaCrear());
            $title = 'Unidad registrada';
            $message = "La unidad de medida {$unidadMedida->nombre_unidad_medida} fue registrada correctamente.";
            $type = 'success';
        } else {
            $unidadMedida = UnidadMedida::query()->findOrFail($this->editingMeasurementUnitId);
            Gate::authorize('update', $unidadMedida);

            if (Gate::denies('changeStatus', $unidadMedida)) {
                $this->form->estado_unidad_medida = $unidadMedida->estado_unidad_medida;
            }

            $unidadMedida = $actualizarUnidadMedida(
                $unidadMedida,
                $this->form->validarParaActualizar($unidadMedida),
            );
            $title = 'Unidad actualizada';
            $message = "Los datos de {$unidadMedida->nombre_unidad_medida} fueron actualizados.";
            $type = 'info';
        }

        $this->closeFormModal();
        $this->resetPage();
        $this->toast($title, $message, $type);
    }

    public function openStatusModal(int $measurementUnitId): void
    {
        $unidadMedida = UnidadMedida::query()->findOrFail($measurementUnitId);
        Gate::authorize('changeStatus', $unidadMedida);

        $this->statusMeasurementUnitId = $unidadMedida->id;
        $this->statusMeasurementUnitName = $unidadMedida->nombre_unidad_medida;
        $this->statusMeasurementUnitIsActive = $unidadMedida->estado_unidad_medida;
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->reset('statusMeasurementUnitId', 'statusMeasurementUnitName', 'statusMeasurementUnitIsActive');
    }

    public function changeStatus(CambiarEstadoUnidadMedidaAction $cambiarEstadoUnidadMedida): void
    {
        $unidadMedida = UnidadMedida::query()->findOrFail($this->statusMeasurementUnitId);
        Gate::authorize('changeStatus', $unidadMedida);

        $unidadMedida = $cambiarEstadoUnidadMedida($unidadMedida);

        $this->closeStatusModal();
        $this->toast(
            $unidadMedida->estado_unidad_medida ? 'Unidad activada' : 'Unidad desactivada',
            "La unidad de medida {$unidadMedida->nombre_unidad_medida} fue ".($unidadMedida->estado_unidad_medida ? 'activada' : 'desactivada').'.',
            $unidadMedida->estado_unidad_medida ? 'success' : 'warning',
        );
    }

    #[Computed]
    public function measurementUnits(): LengthAwarePaginator
    {
        return UnidadMedida::query()
            ->select([
                'id',
                'nombre_unidad_medida',
                'abreviatura_unidad_medida',
                'estado_unidad_medida',
                'created_at',
            ])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('nombre_unidad_medida', 'like', "%{$this->search}%")
                        ->orWhere('abreviatura_unidad_medida', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('estado_unidad_medida', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('estado_unidad_medida', false))
            ->orderBy('nombre_unidad_medida')
            ->paginate(10);
    }

    #[Computed]
    public function totalMeasurementUnits(): int
    {
        return UnidadMedida::query()->count();
    }

    #[Computed]
    public function activeMeasurementUnits(): int
    {
        return UnidadMedida::query()->where('estado_unidad_medida', true)->count();
    }

    public function render(): View
    {
        return view('livewire.panel.unidades-medida.index');
    }
}
