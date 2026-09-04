<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2 text-sm font-medium text-brand-500 dark:text-brand-400">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 21V11M12 14c-4.5 0-7-2.5-7-7 4.5 0 7 2.5 7 7Zm0-3c0-4.5 2.5-7 7-7 0 4.5-2.5 7-7 7Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Administración
            </div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Usuarios</h1>
            <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Gestiona las cuentas, su acceso y el rol que tendrá cada usuario en el sistema.
            </p>
        </div>

        @can('create', App\Models\User::class)
            <button type="button" wire:click="openCreateModal"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 data-loading:pointer-events-none data-loading:opacity-70">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
                Nuevo usuario
            </button>
        @endcan
    </div>

    <section aria-labelledby="users-table-heading" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <h2 id="users-table-heading" class="sr-only">Cuentas del sistema</h2>

        <div class="grid grid-cols-1 gap-4 border-b border-gray-200 p-4 dark:border-gray-800 xl:grid-cols-[auto_minmax(18rem,26rem)_minmax(12rem,16rem)] xl:items-center xl:justify-between">
            <div class="grid grid-cols-3 rounded-xl bg-gray-100 p-1 dark:bg-gray-900" role="group" aria-label="Filtrar usuarios por estado">
                <button type="button" wire:click="$set('status', 'all')" aria-pressed="{{ $status === 'all' ? 'true' : 'false' }}"
                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-brand-500 {{ $status === 'all' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">
                    Todos
                    <span class="inline-flex min-w-6 items-center justify-center rounded-full px-1.5 py-0.5 text-xs {{ $status === 'all' ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">{{ $this->totalUsers }}</span>
                </button>
                <button type="button" wire:click="$set('status', 'active')" aria-pressed="{{ $status === 'active' ? 'true' : 'false' }}"
                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-brand-500 {{ $status === 'active' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">
                    Activos
                    <span class="inline-flex min-w-6 items-center justify-center rounded-full px-1.5 py-0.5 text-xs {{ $status === 'active' ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">{{ $this->activeUsers }}</span>
                </button>
                <button type="button" wire:click="$set('status', 'inactive')" aria-pressed="{{ $status === 'inactive' ? 'true' : 'false' }}"
                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-brand-500 {{ $status === 'inactive' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">
                    Inactivos
                    <span class="inline-flex min-w-6 items-center justify-center rounded-full px-1.5 py-0.5 text-xs {{ $status === 'inactive' ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">{{ $this->totalUsers - $this->activeUsers }}</span>
                </button>
            </div>

            <label class="relative block w-full xl:justify-self-center">
                <span class="sr-only">Buscar usuarios</span>
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7" />
                    <path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                </svg>
                <input type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar por nombre o correo..."
                    class="h-11 w-full rounded-xl border border-gray-300 bg-transparent py-2.5 pl-11 pr-10 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-brand-500" />
                @if ($search !== '')
                    <button type="button" wire:click="$set('search', '')" class="absolute right-1.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus-visible:outline-2 focus-visible:outline-brand-500 dark:hover:bg-gray-800 dark:hover:text-gray-300" aria-label="Limpiar búsqueda">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                    </button>
                @endif
            </label>

            <label class="relative block w-full">
                <span class="sr-only">Filtrar por rol</span>
                <select wire:model.live="role" class="h-11 w-full appearance-none rounded-xl border border-gray-300 bg-transparent px-4 pr-10 text-sm text-gray-700 outline-none transition focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-brand-500">
                    <option value="">Todos los roles</option>
                    @foreach ($this->roles as $availableRole)
                        <option value="{{ $availableRole->id }}">{{ $availableRole->name }}</option>
                    @endforeach
                </select>
                <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </label>
        </div>

        @if ($this->users->isEmpty())
            <div class="flex flex-col items-center gap-3 px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.6" />
                        <path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                </span>
                <div>
                    <h3 class="font-semibold text-gray-800 dark:text-white/90">No encontramos usuarios</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Prueba con otra búsqueda o cambia el filtro seleccionado.</p>
                </div>
                @if ($search !== '' || $status !== 'all' || $role !== '')
                    <button type="button" wire:click="$set('search', ''); $set('status', 'all'); $set('role', '')" class="text-sm font-semibold text-brand-500 hover:text-brand-600 focus-visible:outline-2 focus-visible:outline-brand-500">Limpiar filtros</button>
                @endif
            </div>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left">
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th scope="col" class="px-5 py-3.5">Usuario</th>
                            <th scope="col" class="px-5 py-3.5">Rol</th>
                            <th scope="col" class="px-5 py-3.5">Estado</th>
                            <th scope="col" class="px-5 py-3.5">Último acceso</th>
                            <th scope="col" class="px-5 py-3.5">Registro</th>
                            <th scope="col" class="px-5 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->users as $user)
                            <tr wire:key="user-row-{{ $user->id }}" class="transition hover:bg-gray-50/80 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-sm font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ str($user->display_name)->substr(0, 1)->upper() }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $user->display_name }}</p>
                                            <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4"><span class="inline-flex rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">{{ $user->roles->first()?->name ?? 'Sin rol' }}</span></td>
                                <td class="px-5 py-4">
                                    @if ($user->activo_usuario)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-success-500"></span>Activo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $user->ultimo_acceso_usuario?->format('d/m/Y H:i') ?? 'Aún no ingresó' }}
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $user->created_at->format('d/m/Y') }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        @can('update', $user)
                                            <button type="button" wire:click="openEditModal({{ $user->id }})" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.05]" aria-label="Editar a {{ $user->display_name }}">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                                Editar
                                            </button>
                                        @endcan
                                        @can('changeStatus', $user)
                                            <button type="button" wire:click="openStatusModal({{ $user->id }})"
                                                class="inline-flex h-9 items-center rounded-lg px-3 text-xs font-semibold transition focus-visible:outline-2 focus-visible:outline-brand-500 {{ $user->activo_usuario ? 'bg-error-50 text-error-700 hover:bg-error-100 dark:bg-error-500/10 dark:text-error-400' : 'bg-success-50 text-success-700 hover:bg-success-100 dark:bg-success-500/10 dark:text-success-400' }}">
                                                {{ $user->activo_usuario ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-gray-100 md:hidden dark:divide-gray-800">
                @foreach ($this->users as $user)
                    <article wire:key="user-card-{{ $user->id }}" class="flex flex-col gap-4 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-sm font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ str($user->display_name)->substr(0, 1)->upper() }}</span>
                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $user->display_name }}</h3>
                                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->activo_usuario ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">{{ $user->activo_usuario ? 'Activo' : 'Inactivo' }}</span>
                        </div>
                        <dl class="grid grid-cols-3 gap-3 rounded-xl bg-gray-50 p-3 text-xs dark:bg-gray-900/60">
                            <div><dt class="text-gray-500 dark:text-gray-400">Rol</dt><dd class="mt-1 font-medium text-gray-700 dark:text-gray-300">{{ $user->roles->first()?->name ?? 'Sin rol' }}</dd></div>
                            <div><dt class="text-gray-500 dark:text-gray-400">Último acceso</dt><dd class="mt-1 font-medium text-gray-700 dark:text-gray-300">{{ $user->ultimo_acceso_usuario?->format('d/m/Y H:i') ?? 'Sin acceso' }}</dd></div>
                            <div><dt class="text-gray-500 dark:text-gray-400">Registrado</dt><dd class="mt-1 font-medium text-gray-700 dark:text-gray-300">{{ $user->created_at->format('d/m/Y') }}</dd></div>
                        </dl>
                        <div class="grid grid-cols-2 gap-2">
                            @can('update', $user)
                                <button type="button" wire:click="openEditModal({{ $user->id }})" class="h-10 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Editar</button>
                            @endcan
                            @can('changeStatus', $user)
                                <button type="button" wire:click="openStatusModal({{ $user->id }})" class="h-10 rounded-lg text-sm font-semibold transition {{ $user->activo_usuario ? 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' : 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' }}">{{ $user->activo_usuario ? 'Desactivar' : 'Activar' }}</button>
                            @endcan
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                {{ $this->users->links() }}
            </div>
        @endif
    </section>

    <div wire:show="showFormModal" x-cloak x-data="{ showPassword: false, showConfirmation: false }"
        x-on:user-form-opened.window="$nextTick(() => $refs.nameInput.focus())"
        x-on:keydown.escape.window="$wire.closeFormModal()"
        class="fixed inset-0 z-99999 flex items-end justify-center overflow-y-auto p-0 sm:items-center sm:p-5"
        role="dialog" aria-modal="true" aria-labelledby="user-form-title">
        <button type="button" wire:click="closeFormModal" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/55 backdrop-blur-sm" aria-label="Cerrar modal"></button>

        <div class="relative max-h-[95vh] w-full overflow-y-auto rounded-t-3xl bg-white shadow-theme-xl sm:max-w-2xl sm:rounded-3xl dark:bg-gray-900">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-gray-200 bg-white px-5 py-5 sm:px-6 dark:border-gray-800 dark:bg-gray-900">
                <div>
                    <h2 id="user-form-title" class="text-lg font-semibold text-gray-900 dark:text-white">{{ $editingUserId === null ? 'Crear nuevo usuario' : 'Editar usuario' }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $editingUserId === null ? 'Crea una cuenta segura para ingresar al sistema.' : 'Actualiza la información y el acceso de la cuenta.' }}</p>
                </div>
                <button type="button" wire:click="closeFormModal" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition hover:bg-gray-200 hover:text-gray-700 focus-visible:outline-2 focus-visible:outline-brand-500 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white" aria-label="Cerrar">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                </button>
            </div>

            <form wire:submit="save" class="flex flex-col gap-6 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <label class="flex flex-col gap-1.5 sm:col-span-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Empleado <span class="text-error-500">*</span></span>
                        <select x-ref="nameInput" wire:model="form.employeeId" @disabled($editingUserId !== null && $form->employeeId !== null)
                            class="h-11 rounded-xl border bg-transparent px-4 text-sm text-gray-800 outline-none transition focus:ring-3 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 dark:text-white dark:disabled:bg-gray-800 {{ $errors->has('form.employeeId') ? 'border-error-400 focus:border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-400 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-500' }}">
                            <option value="">Selecciona un empleado sin cuenta</option>
                            @foreach ($this->employees as $availableEmployee)
                                <option value="{{ $availableEmployee->id }}">{{ $availableEmployee->nombre_completo }} · CI {{ $availableEmployee->ci_empleado ?? 'sin registrar' }}</option>
                            @endforeach
                        </select>
                        @if ($editingUserId !== null && $form->employeeId !== null)<span class="text-xs text-gray-500 dark:text-gray-400">La cuenta está vinculada permanentemente con este empleado.</span>@endif
                        @error('form.employeeId') <span class="text-xs text-error-600 dark:text-error-400">{{ $message }}</span> @enderror
                    </label>

                    @can(App\Enums\PermissionName::UsersAssignRole->value)
                        <label class="flex flex-col gap-1.5 sm:col-span-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Rol del usuario <span class="text-error-500">*</span></span>
                            <select wire:model="form.roleId" @disabled($editingUserId === auth()->id()) class="h-11 rounded-xl border bg-transparent px-4 text-sm text-gray-800 outline-none transition focus:ring-3 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 dark:text-white dark:disabled:bg-gray-800 {{ $errors->has('form.roleId') ? 'border-error-400 focus:border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-400 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-500' }}">
                                <option value="">Selecciona un rol</option>
                                @foreach ($this->roles as $availableRole)
                                    <option value="{{ $availableRole->id }}">{{ $availableRole->name }}</option>
                                @endforeach
                            </select>
                            @if ($editingUserId === auth()->id())<span class="text-xs text-gray-500 dark:text-gray-400">No puedes cambiar tu propio rol administrativo.</span>@endif
                            @error('form.roleId') <span class="text-xs text-error-600 dark:text-error-400">{{ $message }}</span> @enderror
                        </label>
                    @else
                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Rol del usuario</span>
                            <div class="flex h-11 items-center rounded-xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300">
                                {{ $this->roles->firstWhere('id', $form->roleId)?->name ?? 'Sin rol' }}
                            </div>
                        </div>
                    @endcan

                    <label class="flex flex-col gap-1.5 sm:col-span-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Correo electrónico <span class="text-error-500">*</span></span>
                        <input type="email" wire:model.blur="form.email" autocomplete="email" placeholder="usuario@empresa.com"
                            class="h-11 rounded-xl border bg-transparent px-4 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:ring-3 dark:text-white dark:placeholder:text-gray-500 {{ $errors->has('form.email') ? 'border-error-400 focus:border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-400 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-500' }}" />
                        @error('form.email') <span class="text-xs text-error-600 dark:text-error-400">{{ $message }}</span> @enderror
                    </label>

                    <label class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Contraseña @if ($editingUserId === null)<span class="text-error-500">*</span>@else<span class="font-normal text-gray-400">(opcional)</span>@endif</span>
                        <span class="relative">
                            <input x-bind:type="showPassword ? 'text' : 'password'" wire:model="form.password" autocomplete="new-password" placeholder="Mínimo 8 caracteres"
                                class="h-11 w-full rounded-xl border bg-transparent px-4 pr-11 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:ring-3 dark:text-white dark:placeholder:text-gray-500 {{ $errors->has('form.password') ? 'border-error-400 focus:border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-400 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-500' }}" />
                            <button type="button" x-on:click="showPassword = !showPassword" class="absolute right-1 top-1 flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800" x-bind:aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" stroke="currentColor" stroke-width="1.6" /><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.6" /></svg>
                            </button>
                        </span>
                        @error('form.password') <span class="text-xs text-error-600 dark:text-error-400">{{ $message }}</span> @enderror
                    </label>

                    <label class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Confirmar contraseña @if ($editingUserId === null)<span class="text-error-500">*</span>@endif</span>
                        <span class="relative">
                            <input x-bind:type="showConfirmation ? 'text' : 'password'" wire:model="form.password_confirmation" autocomplete="new-password" placeholder="Repite la contraseña"
                                class="h-11 w-full rounded-xl border bg-transparent px-4 pr-11 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:ring-3 dark:text-white dark:placeholder:text-gray-500 {{ $errors->has('form.password_confirmation') ? 'border-error-400 focus:border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-400 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-500' }}" />
                            <button type="button" x-on:click="showConfirmation = !showConfirmation" class="absolute right-1 top-1 flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800" x-bind:aria-label="showConfirmation ? 'Ocultar confirmación' : 'Mostrar confirmación'">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" stroke="currentColor" stroke-width="1.6" /><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.6" /></svg>
                            </button>
                        </span>
                        @error('form.password_confirmation') <span class="text-xs text-error-600 dark:text-error-400">{{ $message }}</span> @enderror
                    </label>
                </div>

                @if ($editingUserId === null || $editingUserId !== auth()->id())
                    <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 p-4 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
                        <span>
                            <span class="block text-sm font-semibold text-gray-800 dark:text-white/90">Permitir acceso al sistema</span>
                            <span class="mt-1 block text-xs leading-5 text-gray-500 dark:text-gray-400">Si está desactivado, el usuario no podrá iniciar sesión.</span>
                        </span>
                        <input type="checkbox" wire:model="form.activo_usuario" class="peer sr-only" />
                        <span class="relative mt-0.5 h-6 w-11 shrink-0 rounded-full bg-gray-300 transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:bg-brand-500 peer-checked:after:translate-x-5 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500 dark:bg-gray-700"></span>
                    </label>
                @endif

                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end dark:border-gray-800">
                    <button type="button" wire:click="closeFormModal" class="h-11 rounded-xl border border-gray-300 px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 disabled:cursor-wait disabled:opacity-70">
                        <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" /><path class="opacity-75" d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                        <span wire:loading.remove wire:target="save">{{ $editingUserId === null ? 'Crear usuario' : 'Guardar cambios' }}</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div wire:show="showStatusModal" x-cloak x-on:keydown.escape.window="$wire.closeStatusModal()" class="fixed inset-0 z-99999 flex items-center justify-center p-5" role="alertdialog" aria-modal="true" aria-labelledby="status-modal-title" aria-describedby="status-modal-description">
        <button type="button" wire:click="closeStatusModal" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/55 backdrop-blur-sm" aria-label="Cerrar confirmación"></button>
        <div class="relative w-full max-w-md rounded-3xl bg-white p-6 text-center shadow-theme-xl dark:bg-gray-900">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl {{ $statusUserIsActive ? 'bg-error-50 text-error-600 dark:bg-error-500/10 dark:text-error-400' : 'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400' }}">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 4.4 2.7 18a2 2 0 0 0 1.75 3h15.1a2 2 0 0 0 1.75-3L13.7 4.4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </span>
            <h2 id="status-modal-title" class="mt-5 text-lg font-semibold text-gray-900 dark:text-white">{{ $statusUserIsActive ? '¿Desactivar este usuario?' : '¿Activar este usuario?' }}</h2>
            <p id="status-modal-description" class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                @if ($statusUserIsActive)
                    <strong class="font-semibold text-gray-700 dark:text-gray-300">{{ $statusUserName }}</strong> no podrá iniciar sesión hasta que vuelvas a activar su cuenta.
                @else
                    <strong class="font-semibold text-gray-700 dark:text-gray-300">{{ $statusUserName }}</strong> recuperará inmediatamente el acceso al sistema.
                @endif
            </p>
            <div class="mt-6 grid grid-cols-2 gap-3">
                <button type="button" wire:click="closeStatusModal" class="h-11 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Cancelar</button>
                <button type="button" wire:click="changeStatus" wire:loading.attr="disabled" wire:target="changeStatus" class="h-11 rounded-xl text-sm font-semibold text-white transition focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-wait disabled:opacity-70 {{ $statusUserIsActive ? 'bg-error-500 hover:bg-error-600 focus-visible:outline-error-500' : 'bg-success-500 hover:bg-success-600 focus-visible:outline-success-500' }}">
                    <span wire:loading.remove wire:target="changeStatus">{{ $statusUserIsActive ? 'Sí, desactivar' : 'Sí, activar' }}</span>
                    <span wire:loading wire:target="changeStatus">Procesando...</span>
                </button>
            </div>
        </div>
    </div>
</div>
