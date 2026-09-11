<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2 text-sm font-medium text-brand-500 dark:text-brand-400">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16v13H4V7Zm3 0V4h10v3M8 11h8M8 15h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                Inventario y trazabilidad
            </div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Lotes</h1>
            <p class="max-w-3xl text-sm text-gray-500 dark:text-gray-400">Identifica cada grupo de productos que ingresa junto y permite priorizar las salidas por FEFO.</p>
        </div>

        <span class="inline-flex min-h-10 items-center gap-2 self-start rounded-xl border border-brand-200 bg-brand-50 px-4 text-sm font-semibold text-brand-700 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-400">
            <span class="h-2 w-2 rounded-full bg-brand-500"></span>
            Generación automática desde recepción
        </span>
    </div>

    <section aria-labelledby="lots-table-heading" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <h2 id="lots-table-heading" class="sr-only">Listado de lotes</h2>

        <div class="grid grid-cols-1 gap-4 border-b border-gray-200 p-4 dark:border-gray-800 xl:grid-cols-[auto_minmax(18rem,32rem)_auto] xl:items-center xl:justify-between">
            <div class="flex max-w-full overflow-x-auto rounded-xl bg-gray-100 p-1 dark:bg-gray-900" role="group" aria-label="Filtrar lotes por estado">
                <button type="button" wire:click="$set('status', 'all')" class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $status === 'all' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">Todos <span class="text-xs">{{ $this->totalLots }}</span></button>
                @foreach (App\Enums\EstadoLote::cases() as $estado)
                    <button type="button" wire:click="$set('status', '{{ $estado->value }}')" class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $status === $estado->value ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">{{ $estado->label() }} <span class="text-xs">{{ $this->statusCounts[$estado->value] }}</span></button>
                @endforeach
            </div>

            <label class="relative block w-full xl:justify-self-center">
                <span class="sr-only">Buscar lotes</span>
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7" /><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
                <input type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar por lote, producto, categoría o calidad..." class="h-11 w-full rounded-xl border border-gray-300 bg-transparent py-2.5 pl-11 pr-4 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white dark:placeholder:text-gray-500" />
            </label>

            <button type="button" wire:click="clearFilters" @disabled($search === '' && $status === 'all') class="hidden h-11 rounded-xl border border-gray-300 px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 xl:block dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Limpiar</button>
        </div>

        @if ($this->lots->isEmpty())
            <div class="flex flex-col items-center gap-3 px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16v13H4V7Zm3 0V4h10v3M8 11h8M8 15h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg></span>
                <div>
                    <h3 class="font-semibold text-gray-800 dark:text-white/90">No encontramos lotes</h3>
                    <p class="mt-1 max-w-lg text-sm text-gray-500 dark:text-gray-400">Los primeros lotes aparecerán al confirmar una recepción de mercadería.</p>
                </div>
                @if ($search !== '' || $status !== 'all')
                    <button type="button" wire:click="clearFilters" class="text-sm font-semibold text-brand-500">Limpiar filtros</button>
                @endif
            </div>
        @else
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full min-w-[1050px] text-left">
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"><th class="px-5 py-3.5">Lote</th><th class="px-5 py-3.5">Producto</th><th class="px-5 py-3.5">Origen</th><th class="px-5 py-3.5">Ingreso</th><th class="px-5 py-3.5">Vencimiento / FEFO</th><th class="px-5 py-3.5">Estado</th><th class="px-5 py-3.5 text-right">Acciones</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->lots as $lote)
                            @php
                                $estadoColor = match ($lote->estado_lote) {
                                    App\Enums\EstadoLote::Disponible => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400',
                                    App\Enums\EstadoLote::Bloqueado => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400',
                                    App\Enums\EstadoLote::Agotado => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                                };
                            @endphp
                            <tr wire:key="lot-row-{{ $lote->id }}" class="transition hover:bg-gray-50/80 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-4"><p class="font-mono text-sm font-semibold tracking-wide text-gray-800 dark:text-white/90">{{ $lote->codigo_lote }}</p><p class="mt-1 text-xs text-gray-400">{{ $lote->calidad_lote ?? 'Calidad sin registrar' }}</p></td>
                                <td class="px-5 py-4"><p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $lote->articulo->nombre_articulo }}</p><p class="mt-1 text-xs text-gray-400">{{ $lote->articulo->categoriaArticulo->nombre_categoria_articulo }} · {{ $lote->articulo->unidadMedida->abreviatura_unidad_medida }}</p></td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $lote->tipo_origen_lote->label() }}</td>
                                <td class="whitespace-nowrap px-5 py-4"><p class="text-sm text-gray-700 dark:text-gray-300">{{ $lote->fecha_ingreso_local->format('d/m/Y') }}</p><p class="mt-0.5 text-xs text-gray-400">{{ $lote->fecha_ingreso_local->format('H:i') }}</p></td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if ($lote->fecha_vencimiento_lote)
                                        <p class="text-sm font-medium {{ $lote->estaVencido() ? 'text-error-600 dark:text-error-400' : 'text-gray-700 dark:text-gray-300' }}">{{ $lote->fecha_vencimiento_lote->format('d/m/Y') }}</p>
                                        <p class="mt-0.5 text-xs {{ $lote->estaVencido() ? 'text-error-500' : 'text-gray-400' }}">{{ $lote->estaVencido() ? 'Vencido: no elegible' : 'Prioridad por vencimiento' }}</p>
                                    @else
                                        <p class="text-sm text-gray-500">Sin fecha estimada</p><p class="mt-0.5 text-xs text-gray-400">Se prioriza por ingreso</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $estadoColor }}">{{ $lote->estado_lote->label() }}</span>@if ($lote->motivo_bloqueo_lote)<p class="mt-1 max-w-48 truncate text-xs text-gray-400" title="{{ $lote->motivo_bloqueo_lote }}">{{ $lote->motivo_bloqueo_lote }}</p>@endif</td>
                                <td class="px-5 py-4"><div class="flex justify-end gap-2">@can('update', $lote)<button type="button" wire:click="openEditModal({{ $lote->id }})" class="h-9 rounded-lg border border-gray-300 px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Editar información</button>@endcan @can('changeStatus', $lote)<button type="button" wire:click="openStatusModal({{ $lote->id }})" class="h-9 rounded-lg px-3 text-xs font-semibold {{ $lote->estado_lote === App\Enums\EstadoLote::Disponible ? 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' : 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' }}">{{ $lote->estado_lote === App\Enums\EstadoLote::Disponible ? 'Bloquear' : 'Habilitar' }}</button>@endcan</div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-gray-100 lg:hidden dark:divide-gray-800">
                @foreach ($this->lots as $lote)
                    <article wire:key="lot-card-{{ $lote->id }}" class="flex flex-col gap-4 p-5">
                        <div class="flex items-start justify-between gap-3"><div><h3 class="font-mono font-semibold tracking-wide text-gray-800 dark:text-white/90">{{ $lote->codigo_lote }}</h3><p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-300">{{ $lote->articulo->nombre_articulo }}</p></div><span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ match ($lote->estado_lote) { App\Enums\EstadoLote::Disponible => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400', App\Enums\EstadoLote::Bloqueado => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400', App\Enums\EstadoLote::Agotado => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' } }}">{{ $lote->estado_lote->label() }}</span></div>
                        <dl class="grid grid-cols-2 gap-3 rounded-xl bg-gray-50 p-3 text-xs dark:bg-gray-900/60"><div><dt class="text-gray-500">Origen</dt><dd class="mt-1 font-medium text-gray-700 dark:text-gray-300">{{ $lote->tipo_origen_lote->label() }}</dd></div><div><dt class="text-gray-500">Unidad base</dt><dd class="mt-1 font-medium text-gray-700 dark:text-gray-300">{{ $lote->articulo->unidadMedida->abreviatura_unidad_medida }}</dd></div><div><dt class="text-gray-500">Ingreso</dt><dd class="mt-1 font-medium text-gray-700 dark:text-gray-300">{{ $lote->fecha_ingreso_local->format('d/m/Y H:i') }}</dd></div><div><dt class="text-gray-500">Vencimiento</dt><dd class="mt-1 font-medium {{ $lote->estaVencido() ? 'text-error-600' : 'text-gray-700 dark:text-gray-300' }}">{{ $lote->fecha_vencimiento_lote?->format('d/m/Y') ?? 'Sin estimar' }}</dd></div></dl>
                        @if ($lote->calidad_lote || $lote->observaciones_lote)<p class="text-sm text-gray-500">{{ $lote->calidad_lote }}{{ $lote->calidad_lote && $lote->observaciones_lote ? ' · ' : '' }}{{ $lote->observaciones_lote }}</p>@endif
                        <div class="grid grid-cols-2 gap-2">@can('update', $lote)<button type="button" wire:click="openEditModal({{ $lote->id }})" class="h-10 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Editar</button>@endcan @can('changeStatus', $lote)<button type="button" wire:click="openStatusModal({{ $lote->id }})" class="h-10 rounded-lg text-sm font-semibold {{ $lote->estado_lote === App\Enums\EstadoLote::Disponible ? 'bg-error-50 text-error-700' : 'bg-success-50 text-success-700' }}">{{ $lote->estado_lote === App\Enums\EstadoLote::Disponible ? 'Bloquear' : 'Habilitar' }}</button>@endcan</div>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $this->lots->links() }}</div>
        @endif
    </section>

    <div wire:show="showEditModal" x-cloak class="fixed inset-0 z-99999 flex items-end justify-center overflow-y-auto sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="lot-edit-title">
        <div class="fixed inset-0 bg-gray-950/55 backdrop-blur-sm" aria-hidden="true"></div>
        <div class="relative max-h-[95vh] w-full overflow-y-auto rounded-t-3xl bg-white shadow-theme-xl sm:max-w-xl sm:rounded-3xl dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-5 sm:px-6 dark:border-gray-800"><div><h2 id="lot-edit-title" class="text-lg font-semibold text-gray-900 dark:text-white">Información del lote</h2><p class="mt-1 text-sm text-gray-500">El código, producto, origen e ingreso permanecen inmutables.</p></div><button type="button" wire:click="closeEditModal" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800" aria-label="Cerrar">×</button></div>
            <form wire:submit="save" class="flex flex-col gap-5 p-5 sm:p-6">
                <label class="flex flex-col gap-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Fecha estimada de vencimiento <span class="font-normal text-gray-400">(opcional)</span></span><input type="date" wire:model="form.fecha_vencimiento_lote" class="h-11 rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white" />@error('form.fecha_vencimiento_lote')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>
                <label class="flex flex-col gap-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Calidad o condición <span class="font-normal text-gray-400">(opcional)</span></span><input type="text" wire:model.blur="form.calidad_lote" maxlength="100" placeholder="Ej. Buena, madura, requiere revisión" class="h-11 rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white" />@error('form.calidad_lote')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>
                <label class="flex flex-col gap-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones <span class="font-normal text-gray-400">(opcional)</span></span><textarea wire:model.blur="form.observaciones_lote" rows="3" maxlength="1000" placeholder="Madurez, daños u otra referencia" class="resize-y rounded-xl border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 outline-none focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white"></textarea>@error('form.observaciones_lote')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>
                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end dark:border-gray-800"><button type="button" wire:click="closeEditModal" class="h-11 rounded-xl border border-gray-300 px-5 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Cancelar</button><button type="submit" wire:loading.attr="disabled" wire:target="save" class="h-11 rounded-xl bg-brand-500 px-5 text-sm font-semibold text-white disabled:opacity-60"><span wire:loading.remove wire:target="save">Guardar cambios</span><span wire:loading wire:target="save">Guardando...</span></button></div>
            </form>
        </div>
    </div>

    <div wire:show="showStatusModal" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-5" role="alertdialog" aria-modal="true" aria-labelledby="lot-status-title">
        <div class="fixed inset-0 bg-gray-950/55 backdrop-blur-sm" aria-hidden="true"></div>
        <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-theme-xl dark:bg-gray-900">
            <div class="text-center"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl {{ $statusLotState === App\Enums\EstadoLote::Disponible->value ? 'bg-error-50 text-error-600' : 'bg-success-50 text-success-600' }}"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 4.4 2.7 18a2 2 0 0 0 1.75 3h15.1a2 2 0 0 0 1.75-3L13.7 4.4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg></span><h2 id="lot-status-title" class="mt-5 text-lg font-semibold text-gray-900 dark:text-white">{{ $statusLotState === App\Enums\EstadoLote::Disponible->value ? '¿Bloquear este lote?' : '¿Habilitar este lote?' }}</h2><p class="mt-2 text-sm leading-6 text-gray-500"><strong>{{ $statusLotCode }}</strong> · {{ $statusLotArticle }}</p></div>
            @if ($statusLotState === App\Enums\EstadoLote::Disponible->value)
                <label class="mt-5 flex flex-col gap-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Motivo del bloqueo <span class="text-error-500">*</span></span><textarea wire:model="motivoBloqueo" rows="3" maxlength="500" placeholder="Ej. Producto separado por revisión de calidad" class="resize-none rounded-xl border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 outline-none focus:border-error-400 focus:ring-3 focus:ring-error-500/10 dark:border-gray-700 dark:text-white"></textarea>@error('motivoBloqueo')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>
            @else
                <p class="mt-5 rounded-xl bg-success-50 px-4 py-3 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">El lote volverá a estar disponible para preparación y para la selección FEFO.</p>
            @endif
            @error('estado')<p class="mt-4 text-sm text-error-600">{{ $message }}</p>@enderror
            <div class="mt-6 grid grid-cols-2 gap-3"><button type="button" wire:click="closeStatusModal" class="h-11 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Cancelar</button><button type="button" wire:click="changeStatus" wire:loading.attr="disabled" wire:target="changeStatus" class="h-11 rounded-xl text-sm font-semibold text-white disabled:opacity-60 {{ $statusLotState === App\Enums\EstadoLote::Disponible->value ? 'bg-error-500' : 'bg-success-500' }}"><span wire:loading.remove wire:target="changeStatus">{{ $statusLotState === App\Enums\EstadoLote::Disponible->value ? 'Sí, bloquear' : 'Sí, habilitar' }}</span><span wire:loading wire:target="changeStatus">Procesando...</span></button></div>
        </div>
    </div>
</div>
