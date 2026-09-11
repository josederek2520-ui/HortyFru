<?php

namespace App\Livewire\Panel\Employees;

use App\Actions\Employees\ChangeEmployeeStatusAction;
use App\Actions\Employees\CreateEmployeeAction;
use App\Actions\Employees\UpdateEmployeeAction;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\EmployeeForm;
use App\Models\Empleado;
use App\Models\User;
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

    public EmployeeForm $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    public bool $showFormModal = false;

    public bool $showStatusModal = false;

    public ?int $editingEmployeeId = null;

    public ?int $statusEmployeeId = null;

    public string $statusEmployeeName = '';

    public bool $statusEmployeeIsActive = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Empleado::class);
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
        Gate::authorize('create', Empleado::class);

        $this->editingEmployeeId = null;
        $this->form->clear();
        $this->showFormModal = true;
        $this->dispatch('employee-form-opened');
    }

    public function openEditModal(int $employeeId): void
    {
        $empleado = Empleado::query()
            ->with('user:id,name,email,activo_usuario')
            ->findOrFail($employeeId);
        Gate::authorize('update', $empleado);

        $this->editingEmployeeId = $empleado->id;
        $this->form->clear();
        $this->form->fillFrom($empleado);
        $this->showFormModal = true;
        $this->dispatch('employee-form-opened');
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingEmployeeId = null;
        $this->form->clear();
    }

    public function save(
        CreateEmployeeAction $createEmployee,
        UpdateEmployeeAction $updateEmployee,
    ): void {
        if ($this->editingEmployeeId === null) {
            Gate::authorize('create', Empleado::class);

            if ($this->form->user_id !== null) {
                Gate::authorize('assignUser', new Empleado);
            }

            if ((int) $this->form->user_id === (int) auth()->id()) {
                $this->form->activo_empleado = true;
            }

            $empleado = $createEmployee($this->form->validateForCreate());
            $title = 'Empleado registrado';
            $message = "El empleado {$empleado->nombre_completo} fue registrado correctamente.";
            $type = 'success';
        } else {
            $empleado = Empleado::query()->findOrFail($this->editingEmployeeId);
            Gate::authorize('update', $empleado);

            if ((int) $this->form->user_id !== (int) $empleado->user_id) {
                Gate::authorize('assignUser', $empleado);
            }

            if ((int) $empleado->user_id === (int) auth()->id()) {
                $this->form->user_id = $empleado->user_id;
                $this->form->activo_empleado = true;
            }

            $updatedEmployee = $updateEmployee($empleado, $this->form->validateForUpdate($empleado));
            $title = 'Empleado actualizado';
            $message = "Los datos de {$updatedEmployee->nombre_completo} fueron actualizados.";
            $type = 'info';
        }

        $this->closeFormModal();
        $this->resetPage();
        $this->toast($title, $message, $type);
    }

    public function openStatusModal(int $employeeId): void
    {
        $empleado = Empleado::query()->findOrFail($employeeId);
        Gate::authorize('changeStatus', $empleado);

        $this->statusEmployeeId = $empleado->id;
        $this->statusEmployeeName = $empleado->nombre_completo;
        $this->statusEmployeeIsActive = $empleado->activo_empleado;
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->reset('statusEmployeeId', 'statusEmployeeName', 'statusEmployeeIsActive');
    }

    public function changeStatus(ChangeEmployeeStatusAction $changeEmployeeStatus): void
    {
        $empleado = Empleado::query()->findOrFail($this->statusEmployeeId);
        Gate::authorize('changeStatus', $empleado);

        $updatedEmployee = $changeEmployeeStatus($empleado);
        $state = $updatedEmployee->activo_empleado ? 'reincorporado' : 'retirado';

        $this->closeStatusModal();
        $this->toast(
            $updatedEmployee->activo_empleado ? 'Empleado reincorporado' : 'Empleado retirado',
            "El empleado {$updatedEmployee->nombre_completo} fue {$state}.",
            $updatedEmployee->activo_empleado ? 'success' : 'warning',
        );
    }

    #[Computed]
    public function employees(): LengthAwarePaginator
    {
        return Empleado::query()
            ->select([
                'id',
                'user_id',
                'nombre_empleado',
                'apellido_empleado',
                'ci_empleado',
                'telefono_empleado',
                'cargo_empleado',
                'fecha_ingreso_empleado',
                'activo_empleado',
                'created_at',
            ])
            ->with('user:id,name,email,activo_usuario')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('nombre_empleado', 'like', "%{$this->search}%")
                        ->orWhere('apellido_empleado', 'like', "%{$this->search}%")
                        ->orWhere('ci_empleado', 'like', "%{$this->search}%")
                        ->orWhere('cargo_empleado', 'like', "%{$this->search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('activo_empleado', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('activo_empleado', false))
            ->orderBy('apellido_empleado')
            ->orderBy('nombre_empleado')
            ->paginate(10);
    }

    #[Computed]
    public function totalEmployees(): int
    {
        return Empleado::query()->count();
    }

    #[Computed]
    public function activeEmployees(): int
    {
        return Empleado::query()->where('activo_empleado', true)->count();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function availableUsers(): Collection
    {
        if (! $this->showFormModal) {
            return new Collection;
        }

        $currentUserId = $this->editingEmployeeId === null
            ? null
            : Empleado::query()->whereKey($this->editingEmployeeId)->value('user_id');

        return User::query()
            ->select(['id', 'name', 'email', 'activo_usuario'])
            ->with('empleado:id,user_id,nombre_empleado,apellido_empleado')
            ->where(function ($query) use ($currentUserId): void {
                $query->whereDoesntHave('empleado')
                    ->when($currentUserId !== null, fn ($query) => $query->orWhere('id', $currentUserId));
            })
            ->orderBy('email')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.panel.employees.index');
    }
}
