@php
    $hasFilters = $search !== '' || $module !== 'all' || $event !== 'all' || $dateFrom !== '' || $dateTo !== '';
    $advancedFilterCount = collect([$event !== 'all', $dateFrom !== '', $dateTo !== ''])->filter()->count();
@endphp

<div class="flex flex-col gap-5">
    <header class="flex flex-col gap-1">
        <div class="flex items-center gap-2 text-sm font-medium text-brand-500 dark:text-brand-400">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 20 6v5c0 5-3.4 8.6-8 10-4.6-1.4-8-5-8-10V6l8-3ZM8 12h8M12 8v8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
            Seguridad y auditoría
        </div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Registro de actividad</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Consulta los cambios y accesos registrados en el sistema.</p>
    </header>

    <section x-data="{ filtersOpen: false }" x-on:keydown.escape.window="filtersOpen = false" aria-labelledby="activity-table-heading" class="overflow-visible rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 border-b border-gray-200 px-4 py-4 lg:flex-row lg:items-center lg:justify-between lg:px-5 dark:border-gray-800">
            <div class="min-w-44">
                <h2 id="activity-table-heading" class="font-semibold text-gray-900 dark:text-white">Historial del sistema</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $this->activities->total() }} {{ $this->activities->total() === 1 ? 'actividad encontrada' : 'actividades encontradas' }}</p>
            </div>

            <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center lg:justify-end">
                <label class="relative block sm:w-52">
                    <span class="sr-only">Filtrar por módulo</span>
                    <select wire:model.change.live="module" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-none transition focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="all">Todos los módulos</option>
                        @foreach ($this->modules as $availableModule)
                            <option value="{{ $availableModule->value }}">{{ $availableModule->label() }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center">
                    <label class="relative block min-w-0 sm:w-72">
                        <span class="sr-only">Buscar actividades</span>
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7" /><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
                        <input type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar actividad..." class="h-11 w-full rounded-xl border border-gray-300 bg-transparent py-2.5 pl-11 pr-4 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white dark:placeholder:text-gray-500" />
                    </label>

                    <div class="relative flex gap-2">
                        <button type="button" x-on:click="filtersOpen = ! filtersOpen" x-bind:aria-expanded="filtersOpen" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 sm:flex-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.05]" aria-controls="activity-filters">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h10M18 7h2M14 7a2 2 0 1 0 4 0 2 2 0 0 0-4 0ZM4 17h2m4 0h10M6 17a2 2 0 1 0 4 0 2 2 0 0 0-4 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
                            Filtrar
                            @if ($advancedFilterCount > 0)
                                <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-500 px-1 text-[11px] text-white">{{ $advancedFilterCount }}</span>
                            @endif
                        </button>

                        @can('export', App\Models\Activity::class)
                            <button type="button" wire:click="export" wire:loading.attr="disabled" wire:target="export" class="flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-3 text-xs font-semibold text-gray-600 transition hover:bg-gray-50 disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.05]" aria-label="Exportar historial en CSV" title="Exportar CSV">
                                <svg wire:loading.remove wire:target="export" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                <svg wire:loading wire:target="export" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" class="opacity-25" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                                CSV
                            </button>
                            <button type="button" wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf" class="flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-brand-500 px-3 text-xs font-semibold text-white transition hover:bg-brand-600 disabled:opacity-60" aria-label="Exportar historial en PDF" title="Exportar PDF">
                                <svg wire:loading.remove wire:target="exportPdf" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h7l4 4v14H7V3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /><path d="M14 3v5h4M9.5 16.5h5M9.5 13h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
                                <svg wire:loading wire:target="exportPdf" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" class="opacity-25" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                                PDF
                            </button>
                        @endcan

                        <div id="activity-filters" x-cloak x-show="filtersOpen" x-transition.origin.top.right x-on:click.outside="filtersOpen = false" class="absolute right-0 top-full z-30 mt-2 w-[min(22rem,calc(100vw-2rem))] rounded-xl border border-gray-200 bg-white p-4 shadow-theme-lg dark:border-gray-700 dark:bg-gray-900">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Filtros avanzados</h3>
                                @if ($hasFilters)
                                    <button type="button" wire:click="clearFilters" class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">Limpiar todo</button>
                                @endif
                            </div>
                            <div class="mt-4 flex flex-col gap-4">
                                <label class="flex flex-col gap-1.5">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Tipo de evento</span>
                                    <select wire:model.live="event" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-none focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        <option value="all">Todos los eventos</option>
                                        @foreach ($this->events as $availableEvent)
                                            <option value="{{ $availableEvent->value }}">{{ $availableEvent->label() }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <label class="flex flex-col gap-1.5"><span class="text-xs font-medium text-gray-600 dark:text-gray-300">Desde</span><input type="date" wire:model.live="dateFrom" class="h-11 min-w-0 rounded-xl border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-none focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-gray-300" /></label>
                                    <label class="flex flex-col gap-1.5"><span class="text-xs font-medium text-gray-600 dark:text-gray-300">Hasta</span><input type="date" wire:model.live="dateTo" class="h-11 min-w-0 rounded-xl border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-none focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-gray-300" /></label>
                                </div>
                                <button type="button" x-on:click="filtersOpen = false" class="h-10 rounded-xl bg-brand-500 px-4 text-sm font-semibold text-white transition hover:bg-brand-600">Aplicar filtros</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($hasFilters)
            <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 bg-gray-50/70 px-4 py-2.5 text-xs dark:border-gray-800 dark:bg-gray-900/40">
                <span class="font-medium text-gray-500 dark:text-gray-400">Filtros activos:</span>
                @if ($module !== 'all')<span class="rounded-full bg-white px-2.5 py-1 font-medium text-gray-700 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700">{{ App\Enums\ActivityLogName::tryFrom($module)?->label() }}</span>@endif
                @if ($event !== 'all')<span class="rounded-full bg-white px-2.5 py-1 font-medium text-gray-700 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700">{{ App\Enums\ActivityEvent::tryFrom($event)?->label() }}</span>@endif
                @if ($dateFrom !== '' || $dateTo !== '')<span class="rounded-full bg-white px-2.5 py-1 font-medium text-gray-700 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700">{{ $dateFrom !== '' ? $this->formatPropertyValue($dateFrom, 'fecha_ingreso_empleado') : 'Inicio' }} — {{ $dateTo !== '' ? $this->formatPropertyValue($dateTo, 'fecha_ingreso_empleado') : 'Hoy' }}</span>@endif
                @if ($search !== '')<span class="max-w-56 truncate rounded-full bg-white px-2.5 py-1 font-medium text-gray-700 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700">“{{ $search }}”</span>@endif
                <button type="button" wire:click="clearFilters" class="ml-auto font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">Limpiar</button>
            </div>
        @endif

        <div wire:loading.delay class="border-b border-brand-100 bg-brand-50 px-5 py-2 text-xs font-medium text-brand-600 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-400">Actualizando el historial...</div>

        @if ($this->activities->isEmpty())
            <div class="flex flex-col items-center gap-3 px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16M4 12h16M4 19h10" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg></span>
                <div><h3 class="font-semibold text-gray-800 dark:text-white/90">No hay actividades para mostrar</h3><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cambia los filtros o realiza una acción dentro del sistema.</p></div>
            </div>
        @else
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full min-w-280 text-left">
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            <th class="w-24 px-5 py-3.5">Registro</th><th class="px-5 py-3.5">Responsable</th><th class="px-5 py-3.5">Actividad</th><th class="px-5 py-3.5">Fecha y hora</th><th class="px-5 py-3.5">Módulo</th><th class="px-5 py-3.5">Dirección IP</th><th class="w-16 px-5 py-3.5"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->activities as $activity)
                            <tr wire:key="activity-row-{{ $activity->id }}" class="transition hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-gray-700 dark:text-gray-300">#{{ $activity->id }}</td>
                                <td class="px-5 py-4"><p class="max-w-52 truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $activity->causer_name }}</p><p class="mt-0.5 max-w-52 truncate text-xs text-gray-500 dark:text-gray-400">{{ $activity->causer instanceof App\Models\User ? $activity->causer->email : 'Proceso del sistema' }}</p></td>
                                <td class="px-5 py-4">
                                    <p class="max-w-sm text-sm font-medium text-gray-800 dark:text-gray-200">{{ $activity->description }}</p>
                                    <span @class(['mt-1.5 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' => in_array($activity->event, ['created', 'login_succeeded'], true), 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' => in_array($activity->event, ['deleted', 'login_failed', 'login_blocked'], true), 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400' => ! in_array($activity->event, ['created', 'login_succeeded', 'deleted', 'login_failed', 'login_blocked'], true)])>{{ $activity->event_label }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4"><p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $activity->local_created_at?->format('d/m/Y') }}</p><p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $activity->local_created_at?->format('H:i:s') }}</p></td>
                                <td class="px-5 py-4"><p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $activity->module_label }}</p><p class="mt-0.5 max-w-48 truncate text-xs text-gray-500 dark:text-gray-400">{{ $activity->subject_label }}</p></td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $activity->properties?->get('ip_address', '—') }}</td>
                                <td class="px-5 py-4 text-right">@can('view', $activity)<button type="button" wire:click="openDetail({{ $activity->id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-white" aria-label="Ver detalle" title="Ver detalle"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7" /><circle cx="12" cy="12" r="1.7" /><circle cx="19" cy="12" r="1.7" /></svg></button>@endcan</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-gray-100 lg:hidden dark:divide-gray-800">
                @foreach ($this->activities as $activity)
                    <article wire:key="activity-card-{{ $activity->id }}" class="flex flex-col gap-4 p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="text-xs font-medium text-gray-500 dark:text-gray-400">#{{ $activity->id }} · {{ $activity->local_created_at?->format('d/m/Y H:i:s') }}</p><h3 class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $activity->description }}</h3></div><span class="shrink-0 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">{{ $activity->module_label }}</span></div>
                        <dl class="grid grid-cols-2 gap-3 rounded-xl bg-gray-50 p-3 text-xs dark:bg-gray-900/60"><div class="min-w-0"><dt class="text-gray-500">Responsable</dt><dd class="mt-1 truncate font-medium text-gray-700 dark:text-gray-300">{{ $activity->causer_name }}</dd></div><div><dt class="text-gray-500">Evento</dt><dd class="mt-1 font-medium text-gray-700 dark:text-gray-300">{{ $activity->event_label }}</dd></div><div class="min-w-0"><dt class="text-gray-500">Registro afectado</dt><dd class="mt-1 truncate font-medium text-gray-700 dark:text-gray-300">{{ $activity->subject_label }}</dd></div><div><dt class="text-gray-500">Dirección IP</dt><dd class="mt-1 font-medium text-gray-700 dark:text-gray-300">{{ $activity->properties?->get('ip_address', '—') }}</dd></div></dl>
                        @can('view', $activity)<button type="button" wire:click="openDetail({{ $activity->id }})" class="h-10 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Ver detalle</button>@endcan
                    </article>
                @endforeach
            </div>
            <div class="border-t border-gray-200 px-4 py-4 sm:px-5 dark:border-gray-800">{{ $this->activities->links() }}</div>
        @endif
    </section>

    @if ($showDetailModal && $this->selectedActivity)
        @php
            $detailActivity = $this->selectedActivity;
            $oldValues = collect($detailActivity->properties?->get('old', []));
            $newValues = collect($detailActivity->properties?->get('attributes', []));
            $contextValues = $detailActivity->properties?->except(['old', 'attributes']) ?? collect();
        @endphp
        <div x-cloak x-on:keydown.escape.window="$wire.closeDetail()" class="fixed inset-0 z-99999 flex items-end justify-center overflow-y-auto sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="activity-detail-title">
            <button type="button" wire:click="closeDetail" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/55 backdrop-blur-sm" aria-label="Cerrar detalle"></button>
            <div class="relative max-h-[95vh] w-full overflow-y-auto rounded-t-3xl bg-white shadow-theme-xl sm:max-w-3xl sm:rounded-3xl dark:bg-gray-900">
                <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-gray-200 bg-white px-5 py-5 sm:px-6 dark:border-gray-800 dark:bg-gray-900">
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-400">{{ $detailActivity->module_label }} · {{ $detailActivity->event_label }}</p><h2 id="activity-detail-title" class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $detailActivity->description }}</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $detailActivity->local_created_at?->format('d/m/Y H:i:s') }} · {{ $detailActivity->causer_name }}</p></div>
                    <button type="button" wire:click="closeDetail" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition hover:text-gray-800 dark:bg-gray-800 dark:hover:text-white" aria-label="Cerrar">×</button>
                </div>
                <div class="flex flex-col gap-6 p-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-4 rounded-2xl bg-gray-50 p-4 sm:grid-cols-2 dark:bg-gray-800/60"><div><dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Responsable</dt><dd class="mt-1 text-sm font-semibold text-gray-800 dark:text-white/90">{{ $detailActivity->causer_name }}</dd></div><div><dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Registro afectado</dt><dd class="mt-1 text-sm font-semibold text-gray-800 dark:text-white/90">{{ $detailActivity->subject_label }}</dd></div></dl>
                    @if ($oldValues->isNotEmpty() || $newValues->isNotEmpty())
                        <section><h3 class="text-sm font-semibold text-gray-900 dark:text-white">Cambios realizados</h3><div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-error-200 bg-error-50/50 p-4 dark:border-error-500/20 dark:bg-error-500/10"><h4 class="text-xs font-semibold uppercase tracking-wide text-error-700 dark:text-error-400">Valores anteriores</h4><dl class="mt-3 flex flex-col gap-3">@forelse ($oldValues as $property => $value)<div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ $this->propertyLabel($property) }}</dt><dd class="mt-0.5 break-words text-sm font-medium text-gray-800 dark:text-gray-200">{{ $this->formatPropertyValue($value, $property) }}</dd></div>@empty<p class="text-sm text-gray-500">No aplica</p>@endforelse</dl></div>
                            <div class="rounded-2xl border border-success-200 bg-success-50/50 p-4 dark:border-success-500/20 dark:bg-success-500/10"><h4 class="text-xs font-semibold uppercase tracking-wide text-success-700 dark:text-success-400">Valores nuevos</h4><dl class="mt-3 flex flex-col gap-3">@forelse ($newValues as $property => $value)<div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ $this->propertyLabel($property) }}</dt><dd class="mt-0.5 break-words text-sm font-medium text-gray-800 dark:text-gray-200">{{ $this->formatPropertyValue($value, $property) }}</dd></div>@empty<p class="text-sm text-gray-500">No aplica</p>@endforelse</dl></div>
                        </div></section>
                    @endif
                    @if ($contextValues->isNotEmpty())
                        <section><h3 class="text-sm font-semibold text-gray-900 dark:text-white">Contexto de seguridad</h3><dl class="mt-3 grid grid-cols-1 gap-4 rounded-2xl border border-gray-200 p-4 sm:grid-cols-2 dark:border-gray-800">@foreach ($contextValues as $property => $value)<div class="min-w-0"><dt class="text-xs text-gray-500 dark:text-gray-400">{{ $this->propertyLabel($property) }}</dt><dd class="mt-1 break-words text-sm font-medium text-gray-700 dark:text-gray-300">{{ $this->formatPropertyValue($value, $property) }}</dd></div>@endforeach</dl></section>
                    @endif
                    <button type="button" wire:click="closeDetail" class="h-11 self-end rounded-xl border border-gray-300 px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Cerrar</button>
                </div>
            </div>
        </div>
    @endif
</div>
