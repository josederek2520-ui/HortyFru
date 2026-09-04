<?php

namespace App\Livewire\Panel\Clients;

use App\Actions\Clients\ChangeClientStatusAction;
use App\Actions\Clients\CreateClientAction;
use App\Actions\Clients\UpdateClientAction;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\ClientForm;
use App\Models\Cliente;
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

    public ClientForm $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    public bool $showFormModal = false;

    public bool $showStatusModal = false;

    public ?int $editingClientId = null;

    public ?int $statusClientId = null;

    public string $statusClientName = '';

    public bool $statusClientIsActive = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Cliente::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', Cliente::class);

        $this->editingClientId = null;
        $this->form->clear();
        $this->showFormModal = true;
        $this->dispatch('client-form-opened');
    }

    public function openEditModal(int $clientId): void
    {
        $cliente = Cliente::query()->findOrFail($clientId);
        Gate::authorize('update', $cliente);

        $this->editingClientId = $cliente->id;
        $this->form->clear();
        $this->form->fillFrom($cliente);
        $this->showFormModal = true;
        $this->dispatch('client-form-opened');
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingClientId = null;
        $this->form->clear();
    }

    public function save(
        CreateClientAction $createClient,
        UpdateClientAction $updateClient,
    ): void {
        if ($this->editingClientId === null) {
            Gate::authorize('create', Cliente::class);

            $cliente = $createClient($this->form->validateForCreate());
            $title = 'Cliente registrado';
            $message = "El cliente {$cliente->razon_social} fue registrado correctamente.";
            $type = 'success';
        } else {
            $cliente = Cliente::query()->findOrFail($this->editingClientId);
            Gate::authorize('update', $cliente);

            if (Gate::denies('changeStatus', $cliente)) {
                $this->form->activo_cliente = $cliente->activo_cliente;
            }

            $cliente = $updateClient($cliente, $this->form->validateForUpdate($cliente));
            $title = 'Cliente actualizado';
            $message = "Los datos de {$cliente->razon_social} fueron actualizados.";
            $type = 'info';
        }

        $this->closeFormModal();
        $this->resetPage();
        $this->toast($title, $message, $type);
    }

    public function openStatusModal(int $clientId): void
    {
        $cliente = Cliente::query()->findOrFail($clientId);
        Gate::authorize('changeStatus', $cliente);

        $this->statusClientId = $cliente->id;
        $this->statusClientName = $cliente->razon_social;
        $this->statusClientIsActive = $cliente->activo_cliente;
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->reset('statusClientId', 'statusClientName', 'statusClientIsActive');
    }

    public function changeStatus(ChangeClientStatusAction $changeClientStatus): void
    {
        $cliente = Cliente::query()->findOrFail($this->statusClientId);
        Gate::authorize('changeStatus', $cliente);

        $cliente = $changeClientStatus($cliente);

        $this->closeStatusModal();
        $this->toast(
            $cliente->activo_cliente ? 'Cliente activado' : 'Cliente desactivado',
            "El cliente {$cliente->razon_social} fue ".($cliente->activo_cliente ? 'activado' : 'desactivado').'.',
            $cliente->activo_cliente ? 'success' : 'warning',
        );
    }

    #[Computed]
    public function clients(): LengthAwarePaginator
    {
        return Cliente::query()
            ->select([
                'id',
                'razon_social',
                'nit',
                'telefono_cliente',
                'email_cliente',
                'activo_cliente',
                'created_at',
            ])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('razon_social', 'like', "%{$this->search}%")
                        ->orWhere('nit', 'like', "%{$this->search}%")
                        ->orWhere('telefono_cliente', 'like', "%{$this->search}%")
                        ->orWhere('email_cliente', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('activo_cliente', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('activo_cliente', false))
            ->orderBy('razon_social')
            ->paginate(10);
    }

    #[Computed]
    public function totalClients(): int
    {
        return Cliente::query()->count();
    }

    #[Computed]
    public function activeClients(): int
    {
        return Cliente::query()->where('activo_cliente', true)->count();
    }

    public function render(): View
    {
        return view('livewire.panel.clients.index');
    }
}
