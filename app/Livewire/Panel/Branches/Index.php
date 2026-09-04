<?php

namespace App\Livewire\Panel\Branches;

use App\Actions\Branches\ChangeBranchStatusAction;
use App\Actions\Branches\CreateBranchAction;
use App\Actions\Branches\UpdateBranchAction;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\BranchForm;
use App\Models\Cliente;
use App\Models\Sucursal;
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

    public BranchForm $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    #[Url(as: 'client', except: 'all')]
    public string $client = 'all';

    public bool $showFormModal = false;

    public bool $showStatusModal = false;

    public ?int $editingBranchId = null;

    public ?int $statusBranchId = null;

    public string $statusBranchName = '';

    public string $statusBranchClient = '';

    public bool $statusBranchIsActive = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Sucursal::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedClient(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'client');
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', Sucursal::class);

        $this->editingBranchId = null;
        $this->form->clear();
        $this->showFormModal = true;
        $this->dispatch('branch-form-opened');
    }

    public function openEditModal(int $branchId): void
    {
        $sucursal = Sucursal::query()->findOrFail($branchId);
        Gate::authorize('update', $sucursal);

        $this->editingBranchId = $sucursal->id;
        $this->form->clear();
        $this->form->fillFrom($sucursal);
        $this->showFormModal = true;
        $this->dispatch('branch-form-opened');
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingBranchId = null;
        $this->form->clear();
    }

    public function save(
        CreateBranchAction $createBranch,
        UpdateBranchAction $updateBranch,
    ): void {
        if ($this->editingBranchId === null) {
            Gate::authorize('create', Sucursal::class);

            $sucursal = $createBranch($this->form->validateForCreate());
            $title = 'Sucursal registrada';
            $message = "La sucursal {$sucursal->nombre_sucursal} fue registrada correctamente.";
            $type = 'success';
        } else {
            $sucursal = Sucursal::query()->findOrFail($this->editingBranchId);
            Gate::authorize('update', $sucursal);

            if (Gate::denies('changeStatus', $sucursal)) {
                $this->form->activo_sucursal = $sucursal->activo_sucursal;
            }

            $sucursal = $updateBranch($sucursal, $this->form->validateForUpdate($sucursal));
            $title = 'Sucursal actualizada';
            $message = "Los datos de {$sucursal->nombre_sucursal} fueron actualizados.";
            $type = 'info';
        }

        $this->closeFormModal();
        $this->resetPage();
        $this->toast($title, $message, $type);
    }

    public function openStatusModal(int $branchId): void
    {
        $sucursal = Sucursal::query()->with('cliente:id,razon_social')->findOrFail($branchId);
        Gate::authorize('changeStatus', $sucursal);

        $this->statusBranchId = $sucursal->id;
        $this->statusBranchName = $sucursal->nombre_sucursal;
        $this->statusBranchClient = $sucursal->cliente->razon_social;
        $this->statusBranchIsActive = $sucursal->activo_sucursal;
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->reset('statusBranchId', 'statusBranchName', 'statusBranchClient', 'statusBranchIsActive');
    }

    public function changeStatus(ChangeBranchStatusAction $changeBranchStatus): void
    {
        $sucursal = Sucursal::query()->findOrFail($this->statusBranchId);
        Gate::authorize('changeStatus', $sucursal);

        $sucursal = $changeBranchStatus($sucursal);

        $this->closeStatusModal();
        $this->toast(
            $sucursal->activo_sucursal ? 'Sucursal activada' : 'Sucursal desactivada',
            "La sucursal {$sucursal->nombre_sucursal} fue ".($sucursal->activo_sucursal ? 'activada' : 'desactivada').'.',
            $sucursal->activo_sucursal ? 'success' : 'warning',
        );
    }

    #[Computed]
    public function branches(): LengthAwarePaginator
    {
        return Sucursal::query()
            ->select([
                'id',
                'cliente_id',
                'nombre_sucursal',
                'direccion_sucursal',
                'telefono_sucursal',
                'referencia_sucursal',
                'activo_sucursal',
                'created_at',
            ])
            ->with('cliente:id,razon_social,activo_cliente')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('nombre_sucursal', 'like', "%{$this->search}%")
                        ->orWhere('direccion_sucursal', 'like', "%{$this->search}%")
                        ->orWhere('telefono_sucursal', 'like', "%{$this->search}%")
                        ->orWhere('referencia_sucursal', 'like', "%{$this->search}%")
                        ->orWhereHas('cliente', fn ($clientQuery) => $clientQuery->where('razon_social', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('activo_sucursal', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('activo_sucursal', false))
            ->when($this->client !== 'all', fn ($query) => $query->where('cliente_id', $this->client))
            ->orderBy('nombre_sucursal')
            ->paginate(10);
    }

    #[Computed]
    public function totalBranches(): int
    {
        return Sucursal::query()->count();
    }

    #[Computed]
    public function activeBranches(): int
    {
        return Sucursal::query()->where('activo_sucursal', true)->count();
    }

    /** @return Collection<int, Cliente> */
    #[Computed]
    public function clients(): Collection
    {
        return Cliente::query()
            ->select(['id', 'razon_social', 'activo_cliente'])
            ->orderBy('razon_social')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.panel.branches.index');
    }
}
