<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2 text-sm font-medium text-brand-500 dark:text-brand-400">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 21V7l8-4 8 4v14M8 10h2m4 0h2M8 14h2m4 0h2M9 21v-4h6v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                Clientes y puntos de atención
            </div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Sucursales</h1>
            <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">Registra y organiza las ubicaciones donde atiende cada cliente.</p>
        </div>

        @can('create', App\Models\Sucursal::class)
            <button type="button" wire:click="openCreateModal" @disabled($this->clients->isEmpty()) class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 disabled:cursor-not-allowed disabled:bg-gray-300 dark:disabled:bg-gray-700" title="{{ $this->clients->isEmpty() ? 'Primero registra un cliente' : 'Registrar una nueva sucursal' }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                Nueva sucursal
            </button>
        @endcan
    </div>

    @if ($this->clients->isEmpty())
        <div class="flex items-start gap-3 rounded-2xl border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-500/20 dark:bg-warning-500/10 dark:text-warning-300">
            <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 4.4 2.7 18a2 2 0 0 0 1.75 3h15.1a2 2 0 0 0 1.75-3L13.7 4.4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
            <div><p class="font-semibold">Primero necesitas un cliente</p><p class="mt-0.5 text-warning-700 dark:text-warning-400">Cada sucursal debe pertenecer a un cliente registrado.</p></div>
        </div>
    @endif

    <section aria-labelledby="branches-table-heading" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <h2 id="branches-table-heading" class="sr-only">Sucursales registradas</h2>

        <div class="grid grid-cols-1 gap-4 border-b border-gray-200 p-4 dark:border-gray-800 xl:grid-cols-[auto_minmax(18rem,30rem)_minmax(13rem,18rem)] xl:items-center xl:justify-between">
            <div class="grid grid-cols-3 rounded-xl bg-gray-100 p-1 dark:bg-gray-900" role="group" aria-label="Filtrar sucursales por estado">
                <button type="button" wire:click="$set('status', 'all')" aria-pressed="{{ $status === 'all' ? 'true' : 'false' }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $status === 'all' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">Todas <span class="rounded-full bg-brand-50 px-1.5 py-0.5 text-xs text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ $this->totalBranches }}</span></button>
                <button type="button" wire:click="$set('status', 'active')" aria-pressed="{{ $status === 'active' ? 'true' : 'false' }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $status === 'active' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">Activas <span class="rounded-full bg-white px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $this->activeBranches }}</span></button>
                <button type="button" wire:click="$set('status', 'inactive')" aria-pressed="{{ $status === 'inactive' ? 'true' : 'false' }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $status === 'inactive' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">Inactivas <span class="rounded-full bg-white px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $this->totalBranches - $this->activeBranches }}</span></button>
            </div>

            <label class="relative block w-full xl:justify-self-center">
                <span class="sr-only">Buscar sucursales</span>
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7" /><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
                <input type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar por sucursal, cliente o dirección..." class="h-11 w-full rounded-xl border border-gray-300 bg-transparent py-2.5 pl-11 pr-10 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-brand-500" />
                @if ($search !== '')
                    <button type="button" wire:click="$set('search', '')" class="absolute right-1.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300" aria-label="Limpiar búsqueda"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg></button>
                @endif
            </label>

            <label class="relative block w-full">
                <span class="sr-only">Filtrar por cliente</span>
                <select wire:model.live="client" class="h-11 w-full appearance-none rounded-xl border border-gray-300 bg-transparent px-4 pr-10 text-sm text-gray-700 outline-none transition focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-brand-500">
                    <option value="all">Todos los clientes</option>
                    @foreach ($this->clients as $availableClient)
                        <option value="{{ $availableClient->id }}">{{ $availableClient->razon_social }}{{ $availableClient->activo_cliente ? '' : ' (inactivo)' }}</option>
                    @endforeach
                </select>
                <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </label>
        </div>

        @if ($this->branches->isEmpty())
            <div class="flex flex-col items-center gap-3 px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 21V7l8-4 8 4v14M8 10h2m4 0h2M9 21v-5h6v5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg></span>
                <div><h3 class="font-semibold text-gray-800 dark:text-white/90">No encontramos sucursales</h3><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Prueba con otra búsqueda, cambia los filtros o registra la primera sucursal.</p></div>
                @if ($search !== '' || $status !== 'all' || $client !== 'all')
                    <button type="button" wire:click="clearFilters" class="text-sm font-semibold text-brand-500 hover:text-brand-600">Limpiar filtros</button>
                @endif
            </div>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left">
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50"><tr class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"><th class="px-5 py-3.5">Sucursal</th><th class="px-5 py-3.5">Cliente</th><th class="px-5 py-3.5">Ubicación</th><th class="px-5 py-3.5">Contacto</th><th class="px-5 py-3.5">Estado</th><th class="px-5 py-3.5 text-right">Acciones</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->branches as $branch)
                            <tr wire:key="branch-row-{{ $branch->id }}" class="transition hover:bg-gray-50/80 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-4"><div class="flex items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-sm font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ str($branch->nombre_sucursal)->substr(0, 1)->upper() }}</span><div><p class="font-semibold text-gray-800 dark:text-white/90">{{ $branch->nombre_sucursal }}</p><p class="text-xs text-gray-400">Registrada {{ $branch->created_at->format('d/m/Y') }}</p></div></div></td>
                                <td class="px-5 py-4"><p class="max-w-56 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $branch->cliente->razon_social }}</p>@unless ($branch->cliente->activo_cliente)<span class="mt-1 inline-flex rounded-full bg-warning-50 px-2 py-0.5 text-xs font-semibold text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">Cliente inactivo</span>@endunless</td>
                                <td class="px-5 py-4"><p class="max-w-72 text-sm text-gray-700 dark:text-gray-300">{{ $branch->direccion_sucursal }}</p>@if ($branch->referencia_sucursal)<p class="mt-1 max-w-72 text-xs text-gray-400">Ref. {{ $branch->referencia_sucursal }}</p>@endif</td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $branch->telefono_sucursal ?? 'Sin teléfono' }}</td>
                                <td class="px-5 py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $branch->activo_sucursal ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}"><span class="h-1.5 w-1.5 rounded-full {{ $branch->activo_sucursal ? 'bg-success-500' : 'bg-gray-400' }}"></span>{{ $branch->activo_sucursal ? 'Activa' : 'Inactiva' }}</span></td>
                                <td class="px-5 py-4"><div class="flex justify-end gap-2">@can('update', $branch)<button type="button" wire:click="openEditModal({{ $branch->id }})" class="h-9 rounded-lg border border-gray-300 px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Editar</button>@endcan @can('changeStatus', $branch)<button type="button" wire:click="openStatusModal({{ $branch->id }})" class="h-9 rounded-lg px-3 text-xs font-semibold {{ $branch->activo_sucursal ? 'bg-error-50 text-error-700 hover:bg-error-100 dark:bg-error-500/10 dark:text-error-400' : 'bg-success-50 text-success-700 hover:bg-success-100 dark:bg-success-500/10 dark:text-success-400' }}">{{ $branch->activo_sucursal ? 'Desactivar' : 'Activar' }}</button>@endcan</div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-gray-100 md:hidden dark:divide-gray-800">
                @foreach ($this->branches as $branch)
                    <article wire:key="branch-card-{{ $branch->id }}" class="flex flex-col gap-4 p-5">
                        <div class="flex items-start justify-between gap-3"><div class="min-w-0"><h3 class="truncate font-semibold text-gray-800 dark:text-white/90">{{ $branch->nombre_sucursal }}</h3><p class="mt-1 truncate text-sm text-gray-500">{{ $branch->cliente->razon_social }}</p></div><span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $branch->activo_sucursal ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">{{ $branch->activo_sucursal ? 'Activa' : 'Inactiva' }}</span></div>
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-900/60"><p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $branch->direccion_sucursal }}</p>@if ($branch->referencia_sucursal)<p class="mt-1 text-xs text-gray-500">Referencia: {{ $branch->referencia_sucursal }}</p>@endif<p class="mt-2 text-xs text-gray-500">Teléfono: {{ $branch->telefono_sucursal ?? 'Sin registrar' }}</p></div>
                        @unless ($branch->cliente->activo_cliente)<p class="rounded-lg bg-warning-50 px-3 py-2 text-xs font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">El cliente está inactivo; esta sucursal no estará disponible para nuevas operaciones.</p>@endunless
                        <div class="grid grid-cols-2 gap-2">@can('update', $branch)<button type="button" wire:click="openEditModal({{ $branch->id }})" class="h-10 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Editar</button>@endcan @can('changeStatus', $branch)<button type="button" wire:click="openStatusModal({{ $branch->id }})" class="h-10 rounded-lg text-sm font-semibold {{ $branch->activo_sucursal ? 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' : 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' }}">{{ $branch->activo_sucursal ? 'Desactivar' : 'Activar' }}</button>@endcan</div>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $this->branches->links() }}</div>
        @endif
    </section>

    <div wire:show="showFormModal" x-cloak x-on:branch-form-opened.window="$nextTick(() => $refs.branchClient.focus())" x-on:keydown.escape.window="$wire.closeFormModal()" class="fixed inset-0 z-99999 flex items-end justify-center overflow-y-auto sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="branch-form-title">
        <button type="button" wire:click="closeFormModal" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/55 backdrop-blur-sm" aria-label="Cerrar formulario"></button>
        <div class="relative max-h-[95vh] w-full overflow-y-auto rounded-t-3xl bg-white shadow-theme-xl sm:max-w-2xl sm:rounded-3xl dark:bg-gray-900">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-gray-200 bg-white px-5 py-5 sm:px-6 dark:border-gray-800 dark:bg-gray-900"><div><h2 id="branch-form-title" class="text-lg font-semibold text-gray-900 dark:text-white">{{ $editingBranchId === null ? 'Registrar sucursal' : 'Editar sucursal' }}</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Indica a qué cliente pertenece y cómo encontrar esta ubicación.</p></div><button type="button" wire:click="closeFormModal" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700" aria-label="Cerrar">×</button></div>

            <form wire:submit="save" class="flex flex-col gap-6 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <label class="flex flex-col gap-1.5 sm:col-span-2"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Cliente <span class="text-error-500">*</span></span><select x-ref="branchClient" wire:model.number="form.cliente_id" class="h-11 rounded-xl border bg-transparent px-4 text-sm text-gray-800 outline-none focus:ring-3 dark:bg-gray-900 dark:text-white {{ $errors->has('form.cliente_id') ? 'border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-400 focus:ring-brand-500/10 dark:border-gray-700' }}"><option value="">Selecciona un cliente</option>@foreach ($this->clients as $availableClient)<option value="{{ $availableClient->id }}">{{ $availableClient->razon_social }}{{ $availableClient->activo_cliente ? '' : ' (inactivo)' }}</option>@endforeach</select>@error('form.cliente_id')<span class="text-xs text-error-600">{{ $message }}</span>@enderror<span class="text-xs text-gray-400">La sucursal aparecerá dentro del historial y operaciones de este cliente.</span></label>
                    <label class="flex flex-col gap-1.5 sm:col-span-2"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Nombre de la sucursal <span class="text-error-500">*</span></span><input type="text" wire:model.blur="form.nombre_sucursal" maxlength="150" placeholder="Ej. Sucursal Central" class="h-11 rounded-xl border bg-transparent px-4 text-sm text-gray-800 outline-none focus:ring-3 dark:text-white {{ $errors->has('form.nombre_sucursal') ? 'border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-400 focus:ring-brand-500/10 dark:border-gray-700' }}" />@error('form.nombre_sucursal')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>
                    <label class="flex flex-col gap-1.5 sm:col-span-2"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Dirección <span class="text-error-500">*</span></span><input type="text" wire:model.blur="form.direccion_sucursal" maxlength="255" autocomplete="street-address" placeholder="Ej. Av. San Martín 123, Equipetrol" class="h-11 rounded-xl border bg-transparent px-4 text-sm text-gray-800 outline-none focus:ring-3 dark:text-white {{ $errors->has('form.direccion_sucursal') ? 'border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-400 focus:ring-brand-500/10 dark:border-gray-700' }}" />@error('form.direccion_sucursal')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>
                    <label class="flex flex-col gap-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono <span class="font-normal text-gray-400">(opcional)</span></span><input type="tel" wire:model.blur="form.telefono_sucursal" maxlength="30" autocomplete="tel" placeholder="Ej. 71234567" class="h-11 rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white" />@error('form.telefono_sucursal')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>
                    <label class="flex flex-col gap-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Referencia <span class="font-normal text-gray-400">(opcional)</span></span><input type="text" wire:model.blur="form.referencia_sucursal" maxlength="255" placeholder="Ej. Frente a la plaza principal" class="h-11 rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white" />@error('form.referencia_sucursal')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end dark:border-gray-800"><button type="button" wire:click="closeFormModal" class="h-11 rounded-xl border border-gray-300 px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Cancelar</button><button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex h-11 items-center justify-center rounded-xl bg-brand-500 px-5 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:cursor-wait disabled:opacity-70"><span wire:loading.remove wire:target="save">{{ $editingBranchId === null ? 'Registrar sucursal' : 'Guardar cambios' }}</span><span wire:loading wire:target="save">Guardando...</span></button></div>
            </form>
        </div>
    </div>

    <div wire:show="showStatusModal" x-cloak x-on:keydown.escape.window="$wire.closeStatusModal()" class="fixed inset-0 z-99999 flex items-center justify-center p-5" role="dialog" aria-modal="true" aria-labelledby="branch-status-title">
        <button type="button" wire:click="closeStatusModal" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/55 backdrop-blur-sm" aria-label="Cerrar confirmación"></button>
        <div class="relative w-full max-w-md rounded-3xl bg-white p-6 text-center shadow-theme-xl dark:bg-gray-900"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl {{ $statusBranchIsActive ? 'bg-error-50 text-error-600 dark:bg-error-500/10 dark:text-error-400' : 'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400' }}"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 4.4 2.7 18a2 2 0 0 0 1.75 3h15.1a2 2 0 0 0 1.75-3L13.7 4.4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg></span><h2 id="branch-status-title" class="mt-5 text-lg font-semibold text-gray-900 dark:text-white">{{ $statusBranchIsActive ? '¿Desactivar esta sucursal?' : '¿Activar esta sucursal?' }}</h2><p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400"><strong class="font-semibold text-gray-700 dark:text-gray-300">{{ $statusBranchName }}</strong> de {{ $statusBranchClient }} {{ $statusBranchIsActive ? 'ya no estará disponible para nuevas operaciones, pero conservará su información e historial.' : 'volverá a estar disponible para nuevas operaciones si su cliente también está activo.' }}</p><div class="mt-6 grid grid-cols-2 gap-3"><button type="button" wire:click="closeStatusModal" class="h-11 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Cancelar</button><button type="button" wire:click="changeStatus" wire:loading.attr="disabled" wire:target="changeStatus" class="h-11 rounded-xl text-sm font-semibold text-white disabled:cursor-wait disabled:opacity-70 {{ $statusBranchIsActive ? 'bg-error-500 hover:bg-error-600' : 'bg-success-500 hover:bg-success-600' }}"><span wire:loading.remove wire:target="changeStatus">{{ $statusBranchIsActive ? 'Sí, desactivar' : 'Sí, activar' }}</span><span wire:loading wire:target="changeStatus">Procesando...</span></button></div></div>
    </div>
</div>
