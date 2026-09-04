<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2 text-sm font-medium text-brand-500 dark:text-brand-400">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 16h14l-1.5-5h-11L5 16ZM7 11l1.5-4h7L17 11M7 16v2m10-2v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                Transporte
            </div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Vehículos</h1>
            <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">Administra los vehículos disponibles para las operaciones del sistema.</p>
        </div>

        @can('create', App\Models\Vehiculo::class)
            <button type="button" wire:click="openCreateModal" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                Nuevo vehículo
            </button>
        @endcan
    </div>

    <section aria-labelledby="vehicles-table-heading" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <h2 id="vehicles-table-heading" class="sr-only">Listado de vehículos</h2>

        <div class="grid grid-cols-1 gap-4 border-b border-gray-200 p-4 dark:border-gray-800 xl:grid-cols-[auto_minmax(18rem,32rem)_auto] xl:items-center xl:justify-between">
            <div class="grid grid-cols-3 rounded-xl bg-gray-100 p-1 dark:bg-gray-900" role="group" aria-label="Filtrar vehículos por estado">
                <button type="button" wire:click="$set('status', 'all')" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $status === 'all' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">Todos <span class="rounded-full bg-brand-50 px-1.5 py-0.5 text-xs text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ $this->totalVehicles }}</span></button>
                <button type="button" wire:click="$set('status', 'active')" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $status === 'active' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">Activos <span class="rounded-full bg-white px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $this->activeVehicles }}</span></button>
                <button type="button" wire:click="$set('status', 'inactive')" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $status === 'inactive' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">Inactivos <span class="rounded-full bg-white px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $this->totalVehicles - $this->activeVehicles }}</span></button>
            </div>

            <label class="relative block w-full xl:justify-self-center">
                <span class="sr-only">Buscar vehículos</span>
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7" /><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
                <input type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar por placa, marca o tipo..." class="h-11 w-full rounded-xl border border-gray-300 bg-transparent py-2.5 pl-11 pr-4 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-brand-500" />
            </label>

            <div class="hidden items-center justify-end gap-2 text-xs font-medium text-gray-500 xl:flex dark:text-gray-400"><span class="h-2 w-2 rounded-full bg-brand-500"></span>{{ $this->vehicles->total() }} {{ $this->vehicles->total() === 1 ? 'resultado' : 'resultados' }}</div>
        </div>

        @if ($this->vehicles->isEmpty())
            <div class="flex flex-col items-center gap-3 px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 16h14l-1.5-5h-11L5 16ZM7 11l1.5-4h7L17 11" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg></span>
                <div><h3 class="font-semibold text-gray-800 dark:text-white/90">No encontramos vehículos</h3><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Prueba con otra búsqueda o registra el primer vehículo.</p></div>
                @if ($search !== '' || $status !== 'all')<button type="button" wire:click="clearFilters" class="text-sm font-semibold text-brand-500">Limpiar filtros</button>@endif
            </div>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left">
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"><th class="px-5 py-3.5">Placa</th><th class="px-5 py-3.5">Marca y tipo</th><th class="px-5 py-3.5">Registro</th><th class="px-5 py-3.5">Estado</th><th class="px-5 py-3.5 text-right">Acciones</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->vehicles as $vehiculo)
                            <tr wire:key="vehicle-row-{{ $vehiculo->id }}" class="transition hover:bg-gray-50/80 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-4"><div class="flex items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 16h14l-1.5-5h-11L5 16ZM7 11l1.5-4h7L17 11" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg></span><span class="font-mono font-semibold tracking-wide text-gray-800 dark:text-white/90">{{ $vehiculo->placa_vehiculo }}</span></div></td>
                                <td class="px-5 py-4"><div class="flex flex-col gap-0.5 text-sm"><span class="font-medium text-gray-700 dark:text-gray-300">{{ $vehiculo->marca_vehiculo ?? 'Marca sin registrar' }}</span><span class="text-xs text-gray-400">{{ $vehiculo->tipo_vehiculo }}</span></div></td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $vehiculo->created_at->format('d/m/Y') }}</td>
                                <td class="px-5 py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $vehiculo->estado_vehiculo ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}"><span class="h-1.5 w-1.5 rounded-full {{ $vehiculo->estado_vehiculo ? 'bg-success-500' : 'bg-gray-400' }}"></span>{{ $vehiculo->estado_vehiculo ? 'Activo' : 'Inactivo' }}</span></td>
                                <td class="px-5 py-4"><div class="flex justify-end gap-2">@can('update', $vehiculo)<button type="button" wire:click="openEditModal({{ $vehiculo->id }})" class="h-9 rounded-lg border border-gray-300 px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Editar</button>@endcan @can('changeStatus', $vehiculo)<button type="button" wire:click="openStatusModal({{ $vehiculo->id }})" class="h-9 rounded-lg px-3 text-xs font-semibold {{ $vehiculo->estado_vehiculo ? 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' : 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' }}">{{ $vehiculo->estado_vehiculo ? 'Desactivar' : 'Activar' }}</button>@endcan</div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-gray-100 md:hidden dark:divide-gray-800">
                @foreach ($this->vehicles as $vehiculo)
                    <article wire:key="vehicle-card-{{ $vehiculo->id }}" class="flex flex-col gap-4 p-5">
                        <div class="flex items-start justify-between gap-3"><div><h3 class="font-mono font-semibold tracking-wide text-gray-800 dark:text-white/90">{{ $vehiculo->placa_vehiculo }}</h3><p class="mt-1 text-sm text-gray-500">{{ $vehiculo->tipo_vehiculo }}</p></div><span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $vehiculo->estado_vehiculo ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">{{ $vehiculo->estado_vehiculo ? 'Activo' : 'Inactivo' }}</span></div>
                        <dl class="rounded-xl bg-gray-50 p-3 text-xs dark:bg-gray-900/60"><dt class="text-gray-500">Marca</dt><dd class="mt-1 font-medium text-gray-700 dark:text-gray-300">{{ $vehiculo->marca_vehiculo ?? 'Sin registrar' }}</dd></dl>
                        <div class="grid grid-cols-2 gap-2">@can('update', $vehiculo)<button type="button" wire:click="openEditModal({{ $vehiculo->id }})" class="h-10 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Editar</button>@endcan @can('changeStatus', $vehiculo)<button type="button" wire:click="openStatusModal({{ $vehiculo->id }})" class="h-10 rounded-lg text-sm font-semibold {{ $vehiculo->estado_vehiculo ? 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' : 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' }}">{{ $vehiculo->estado_vehiculo ? 'Desactivar' : 'Activar' }}</button>@endcan</div>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $this->vehicles->links() }}</div>
        @endif
    </section>

    <div wire:show="showFormModal" x-cloak x-on:vehicle-form-opened.window="$nextTick(() => $refs.vehiclePlate.focus())" x-on:keydown.escape.window="$wire.closeFormModal()" class="fixed inset-0 z-99999 flex items-end justify-center overflow-y-auto sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="vehicle-form-title">
        <button type="button" wire:click="closeFormModal" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/55 backdrop-blur-sm" aria-label="Cerrar modal"></button>
        <div class="relative max-h-[95vh] w-full overflow-y-auto rounded-t-3xl bg-white shadow-theme-xl sm:max-w-2xl sm:rounded-3xl dark:bg-gray-900">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-gray-200 bg-white px-5 py-5 sm:px-6 dark:border-gray-800 dark:bg-gray-900"><div><h2 id="vehicle-form-title" class="text-lg font-semibold text-gray-900 dark:text-white">{{ $editingVehicleId === null ? 'Registrar vehículo' : 'Editar vehículo' }}</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Completa los datos de identificación del vehículo.</p></div><button type="button" wire:click="closeFormModal" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700" aria-label="Cerrar">×</button></div>

            <form wire:submit="save" class="flex flex-col gap-6 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <label class="flex flex-col gap-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Placa <span class="text-error-500">*</span></span><input x-ref="vehiclePlate" type="text" wire:model.blur="form.placa_vehiculo" autocomplete="off" placeholder="Ej. ABC-123" class="h-11 rounded-xl border bg-transparent px-4 font-mono text-sm uppercase tracking-wide text-gray-800 outline-none focus:ring-3 dark:text-white {{ $errors->has('form.placa_vehiculo') ? 'border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-400 focus:ring-brand-500/10 dark:border-gray-700' }}" />@error('form.placa_vehiculo')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>
                    <label class="flex flex-col gap-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Marca <span class="font-normal text-gray-400">(opcional)</span></span><input type="text" wire:model.blur="form.marca_vehiculo" placeholder="Ej. Toyota" class="h-11 rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white" />@error('form.marca_vehiculo')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>
                    <label class="flex flex-col gap-1.5 sm:col-span-2"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de vehículo <span class="text-error-500">*</span></span><input type="text" wire:model.blur="form.tipo_vehiculo" list="vehicle-types" placeholder="Ej. Camioneta" class="h-11 rounded-xl border bg-transparent px-4 text-sm text-gray-800 outline-none focus:ring-3 dark:text-white {{ $errors->has('form.tipo_vehiculo') ? 'border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-400 focus:ring-brand-500/10 dark:border-gray-700' }}" /><datalist id="vehicle-types"><option value="Camión"></option><option value="Camioneta"></option><option value="Automóvil"></option><option value="Motocicleta"></option></datalist>@error('form.tipo_vehiculo')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>
                </div>

                @if ($editingVehicleId === null || auth()->user()->can(App\Enums\PermissionName::VehiclesChangeStatus->value))
                    <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 p-4 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]"><span><span class="block text-sm font-semibold text-gray-800 dark:text-white/90">Vehículo activo</span><span class="mt-1 block text-xs leading-5 text-gray-500 dark:text-gray-400">Los vehículos inactivos se conservan en el historial, pero no estarán disponibles para nuevas operaciones.</span></span><input type="checkbox" wire:model="form.estado_vehiculo" class="peer sr-only" /><span class="relative mt-0.5 h-6 w-11 shrink-0 rounded-full bg-gray-300 transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:bg-brand-500 peer-checked:after:translate-x-5 dark:bg-gray-700"></span></label>
                @endif

                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end dark:border-gray-800"><button type="button" wire:click="closeFormModal" class="h-11 rounded-xl border border-gray-300 px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Cancelar</button><button type="submit" wire:loading.attr="disabled" wire:target="save" class="h-11 rounded-xl bg-brand-500 px-5 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:opacity-70"><span wire:loading.remove wire:target="save">{{ $editingVehicleId === null ? 'Registrar vehículo' : 'Guardar cambios' }}</span><span wire:loading wire:target="save">Guardando...</span></button></div>
            </form>
        </div>
    </div>

    <div wire:show="showStatusModal" x-cloak x-on:keydown.escape.window="$wire.closeStatusModal()" class="fixed inset-0 z-99999 flex items-center justify-center p-5" role="alertdialog" aria-modal="true" aria-labelledby="vehicle-status-title">
        <button type="button" wire:click="closeStatusModal" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/55 backdrop-blur-sm" aria-label="Cerrar confirmación"></button>
        <div class="relative w-full max-w-md rounded-3xl bg-white p-6 text-center shadow-theme-xl dark:bg-gray-900"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl {{ $statusVehicleIsActive ? 'bg-error-50 text-error-600 dark:bg-error-500/10 dark:text-error-400' : 'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400' }}"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 4.4 2.7 18a2 2 0 0 0 1.75 3h15.1a2 2 0 0 0 1.75-3L13.7 4.4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg></span><h2 id="vehicle-status-title" class="mt-5 text-lg font-semibold text-gray-900 dark:text-white">{{ $statusVehicleIsActive ? '¿Desactivar este vehículo?' : '¿Activar este vehículo?' }}</h2><p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400"><strong class="font-mono font-semibold tracking-wide text-gray-700 dark:text-gray-300">{{ $statusVehiclePlate }}</strong> {{ $statusVehicleIsActive ? 'dejará de estar disponible para nuevas operaciones, pero conservará todo su historial.' : 'volverá a estar disponible para nuevas operaciones.' }}</p><div class="mt-6 grid grid-cols-2 gap-3"><button type="button" wire:click="closeStatusModal" class="h-11 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Cancelar</button><button type="button" wire:click="changeStatus" wire:loading.attr="disabled" wire:target="changeStatus" class="h-11 rounded-xl text-sm font-semibold text-white disabled:opacity-70 {{ $statusVehicleIsActive ? 'bg-error-500 hover:bg-error-600' : 'bg-success-500 hover:bg-success-600' }}"><span wire:loading.remove wire:target="changeStatus">{{ $statusVehicleIsActive ? 'Sí, desactivar' : 'Sí, activar' }}</span><span wire:loading wire:target="changeStatus">Procesando...</span></button></div></div>
    </div>
</div>
