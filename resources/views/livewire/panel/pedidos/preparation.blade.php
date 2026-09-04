<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-medium text-brand-500 dark:text-brand-400">Operaciones</p>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Preparación de pedidos</h1>
            <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">Hoja digital consolidada por fecha. Marca cada cantidad conforme se prepara físicamente para su sucursal.</p>
        </div>
        <a href="{{ route('panel.pedidos.index') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-gray-300 px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m12 5-5 5 5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
            Volver a pedidos
        </a>
    </div>

    <section aria-label="Resumen de preparación" class="grid gap-4 md:grid-cols-3">
        <article class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h12v18H6zM9 7h6M9 11h6M9 15h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg></span>
            <div><p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $this->orders->count() }}</p><p class="text-sm text-gray-500 dark:text-gray-400">Pedidos del día</p></div>
        </article>
        <article class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10M7 21h10M8 3c0 4 1.2 6 4 9-2.8 3-4 5-4 9m8-18c0 4-1.2 6-4 9 2.8 3 4 5 4 9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg></span>
            <div><p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $this->pendingDetails }}</p><p class="text-sm text-gray-500 dark:text-gray-400">Productos por preparar</p></div>
        </article>
        <article class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6" /><path d="m8 12 2.5 2.5L16 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg></span>
            <div><p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $this->preparedDetails }}</p><p class="text-sm text-gray-500 dark:text-gray-400">Productos preparados</p></div>
        </article>
    </section>

    <section aria-labelledby="preparation-sheet-title" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid gap-4 border-b border-gray-200 p-4 dark:border-gray-800 xl:grid-cols-[auto_minmax(20rem,1fr)_auto] xl:items-center">
            <div class="grid grid-cols-3 rounded-xl bg-gray-100 p-1 dark:bg-gray-900" role="group" aria-label="Filtrar productos por preparación">
                <button type="button" wire:click="$set('preparationStatus', 'all')" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $preparationStatus === 'all' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">Todos <span class="rounded-full bg-brand-50 px-1.5 py-0.5 text-xs text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ $this->totalDetails }}</span></button>
                <button type="button" wire:click="$set('preparationStatus', 'pending')" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $preparationStatus === 'pending' ? 'bg-white text-warning-600 shadow-theme-xs dark:bg-gray-800 dark:text-warning-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">Por preparar <span class="rounded-full bg-white px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $this->pendingDetails }}</span></button>
                <button type="button" wire:click="$set('preparationStatus', 'prepared')" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $preparationStatus === 'prepared' ? 'bg-white text-success-600 shadow-theme-xs dark:bg-gray-800 dark:text-success-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">Preparados <span class="rounded-full bg-white px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $this->preparedDetails }}</span></button>
            </div>

            <div class="grid gap-3 sm:grid-cols-[minmax(16rem,1fr)_auto]">
                <label class="relative block">
                    <span class="sr-only">Buscar productos, pedidos o sucursales</span>
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m14.5 14.5 3 3M16 9a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" /></svg>
                    <input type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar producto, pedido o sucursal..." class="h-11 w-full rounded-xl border border-gray-300 bg-transparent py-2.5 pl-11 pr-4 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white dark:placeholder:text-gray-500">
                </label>
                <input type="date" wire:model.live="requiredDate" aria-label="Fecha requerida" class="h-11 rounded-xl border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
            </div>

            <div class="flex items-center justify-between gap-4 xl:justify-end">
                @if ($search !== '' || $preparationStatus !== 'all')
                    <button type="button" wire:click="clearFilters" class="text-sm font-semibold text-brand-500 transition hover:text-brand-600">Limpiar filtros</button>
                @endif
                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $this->progressPercentage }}% completado</span>
            </div>
        </div>

        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div><h2 id="preparation-sheet-title" class="font-semibold text-gray-900 dark:text-white">Hoja del {{ \Illuminate\Support\Carbon::parse($requiredDate)->format('d/m/Y') }}</h2><p class="text-sm text-gray-500">Cada columna corresponde a un pedido y su sucursal, como en la plantilla física.</p></div>
                <progress value="{{ $this->preparedDetails }}" max="{{ max($this->totalDetails, 1) }}" class="h-2 w-full accent-brand-500 sm:max-w-56" aria-label="Avance general de preparación">{{ $this->progressPercentage }}%</progress>
            </div>
        </div>

        @if ($this->totalDetails === 0)
            <div class="flex flex-col items-center gap-3 px-6 py-16 text-center"><span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h12v18H6zM9 7h6M9 11h6M9 15h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg></span><div><h3 class="font-semibold text-gray-900 dark:text-white">No hay pedidos para preparar</h3><p class="mt-1 text-sm text-gray-500">Cambia la fecha o registra pedidos con esa fecha requerida.</p></div></div>
        @elseif ($this->details->isEmpty())
            <div class="flex flex-col items-center gap-3 px-6 py-16 text-center"><span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800"><svg class="h-7 w-7" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m14.5 14.5 3 3M16 9a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" /></svg></span><div><h3 class="font-semibold text-gray-900 dark:text-white">No hay productos que coincidan</h3><p class="mt-1 text-sm text-gray-500">Prueba con otro estado o término de búsqueda.</p></div><button type="button" wire:click="clearFilters" class="text-sm font-semibold text-brand-500 hover:text-brand-600">Limpiar filtros</button></div>
        @else
            <div class="hidden overflow-x-auto lg:block">
                <table class="min-w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 dark:bg-white/[0.02] dark:text-gray-300">
                            <th class="sticky left-0 z-10 min-w-72 border-b border-r border-gray-200 bg-gray-50 px-4 py-3 text-left font-semibold dark:border-gray-800 dark:bg-gray-900">Producto / presentación</th>
                            @foreach ($this->preparationOrders as $orderGroup)
                                <th class="min-w-40 border-b border-r border-gray-200 px-3 py-3 text-center font-semibold dark:border-gray-800"><span class="block">{{ $orderGroup['pedido']->sucursal->nombre_sucursal }}</span><span class="mt-0.5 block text-xs font-normal text-gray-400">{{ $orderGroup['pedido']->codigo_pedido }}</span><span class="mt-1 block text-xs font-medium text-brand-500">{{ $orderGroup['preparados'] }}/{{ $orderGroup['total'] }} preparados</span></th>
                            @endforeach
                            <th class="min-w-28 border-b border-gray-200 px-3 py-3 text-center font-semibold dark:border-gray-800">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->preparationRows as $row)
                            <tr wire:key="preparation-row-{{ $row['key'] }}" class="transition hover:bg-gray-50/60 dark:hover:bg-white/[0.02]">
                                <th class="sticky left-0 z-10 border-r border-gray-200 bg-white px-4 py-3 text-left dark:border-gray-800 dark:bg-gray-900">
                                    <span class="flex items-center gap-3">
                                        @if ($row['articulo']->imagen_articulo)
                                            <img src="{{ $row['articulo']->imagen_url }}" alt="{{ $row['articulo']->nombre_articulo }}" loading="lazy" class="h-12 w-12 shrink-0 rounded-xl border border-gray-200 object-cover dark:border-gray-700">
                                        @else
                                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg></span>
                                        @endif
                                        <span class="min-w-0"><span class="block font-semibold text-gray-800 dark:text-gray-200">{{ $row['articulo']->nombre_articulo }}</span><span class="block text-xs font-normal text-gray-500">{{ $row['presentacion']->nombre_presentacion_articulo }} · {{ $row['articulo']->unidadMedida->abreviatura_unidad_medida }}</span></span>
                                    </span>
                                </th>
                                @foreach ($this->preparationOrders as $orderGroup)
                                    @php($detail = $row['detalles']->get($orderGroup['pedido']->id))
                                    <td class="border-r border-gray-100 px-3 py-2 text-center dark:border-gray-800">
                                        @if ($detail)
                                            <button type="button" wire:click="setPrepared({{ $detail->id }}, {{ $detail->preparado_detalle_pedido ? 'false' : 'true' }})" wire:loading.attr="disabled" wire:target="setPrepared({{ $detail->id }}, {{ $detail->preparado_detalle_pedido ? 'false' : 'true' }})" @class(['mx-auto flex min-h-14 min-w-28 flex-col items-center justify-center rounded-xl border px-3 py-2 font-semibold transition disabled:opacity-60', 'border-success-300 bg-success-50 text-success-700 dark:border-success-500/40 dark:bg-success-500/10 dark:text-success-400' => $detail->preparado_detalle_pedido, 'border-gray-300 text-gray-700 hover:border-brand-300 hover:bg-brand-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-brand-500/10' => ! $detail->preparado_detalle_pedido])>
                                                <span class="flex items-center gap-2"><span @class(['flex h-5 w-5 items-center justify-center rounded border', 'border-success-500 bg-success-500 text-white' => $detail->preparado_detalle_pedido, 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-900' => ! $detail->preparado_detalle_pedido])>@if ($detail->preparado_detalle_pedido) ✓ @endif</span>{{ rtrim(rtrim($detail->cantidad_solicitada_detalle_pedido, '0'), '.') }}</span>
                                                @if ($detail->observaciones_detalle_pedido)<span class="mt-1 max-w-28 truncate text-[11px] font-normal text-gray-500" title="{{ $detail->observaciones_detalle_pedido }}">{{ $detail->observaciones_detalle_pedido }}</span>@endif
                                            </button>
                                        @else
                                            <span class="text-gray-300 dark:text-gray-700">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-3 text-center font-bold text-brand-600 dark:text-brand-400">{{ rtrim(rtrim(number_format($row['cantidad_total'], 3, '.', ''), '0'), '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-4 bg-gray-50/60 p-4 lg:hidden dark:bg-white/[0.01]">
                @foreach ($this->preparationOrders as $orderGroup)
                    <article wire:key="preparation-order-mobile-{{ $orderGroup['pedido']->id }}" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                        <header class="flex items-start justify-between gap-3 border-b border-gray-100 p-4 dark:border-gray-800">
                            <div><h3 class="font-semibold text-gray-900 dark:text-white">{{ $orderGroup['pedido']->sucursal->nombre_sucursal }}</h3><p class="text-xs text-gray-500">{{ $orderGroup['pedido']->codigo_pedido }} · {{ $orderGroup['pedido']->sucursal->cliente->razon_social }}</p></div>
                            <span @class(['shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' => $orderGroup['preparados'] === $orderGroup['total'], 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' => $orderGroup['preparados'] !== $orderGroup['total']])>{{ $orderGroup['preparados'] }}/{{ $orderGroup['total'] }}</span>
                        </header>
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($orderGroup['detalles'] as $detail)
                                <button type="button" wire:key="preparation-detail-mobile-{{ $detail->id }}" wire:click="setPrepared({{ $detail->id }}, {{ $detail->preparado_detalle_pedido ? 'false' : 'true' }})" wire:loading.attr="disabled" wire:target="setPrepared({{ $detail->id }}, {{ $detail->preparado_detalle_pedido ? 'false' : 'true' }})" @class(['flex w-full items-center gap-3 p-4 text-left transition disabled:opacity-60', 'bg-success-50/70 dark:bg-success-500/5' => $detail->preparado_detalle_pedido, 'hover:bg-gray-50 dark:hover:bg-white/[0.03]' => ! $detail->preparado_detalle_pedido])>
                                    @if ($detail->articulo->imagen_articulo)
                                        <img src="{{ $detail->articulo->imagen_url }}" alt="{{ $detail->articulo->nombre_articulo }}" loading="lazy" class="h-14 w-14 shrink-0 rounded-xl border border-gray-200 object-cover dark:border-gray-700">
                                    @else
                                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg></span>
                                    @endif
                                    <span class="min-w-0 flex-1"><span class="block truncate font-semibold text-gray-800 dark:text-gray-200">{{ $detail->articulo->nombre_articulo }}</span><span class="block text-sm text-gray-500">{{ rtrim(rtrim($detail->cantidad_solicitada_detalle_pedido, '0'), '.') }} {{ $detail->presentacionArticulo->nombre_presentacion_articulo }}</span>@if ($detail->observaciones_detalle_pedido)<span class="mt-0.5 block truncate text-xs text-warning-600 dark:text-warning-400">{{ $detail->observaciones_detalle_pedido }}</span>@endif</span>
                                    <span @class(['flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border text-sm', 'border-success-500 bg-success-500 text-white' => $detail->preparado_detalle_pedido, 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-900' => ! $detail->preparado_detalle_pedido])>@if ($detail->preparado_detalle_pedido) ✓ @endif</span>
                                </button>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
