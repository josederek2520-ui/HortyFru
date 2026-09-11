<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-sm font-medium text-success-700 dark:text-success-400">
                {!! \App\Helpers\MenuHelper::getIconSvg('inventory-movements') !!}
                <span>Inventario</span>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Movimientos de inventario</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Historial de entradas y salidas por almacén, producto y lote.</p>
        </div>
        <div class="rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-800 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-300">
            <p class="font-semibold">Registro automático</p>
            <p class="mt-0.5 text-xs">Se generará desde recepciones, pedidos, producción, mermas y ajustes autorizados.</p>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto border-b border-gray-200 dark:border-gray-800">
            <div class="flex min-w-[82rem] items-end gap-3 p-4 sm:p-5">
                <label class="relative block w-64 shrink-0">
                    <span class="sr-only">Buscar movimientos</span>
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg>
                    <input wire:model.live.debounce.350ms="search" type="search" placeholder="Código, producto o lote..." class="h-11 w-full rounded-xl border border-gray-300 bg-transparent pl-11 pr-4 text-sm text-gray-800 outline-none transition focus:border-success-500 focus:ring-2 focus:ring-success-500/10 dark:border-gray-700 dark:text-white dark:placeholder:text-gray-500" />
                </label>
                <select wire:model.live="type" aria-label="Filtrar por tipo" class="h-11 w-44 shrink-0 rounded-xl border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-none focus:border-success-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    <option value="all">Todos los tipos</option>
                    @foreach ($this->movementTypes as $movementType)
                        <option value="{{ $movementType->value }}">{{ $movementType->label() }}</option>
                    @endforeach
                </select>
                <select wire:model.live="warehouse" aria-label="Filtrar por almacén" class="h-11 w-48 shrink-0 rounded-xl border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-none focus:border-success-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    <option value="all">Todos los almacenes</option>
                    @foreach ($this->warehouses as $warehouseOption)
                        <option value="{{ $warehouseOption->id }}">{{ $warehouseOption->nombre_almacen }}</option>
                    @endforeach
                </select>
                <select wire:model.live="status" aria-label="Filtrar por estado" class="h-11 w-44 shrink-0 rounded-xl border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-none focus:border-success-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    <option value="all">Todos los estados</option>
                    @foreach ($this->movementStatuses as $movementStatus)
                        <option value="{{ $movementStatus->value }}">{{ $movementStatus->label() }}</option>
                    @endforeach
                </select>

                <div class="w-36 shrink-0">
                    <x-panel.form.date-picker
                        id="movement-date-from-filter"
                        model="dateFrom"
                        :default-date="$dateFrom ?: null"
                        label="Desde"
                        placeholder="dd/mm/aaaa"
                        aria-label="Filtrar movimientos desde la fecha"
                        :disable-mobile="true"
                        compact
                        wire:key="movement-date-from-filter-{{ $dateFrom ?: 'empty' }}"
                    />
                </div>

                <div class="w-36 shrink-0">
                    <x-panel.form.date-picker
                        id="movement-date-to-filter"
                        model="dateTo"
                        :default-date="$dateTo ?: null"
                        label="Hasta"
                        placeholder="dd/mm/aaaa"
                        aria-label="Filtrar movimientos hasta la fecha"
                        :disable-mobile="true"
                        compact
                        wire:key="movement-date-to-filter-{{ $dateTo ?: 'empty' }}"
                    />
                </div>

                <button wire:click="clearFilters" type="button" class="h-11 w-36 shrink-0 rounded-xl border border-gray-300 px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Limpiar filtros</button>
            </div>
        </div>

        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 text-sm dark:border-gray-800 sm:px-5">
            <p class="text-gray-500 dark:text-gray-400"><span class="font-semibold text-gray-800 dark:text-gray-200">{{ $this->movements->total() }}</span> {{ $this->movements->total() === 1 ? 'resultado' : 'resultados' }}</p>
            <p class="text-xs text-gray-400">Total histórico: {{ $this->totalMovements }}</p>
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full text-left">
                <thead class="bg-gray-50/70 text-xs uppercase text-gray-500 dark:bg-white/[0.02] dark:text-gray-400"><tr><th class="px-5 py-3 font-semibold">Movimiento</th><th class="px-5 py-3 font-semibold">Tipo</th><th class="px-5 py-3 font-semibold">Almacén</th><th class="px-5 py-3 font-semibold">Origen</th><th class="px-5 py-3 font-semibold">Productos</th><th class="px-5 py-3 font-semibold">Responsable</th><th class="px-5 py-3 text-right font-semibold">Acción</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($this->movements as $movement)
                        <tr wire:key="movement-row-{{ $movement->id }}" class="text-sm text-gray-700 dark:text-gray-300">
                            <td class="px-5 py-4"><p class="font-semibold text-gray-900 dark:text-white">{{ $movement->codigo_movimiento_inventario }}</p><p class="mt-0.5 text-xs text-gray-500">{{ $movement->fecha_movimiento_local->format('d/m/Y H:i') }}</p></td>
                            <td class="px-5 py-4">
                                <span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' => $movement->tipo_movimiento_inventario->esEntrada(), 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' => ! $movement->tipo_movimiento_inventario->esEntrada()])>{{ $movement->tipo_movimiento_inventario->esEntrada() ? '+' : '−' }} {{ $movement->tipo_movimiento_inventario->label() }}</span>
                                @if ($movement->estado_movimiento_inventario === \App\Enums\EstadoMovimientoInventario::Anulado)<p class="mt-1 text-xs font-medium text-gray-400">Anulado</p>@endif
                            </td>
                            <td class="px-5 py-4">{{ $movement->almacen->nombre_almacen }}</td>
                            <td class="px-5 py-4"><p>{{ $movement->tipo_referencia_movimiento_inventario?->label() ?? 'Ajuste interno' }}</p>@if ($movement->referencia_id_movimiento_inventario)<p class="text-xs text-gray-500">Registro #{{ $movement->referencia_id_movimiento_inventario }}</p>@endif</td>
                            <td class="px-5 py-4">{{ $movement->detalles_count }} {{ $movement->detalles_count === 1 ? 'línea' : 'líneas' }}</td>
                            <td class="px-5 py-4">{{ $movement->registradoPor->display_name }}</td>
                            <td class="px-5 py-4 text-right"><button wire:click="openDetailModal({{ $movement->id }})" type="button" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:border-success-300 hover:text-success-700 dark:border-gray-700 dark:text-gray-300">Ver detalle</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-14 text-center text-sm text-gray-500">Todavía no existen movimientos con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-gray-100 lg:hidden dark:divide-gray-800">
            @forelse ($this->movements as $movement)
                <article wire:key="movement-card-{{ $movement->id }}" class="space-y-3 p-4">
                    <div class="flex items-start justify-between gap-3"><div><p class="font-semibold text-gray-900 dark:text-white">{{ $movement->codigo_movimiento_inventario }}</p><p class="text-xs text-gray-500">{{ $movement->fecha_movimiento_local->format('d/m/Y H:i') }}</p></div><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' => $movement->tipo_movimiento_inventario->esEntrada(), 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' => ! $movement->tipo_movimiento_inventario->esEntrada()])>{{ $movement->tipo_movimiento_inventario->esEntrada() ? 'Entrada' : 'Salida' }}</span></div>
                    <dl class="grid grid-cols-2 gap-3 text-sm"><div><dt class="text-xs text-gray-500">Tipo</dt><dd class="mt-0.5 text-gray-800 dark:text-gray-200">{{ $movement->tipo_movimiento_inventario->label() }}</dd></div><div><dt class="text-xs text-gray-500">Almacén</dt><dd class="mt-0.5 text-gray-800 dark:text-gray-200">{{ $movement->almacen->nombre_almacen }}</dd></div><div><dt class="text-xs text-gray-500">Origen</dt><dd class="mt-0.5 text-gray-800 dark:text-gray-200">{{ $movement->tipo_referencia_movimiento_inventario?->label() ?? 'Ajuste interno' }}</dd></div><div><dt class="text-xs text-gray-500">Productos</dt><dd class="mt-0.5 text-gray-800 dark:text-gray-200">{{ $movement->detalles_count }}</dd></div></dl>
                    <button wire:click="openDetailModal({{ $movement->id }})" type="button" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Ver detalle</button>
                </article>
            @empty
                <p class="px-5 py-14 text-center text-sm text-gray-500">Todavía no existen movimientos con estos filtros.</p>
            @endforelse
        </div>
        @if ($this->movements->hasPages())<div class="border-t border-gray-200 px-4 py-4 dark:border-gray-800 sm:px-5">{{ $this->movements->links() }}</div>@endif
    </section>

    @if ($showDetailModal && $this->selectedMovement)
        @php($selectedMovement = $this->selectedMovement)
        <div class="fixed inset-0 z-[99999] flex items-center justify-center bg-gray-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="movement-detail-title">
            <button wire:click="closeDetailModal" type="button" class="absolute inset-0 cursor-default" aria-label="Cerrar detalle"></button>
            <section class="relative z-10 max-h-[90vh] w-full max-w-5xl overflow-y-auto rounded-3xl bg-white shadow-2xl dark:bg-gray-900">
                <header class="flex items-start justify-between border-b border-gray-200 px-5 py-5 dark:border-gray-800 sm:px-7"><div><h2 id="movement-detail-title" class="text-xl font-semibold text-gray-900 dark:text-white">{{ $selectedMovement->codigo_movimiento_inventario }}</h2><p class="mt-1 text-sm text-gray-500">{{ $selectedMovement->tipo_movimiento_inventario->label() }} · {{ $selectedMovement->fecha_movimiento_local->format('d/m/Y H:i') }}</p></div><button wire:click="closeDetailModal" type="button" class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300" aria-label="Cerrar">×</button></header>
                <div class="space-y-5 p-5 sm:p-7">
                    <dl class="grid grid-cols-1 gap-4 rounded-2xl bg-gray-50 p-4 sm:grid-cols-2 lg:grid-cols-4 dark:bg-white/[0.03]"><div><dt class="text-xs text-gray-500">Almacén</dt><dd class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $selectedMovement->almacen->nombre_almacen }}</dd></div><div><dt class="text-xs text-gray-500">Origen</dt><dd class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $selectedMovement->tipo_referencia_movimiento_inventario?->label() ?? 'Ajuste interno' }}{{ $selectedMovement->referencia_id_movimiento_inventario ? ' #'.$selectedMovement->referencia_id_movimiento_inventario : '' }}</dd></div><div><dt class="text-xs text-gray-500">Estado</dt><dd class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $selectedMovement->estado_movimiento_inventario->label() }}</dd></div><div><dt class="text-xs text-gray-500">Registrado por</dt><dd class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $selectedMovement->registradoPor->display_name }}</dd></div></dl>
                    <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800"><div class="overflow-x-auto"><table class="w-full min-w-[700px] text-left"><thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-white/[0.03] dark:text-gray-400"><tr><th class="px-4 py-3">Producto</th><th class="px-4 py-3">Categoría</th><th class="px-4 py-3">Lote</th><th class="px-4 py-3">Vencimiento</th><th class="px-4 py-3 text-right">Cantidad base</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($selectedMovement->detalles as $detail)
                            <tr wire:key="movement-detail-{{ $detail->id }}" class="text-sm text-gray-700 dark:text-gray-300"><td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $detail->articulo->nombre_articulo }}</td><td class="px-4 py-3">{{ $detail->articulo->categoriaArticulo->nombre_categoria_articulo }}</td><td class="px-4 py-3 font-medium">{{ $detail->lote->codigo_lote }}</td><td class="px-4 py-3">{{ $detail->lote->fecha_vencimiento_lote?->format('d/m/Y') ?? 'Sin fecha' }}</td><td @class(['px-4 py-3 text-right font-bold tabular-nums', 'text-success-700 dark:text-success-400' => $selectedMovement->tipo_movimiento_inventario->esEntrada(), 'text-error-700 dark:text-error-400' => ! $selectedMovement->tipo_movimiento_inventario->esEntrada()])>{{ $selectedMovement->tipo_movimiento_inventario->esEntrada() ? '+' : '−' }}{{ rtrim(rtrim(number_format((float) $detail->cantidad_movimiento_inventario, 3, ',', '.'), '0'), ',') }} {{ $detail->unidadMedida->abreviatura_unidad_medida }}</td></tr>
                        @endforeach
                    </tbody></table></div></div>
                    @if ($selectedMovement->observaciones_movimiento_inventario)<div><p class="text-xs font-medium text-gray-500">Observaciones</p><p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $selectedMovement->observaciones_movimiento_inventario }}</p></div>@endif
                </div>
            </section>
        </div>
    @endif
</div>
