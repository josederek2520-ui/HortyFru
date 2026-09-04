<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-medium text-brand-500 dark:text-brand-400">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 20 6v5c0 5-3.4 8.6-8 10-4.6-1.4-8-5-8-10V6l8-3Z" stroke="currentColor" stroke-width="1.7" /></svg>
                Administración
            </div>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Roles y permisos</h1>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">Define qué acciones puede realizar cada tipo de usuario dentro del sistema.</p>
        </div>

        @can('create', Spatie\Permission\Models\Role::class)
            <button type="button" wire:click="openCreateModal" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                Nuevo rol
            </button>
        @endcan
    </div>

    <section aria-labelledby="roles-table-heading" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 border-b border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div>
                <h2 id="roles-table-heading" class="font-semibold text-gray-900 dark:text-white">Roles del sistema</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $this->roles->total() }} {{ $this->roles->total() === 1 ? 'rol configurado' : 'roles configurados' }}</p>
            </div>
            <label class="relative block w-full sm:max-w-sm">
                <span class="sr-only">Buscar roles</span>
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7" /><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
                <input type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar rol..." class="h-11 w-full rounded-xl border border-gray-300 bg-transparent py-2.5 pl-11 pr-4 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white dark:focus:border-brand-500" />
            </label>
        </div>

        @if ($this->roles->isEmpty())
            <div class="px-6 py-16 text-center">
                <h3 class="font-semibold text-gray-800 dark:text-white/90">No encontramos roles</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cambia la búsqueda o crea un nuevo rol.</p>
            </div>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left">
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-5 py-3.5">Rol</th>
                            <th class="px-5 py-3.5">Permisos</th>
                            <th class="px-5 py-3.5">Usuarios</th>
                            <th class="px-5 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->roles as $role)
                            <tr wire:key="role-row-{{ $role->id }}" class="transition hover:bg-gray-50/80 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 20 6v5c0 5-3.4 8.6-8 10-4.6-1.4-8-5-8-10V6l8-3Z" stroke="currentColor" stroke-width="1.6" /></svg></span>
                                        <div><p class="font-semibold text-gray-800 dark:text-white/90">{{ $role->name }}</p>@if ($role->name === App\Enums\RoleName::SuperAdministrator->value)<span class="text-xs text-brand-600 dark:text-brand-400">Protegido por el sistema</span>@endif</div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $role->permissions_count }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $role->users_count }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        @can('update', $role)
                                            <button type="button" wire:click="openEditModal({{ $role->id }})" class="h-9 rounded-lg border border-gray-300 px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Editar</button>
                                        @endcan
                                        @can('delete', $role)
                                            <button type="button" wire:click="openDeleteModal({{ $role->id }})" class="h-9 rounded-lg bg-error-50 px-3 text-xs font-semibold text-error-700 transition hover:bg-error-100 dark:bg-error-500/10 dark:text-error-400">Eliminar</button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-gray-100 md:hidden dark:divide-gray-800">
                @foreach ($this->roles as $role)
                    <article wire:key="role-card-{{ $role->id }}" class="p-5">
                        <div class="flex items-start justify-between gap-3"><div><h3 class="font-semibold text-gray-800 dark:text-white/90">{{ $role->name }}</h3><p class="mt-1 text-xs text-gray-500">{{ $role->permissions_count }} permisos · {{ $role->users_count }} usuarios</p></div>@if ($role->name === App\Enums\RoleName::SuperAdministrator->value)<span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">Protegido</span>@endif</div>
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            @can('update', $role)<button type="button" wire:click="openEditModal({{ $role->id }})" class="h-10 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Editar</button>@endcan
                            @can('delete', $role)<button type="button" wire:click="openDeleteModal({{ $role->id }})" class="h-10 rounded-lg bg-error-50 text-sm font-semibold text-error-700 dark:bg-error-500/10 dark:text-error-400">Eliminar</button>@endcan
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $this->roles->links() }}</div>
        @endif
    </section>

    <div wire:show="showFormModal" x-cloak x-on:role-form-opened.window="$nextTick(() => $refs.roleName.focus())" x-on:keydown.escape.window="$wire.closeFormModal()" class="fixed inset-0 z-99999 flex items-end justify-center overflow-y-auto sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="role-form-title">
        <button type="button" wire:click="closeFormModal" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/55 backdrop-blur-sm" aria-label="Cerrar modal"></button>
        <div class="relative max-h-[95vh] w-full overflow-y-auto rounded-t-3xl bg-white shadow-theme-xl sm:max-w-3xl sm:rounded-3xl dark:bg-gray-900">
            <div class="sticky top-0 z-10 flex items-start justify-between border-b border-gray-200 bg-white px-5 py-5 sm:px-6 dark:border-gray-800 dark:bg-gray-900">
                <div><h2 id="role-form-title" class="text-lg font-semibold text-gray-900 dark:text-white">{{ $editingRoleId === null ? 'Crear nuevo rol' : 'Editar rol' }}</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Asigna únicamente los permisos necesarios para sus funciones.</p></div>
                <button type="button" wire:click="closeFormModal" class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800" aria-label="Cerrar">×</button>
            </div>

            <form wire:submit="save" class="flex flex-col gap-6 p-5 sm:p-6">
                <label class="flex flex-col gap-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del rol <span class="text-error-500">*</span></span><input x-ref="roleName" type="text" wire:model.blur="form.name" placeholder="Ej. Encargado de almacén" class="h-11 rounded-xl border bg-transparent px-4 text-sm text-gray-800 outline-none focus:ring-3 dark:text-white {{ $errors->has('form.name') ? 'border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-400 focus:ring-brand-500/10 dark:border-gray-700' }}" />@error('form.name')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>

                <fieldset>
                    <legend class="text-sm font-semibold text-gray-800 dark:text-white/90">Permisos <span class="text-error-500">*</span></legend>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Puedes seleccionar un módulo completo o permisos individuales.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach ($this->permissionGroups as $module => $group)
                            @php
                                $modulePermissionIds = collect($group['permissions'])->pluck('id')->all();
                                $allModuleSelected = collect($modulePermissionIds)->every(fn ($id) => in_array($id, array_map('intval', $form->permissions), true));
                            @endphp
                            <section class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                                <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-3 dark:border-gray-800"><h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $group['label'] }}</h3><button type="button" wire:click="toggleModule({{ Illuminate\Support\Js::from($modulePermissionIds) }})" class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">{{ $allModuleSelected ? 'Quitar todos' : 'Seleccionar todos' }}</button></div>
                                <div class="mt-3 flex flex-col gap-3">
                                    @foreach ($group['permissions'] as $permission)
                                        <label class="flex cursor-pointer items-start gap-3 rounded-lg p-2 transition hover:bg-gray-50 dark:hover:bg-white/[0.03]"><input type="checkbox" value="{{ $permission['id'] }}" wire:model="form.permissions" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700" /><span><span class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $permission['label'] }}</span><span class="block text-xs text-gray-400">{{ $permission['name'] }}</span></span></label>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                    @error('form.permissions')<span class="mt-2 block text-xs text-error-600">{{ $message }}</span>@enderror
                    @error('form.permissions.*')<span class="mt-2 block text-xs text-error-600">{{ $message }}</span>@enderror
                </fieldset>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end dark:border-gray-800"><button type="button" wire:click="closeFormModal" class="h-11 rounded-xl border border-gray-300 px-5 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Cancelar</button><button type="submit" wire:loading.attr="disabled" wire:target="save" class="h-11 rounded-xl bg-brand-500 px-5 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:opacity-70"><span wire:loading.remove wire:target="save">{{ $editingRoleId === null ? 'Crear rol' : 'Guardar cambios' }}</span><span wire:loading wire:target="save">Guardando...</span></button></div>
            </form>
        </div>
    </div>

    <div wire:show="showDeleteModal" x-cloak x-on:keydown.escape.window="$wire.closeDeleteModal()" class="fixed inset-0 z-99999 flex items-center justify-center p-5" role="alertdialog" aria-modal="true" aria-labelledby="delete-role-title">
        <button type="button" wire:click="closeDeleteModal" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/55 backdrop-blur-sm" aria-label="Cerrar confirmación"></button>
        <div class="relative w-full max-w-md rounded-3xl bg-white p-6 text-center shadow-theme-xl dark:bg-gray-900">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-error-50 text-error-600 dark:bg-error-500/10 dark:text-error-400"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 14h10l1-14M9 7V4h6v3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg></span>
            <h2 id="delete-role-title" class="mt-5 text-lg font-semibold text-gray-900 dark:text-white">¿Eliminar este rol?</h2>
            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">Se eliminará <strong class="font-semibold text-gray-700 dark:text-gray-300">{{ $deletingRoleName }}</strong>. Esta acción solo está permitida si no tiene usuarios asignados.</p>
            @error('role')<p class="mt-3 rounded-lg bg-error-50 px-3 py-2 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">{{ $message }}</p>@enderror
            <div class="mt-6 grid grid-cols-2 gap-3"><button type="button" wire:click="closeDeleteModal" class="h-11 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Cancelar</button><button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete" class="h-11 rounded-xl bg-error-500 text-sm font-semibold text-white hover:bg-error-600 disabled:opacity-70"><span wire:loading.remove wire:target="delete">Sí, eliminar</span><span wire:loading wire:target="delete">Eliminando...</span></button></div>
        </div>
    </div>
</div>
