<?php

namespace App\Livewire\Panel\Users;

use App\Actions\Users\ChangeUserStatusAction;
use App\Actions\Users\CreateUserAction;
use App\Actions\Users\UpdateUserAction;
use App\Enums\PermissionName;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\UserForm;
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
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    public UserForm $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    #[Url(as: 'rol', except: '')]
    public string $role = '';

    public bool $showFormModal = false;

    public bool $showStatusModal = false;

    public ?int $editingUserId = null;

    public ?int $statusUserId = null;

    public string $statusUserName = '';

    public bool $statusUserIsActive = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', User::class);

        $this->editingUserId = null;
        $this->form->clear();
        $this->showFormModal = true;
        $this->dispatch('user-form-opened');
    }

    public function openEditModal(int $userId): void
    {
        $user = User::query()->with([
            'roles:id,name',
            'empleado:id,user_id,nombre_empleado,apellido_empleado',
        ])->findOrFail($userId);
        Gate::authorize('update', $user);

        $this->editingUserId = $user->id;
        $this->form->clear();
        $this->form->fillFrom($user);
        $this->showFormModal = true;
        $this->dispatch('user-form-opened');
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingUserId = null;
        $this->form->clear();
    }

    public function save(
        CreateUserAction $createUser,
        UpdateUserAction $updateUser,
    ): void {
        if ($this->editingUserId === null) {
            Gate::authorize('create', User::class);
            $user = $createUser($this->form->validateForCreate());
            $title = 'Cuenta creada';
            $message = "La cuenta de {$user->display_name} fue creada correctamente.";
            $type = 'success';
        } else {
            $user = User::query()->with('empleado:id,user_id,nombre_empleado,apellido_empleado')->findOrFail($this->editingUserId);
            Gate::authorize('update', $user);

            $currentRoleId = $user->roles()->value('roles.id');

            if ((int) $this->form->roleId !== (int) $currentRoleId) {
                Gate::authorize('assignRole', $user);
            }

            if ((int) $this->form->employeeId !== (int) $user->empleado?->id) {
                Gate::authorize(PermissionName::EmployeesAssignUser->value);
            }

            if ($user->is(auth()->user())) {
                $this->form->activo_usuario = true;
                $this->form->roleId = $user->roles()->value('roles.id');
            }

            $updatedUser = $updateUser($user, $this->form->validateForUpdate($user));
            $title = 'Cuenta actualizada';
            $message = "Los datos de {$updatedUser->display_name} fueron actualizados.";
            $type = 'info';
        }

        $this->closeFormModal();
        $this->resetPage();
        $this->toast($title, $message, $type);
    }

    public function openStatusModal(int $userId): void
    {
        $user = User::query()->with('empleado:id,user_id,nombre_empleado,apellido_empleado')->findOrFail($userId);
        Gate::authorize('changeStatus', $user);

        $this->statusUserId = $user->id;
        $this->statusUserName = $user->display_name;
        $this->statusUserIsActive = $user->activo_usuario;
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->reset('statusUserId', 'statusUserName', 'statusUserIsActive');
    }

    public function changeStatus(ChangeUserStatusAction $changeUserStatus): void
    {
        $user = User::query()->with('empleado:id,user_id,nombre_empleado,apellido_empleado')->findOrFail($this->statusUserId);
        Gate::authorize('changeStatus', $user);

        $updatedUser = $changeUserStatus($user);
        $state = $updatedUser->activo_usuario ? 'activado' : 'desactivado';

        $this->closeStatusModal();
        $this->toast(
            $updatedUser->activo_usuario ? 'Cuenta activada' : 'Cuenta desactivada',
            "El usuario {$updatedUser->display_name} fue {$state}.",
            $updatedUser->activo_usuario ? 'success' : 'warning',
        );
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->select(['id', 'name', 'email', 'activo_usuario', 'ultimo_acceso_usuario', 'created_at'])
            ->with([
                'roles:id,name',
                'empleado:id,user_id,nombre_empleado,apellido_empleado',
            ])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhereHas('empleado', function ($employeeQuery): void {
                            $employeeQuery
                                ->where('nombre_empleado', 'like', "%{$this->search}%")
                                ->orWhere('apellido_empleado', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('activo_usuario', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('activo_usuario', false))
            ->when($this->role !== '', fn ($query) => $query->whereHas(
                'roles',
                fn ($roleQuery) => $roleQuery->whereKey((int) $this->role),
            ))
            ->latest('id')
            ->paginate(10);
    }

    #[Computed]
    public function totalUsers(): int
    {
        return User::query()->count();
    }

    #[Computed]
    public function activeUsers(): int
    {
        return User::query()->where('activo_usuario', true)->count();
    }

    /** @return Collection<int, Empleado> */
    #[Computed]
    public function employees(): Collection
    {
        $currentEmployeeId = $this->editingUserId === null
            ? null
            : Empleado::query()->where('user_id', $this->editingUserId)->value('id');

        return Empleado::query()
            ->select(['id', 'user_id', 'nombre_empleado', 'apellido_empleado', 'ci_empleado', 'activo_empleado'])
            ->where(function ($query) use ($currentEmployeeId): void {
                $query->where(function ($query): void {
                    $query->whereNull('user_id')->where('activo_empleado', true);
                })->when($currentEmployeeId !== null, fn ($query) => $query->orWhere('id', $currentEmployeeId));
            })
            ->orderBy('apellido_empleado')
            ->orderBy('nombre_empleado')
            ->get();
    }

    /** @return Collection<int, Role> */
    #[Computed]
    public function roles(): Collection
    {
        return Role::query()
            ->select(['id', 'name'])
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.panel.users.index');
    }
}
