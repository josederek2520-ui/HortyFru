<?php

namespace App\Livewire\Panel\Roles;

use App\Actions\Roles\CreateRoleAction;
use App\Actions\Roles\DeleteRoleAction;
use App\Actions\Roles\UpdateRoleAction;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\RoleForm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    public RoleForm $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingRoleId = null;

    public ?int $deletingRoleId = null;

    public string $deletingRoleName = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Role::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', Role::class);

        $this->editingRoleId = null;
        $this->form->clear();
        $this->showFormModal = true;
        $this->dispatch('role-form-opened');
    }

    public function openEditModal(int $roleId): void
    {
        $role = Role::query()->with('permissions:id,name')->findOrFail($roleId);
        Gate::authorize('update', $role);

        $this->editingRoleId = $role->id;
        $this->form->clear();
        $this->form->fillFrom($role);
        $this->showFormModal = true;
        $this->dispatch('role-form-opened');
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingRoleId = null;
        $this->form->clear();
    }

    public function save(CreateRoleAction $createRole, UpdateRoleAction $updateRole): void
    {
        if ($this->editingRoleId === null) {
            Gate::authorize('create', Role::class);
            $role = $createRole($this->form->validateForCreate());
            $title = 'Rol creado';
            $message = "El rol {$role->name} fue creado correctamente.";
            $type = 'success';
        } else {
            $role = Role::query()->findOrFail($this->editingRoleId);
            Gate::authorize('update', $role);
            $updatedRole = $updateRole($role, $this->form->validateForUpdate($role));
            $title = 'Rol actualizado';
            $message = "El rol {$updatedRole->name} fue actualizado.";
            $type = 'info';
        }

        $this->closeFormModal();
        $this->resetPage();
        $this->toast($title, $message, $type);
    }

    /** @param list<int> $permissionIds */
    public function toggleModule(array $permissionIds): void
    {
        $currentPermissions = collect($this->form->permissions)->map(fn (int|string $id): int => (int) $id);
        $modulePermissions = collect($permissionIds);

        $this->form->permissions = $modulePermissions->every(
            fn (int $permissionId): bool => $currentPermissions->contains($permissionId),
        )
            ? $currentPermissions->diff($modulePermissions)->values()->all()
            : $currentPermissions->merge($modulePermissions)->unique()->values()->all();
    }

    public function openDeleteModal(int $roleId): void
    {
        $role = Role::query()->findOrFail($roleId);
        Gate::authorize('delete', $role);

        $this->deletingRoleId = $role->id;
        $this->deletingRoleName = $role->name;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->reset('deletingRoleId', 'deletingRoleName');
        $this->resetValidation('role');
    }

    public function delete(DeleteRoleAction $deleteRole): void
    {
        $role = Role::query()->findOrFail($this->deletingRoleId);
        Gate::authorize('delete', $role);

        try {
            $deleteRole($role);
        } catch (ValidationException $exception) {
            $this->addError('role', $exception->errors()['role'][0]);

            return;
        }

        $roleName = $role->name;
        $this->closeDeleteModal();
        $this->resetPage();
        $this->toast('Rol eliminado', "El rol {$roleName} fue eliminado.", 'error');
    }

    #[Computed]
    public function roles(): LengthAwarePaginator
    {
        return Role::query()
            ->select(['id', 'name', 'guard_name', 'created_at'])
            ->withCount(['permissions', 'users'])
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [RoleName::SuperAdministrator->value])
            ->orderBy('name')
            ->paginate(10);
    }

    /** @return array<string, array{label: string, permissions: list<array{id: int, name: string, label: string}>}> */
    #[Computed]
    public function permissionGroups(): array
    {
        if (! $this->showFormModal) {
            return [];
        }

        $permissions = Permission::query()
            ->select(['id', 'name'])
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        $groups = [];

        foreach ($permissions as $permission) {
            $permissionName = PermissionName::tryFrom($permission->name);

            if ($permissionName === null) {
                continue;
            }

            $module = $permissionName->module();
            $groups[$module] ??= [
                'label' => $permissionName->moduleLabel(),
                'permissions' => [],
            ];
            $groups[$module]['permissions'][] = [
                'id' => $permission->id,
                'name' => $permission->name,
                'label' => $permissionName->label(),
            ];
        }

        return $groups;
    }

    public function render(): View
    {
        return view('livewire.panel.roles.index');
    }
}
