<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-medium text-brand-500 dark:text-brand-400">Operaciones</p>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Pedidos</h1>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">Registra exactamente lo solicitado por cada sucursal. La preparación y el despacho se gestionarán en sus etapas correspondientes.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
            @can(App\Enums\PermissionName::OrdersPrepare->value)
                <a href="{{ route('panel.pedidos.preparation') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-brand-200 px-4 py-2.5 text-sm font-semibold text-brand-600 transition hover:bg-brand-50 dark:border-brand-500/30 dark:text-brand-400 dark:hover:bg-brand-500/10">Preparar pedidos</a>
            @endcan
            @can('create', App\Models\Pedido::class)
                <a href="{{ route('panel.pedidos.create') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                    Nuevo pedido
                </a>
            @endcan
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-4 border-b border-gray-200 p-4 dark:border-gray-800 2xl:grid-cols-[auto_minmax(20rem,1fr)_auto] 2xl:items-center 2xl:justify-between">
            <div class="grid grid-cols-2 rounded-xl bg-gray-100 p-1 sm:grid-cols-4 dark:bg-gray-900" role="group" aria-label="Filtrar pedidos por estado">
                <button type="button" wire:click="$set('status', 'all')" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $status === 'all' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">Todos <span class="rounded-full bg-brand-50 px-1.5 py-0.5 text-xs text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ $this->statusCounts['all'] }}</span></button>
                @foreach ($this->statuses() as $estado)
                    <button type="button" wire:click="$set('status', '{{ $estado->value }}')" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $status === $estado->value ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">{{ $estado->label() }}<span class="rounded-full bg-white px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $this->statusCounts[$estado->value] }}</span></button>
                @endforeach
            </div>

            <div class="grid gap-3 md:grid-cols-[minmax(16rem,1fr)_auto]">
                <label class="relative block w-full">
                    <span class="sr-only">Buscar pedidos</span>
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <input type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar pedido, cliente o sucursal..." class="h-11 w-full rounded-xl border border-gray-300 bg-transparent py-2.5 pl-11 pr-4 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-brand-500">
                </label>
                <input type="date" wire:model.live="requiredDate" aria-label="Filtrar por fecha requerida" class="h-11 rounded-xl border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
            </div>

            <div class="flex items-center justify-between gap-4 2xl:justify-end">
                @if ($search !== '' || $status !== 'all' || $requiredDate !== '')
                    <button type="button" wire:click="clearFilters" class="text-sm font-semibold text-brand-500 transition hover:text-brand-600">Limpiar filtros</button>
                @endif
                <div class="flex items-center justify-end gap-2 text-xs font-medium text-gray-500 dark:text-gray-400"><span class="h-2 w-2 rounded-full bg-brand-500"></span>{{ $this->orders->total() }} {{ $this->orders->total() === 1 ? 'resultado' : 'resultados' }}</div>
            </div>
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-white/[0.02] dark:text-gray-400"><tr><th class="px-5 py-3">Pedido</th><th class="px-5 py-3">Sucursal</th><th class="px-5 py-3">Requerido</th><th class="px-5 py-3">Productos</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3 text-right">Acciones</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($this->orders as $pedido)
                        <tr wire:key="pedido-{{ $pedido->id }}" class="text-gray-700 dark:text-gray-300">
                            <td class="px-5 py-4"><p class="font-semibold text-gray-900 dark:text-white">{{ $pedido->codigo_pedido }}</p><p class="mt-0.5 text-xs text-gray-500">{{ $pedido->fecha_pedido_local->format('d/m/Y H:i') }}</p></td>
                            <td class="px-5 py-4"><p class="font-medium">{{ $pedido->sucursal->nombre_sucursal }}</p><p class="text-xs text-gray-500">{{ $pedido->sucursal->cliente->razon_social }}</p></td>
                            <td class="px-5 py-4">{{ $pedido->fecha_requerida_pedido->format('d/m/Y') }}</td>
                            <td class="px-5 py-4">{{ $pedido->detalles_count }}</td>
                            <td class="px-5 py-4"><span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' => $pedido->estado_pedido === App\Enums\EstadoPedido::Pendiente, 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' => $pedido->estado_pedido === App\Enums\EstadoPedido::Preparado, 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' => $pedido->estado_pedido === App\Enums\EstadoPedido::Cancelado])>{{ $pedido->estado_pedido->label() }}</span></td>
                            <td class="px-5 py-4"><div class="flex justify-end gap-2"><button type="button" wire:click="openDetailModal({{ $pedido->id }})" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-white/[0.05]">Ver</button>@can('update', $pedido)<a href="{{ route('panel.pedidos.edit', $pedido) }}" wire:navigate class="rounded-lg border border-brand-200 px-3 py-2 text-xs font-semibold text-brand-600 hover:bg-brand-50 dark:border-brand-500/30 dark:text-brand-400">Editar</a>@endcan @can('cancel', $pedido)<button type="button" wire:click="openCancelModal({{ $pedido->id }})" class="rounded-lg border border-error-200 px-3 py-2 text-xs font-semibold text-error-600 hover:bg-error-50 dark:border-error-500/30 dark:text-error-400">Cancelar</button>@endcan</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-14 text-center text-sm text-gray-500">No hay pedidos que coincidan con los filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-gray-100 lg:hidden dark:divide-gray-800">
            @forelse ($this->orders as $pedido)
                <article wire:key="pedido-mobile-{{ $pedido->id }}" class="space-y-3 p-4">
                    <div class="flex items-start justify-between gap-3"><div><p class="font-semibold text-gray-900 dark:text-white">{{ $pedido->codigo_pedido }}</p><p class="text-sm text-gray-500">{{ $pedido->sucursal->cliente->razon_social }} · {{ $pedido->sucursal->nombre_sucursal }}</p></div><span @class(['shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' => $pedido->estado_pedido === App\Enums\EstadoPedido::Pendiente, 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' => $pedido->estado_pedido === App\Enums\EstadoPedido::Preparado, 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' => $pedido->estado_pedido === App\Enums\EstadoPedido::Cancelado])>{{ $pedido->estado_pedido->label() }}</span></div>
                    <div class="grid grid-cols-2 gap-3 text-sm"><div><p class="text-xs text-gray-500">Fecha requerida</p><p class="font-medium text-gray-700 dark:text-gray-300">{{ $pedido->fecha_requerida_pedido->format('d/m/Y') }}</p></div><div><p class="text-xs text-gray-500">Productos</p><p class="font-medium text-gray-700 dark:text-gray-300">{{ $pedido->detalles_count }}</p></div></div>
                    <div class="flex flex-wrap gap-2"><button type="button" wire:click="openDetailModal({{ $pedido->id }})" class="min-h-10 rounded-lg border border-gray-300 px-3 text-xs font-semibold dark:border-gray-700">Ver detalle</button>@can('update', $pedido)<a href="{{ route('panel.pedidos.edit', $pedido) }}" wire:navigate class="inline-flex min-h-10 items-center rounded-lg border border-brand-200 px-3 text-xs font-semibold text-brand-600 dark:border-brand-500/30 dark:text-brand-400">Editar</a>@endcan @can('cancel', $pedido)<button type="button" wire:click="openCancelModal({{ $pedido->id }})" class="min-h-10 rounded-lg border border-error-200 px-3 text-xs font-semibold text-error-600 dark:border-error-500/30 dark:text-error-400">Cancelar</button>@endcan</div>
                </article>
            @empty
                <p class="p-10 text-center text-sm text-gray-500">No hay pedidos que coincidan con los filtros.</p>
            @endforelse
        </div>
        @if ($this->orders->hasPages())<div class="border-t border-gray-200 p-4 dark:border-gray-800">{{ $this->orders->links() }}</div>@endif
    </section>

    <div wire:show="showDetailModal" x-cloak x-on:keydown.escape.window="$wire.closeDetailModal()" class="fixed inset-0 z-99999 flex items-center justify-center p-3 sm:p-5" role="dialog" aria-modal="true" aria-labelledby="order-detail-title">
        <button type="button" wire:click="closeDetailModal" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/60 backdrop-blur-sm" aria-label="Cerrar detalle"></button>
        @if ($this->viewingOrder)
            <div class="relative max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-3xl bg-white p-5 shadow-theme-xl dark:bg-gray-900 sm:p-6">
                <div class="flex items-start justify-between"><div><p class="text-sm font-medium text-brand-500">Detalle del pedido</p><h2 id="order-detail-title" class="text-xl font-semibold text-gray-900 dark:text-white">{{ $this->viewingOrder->codigo_pedido }}</h2></div><button type="button" wire:click="closeDetailModal" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="Cerrar">✕</button></div>
                <dl class="mt-5 grid gap-4 rounded-2xl bg-gray-50 p-4 text-sm sm:grid-cols-2 dark:bg-white/[0.03]"><div><dt class="text-gray-500">Cliente y sucursal</dt><dd class="mt-1 font-medium text-gray-800 dark:text-gray-200">{{ $this->viewingOrder->sucursal->cliente->razon_social }} — {{ $this->viewingOrder->sucursal->nombre_sucursal }}</dd></div><div><dt class="text-gray-500">Fecha requerida</dt><dd class="mt-1 font-medium text-gray-800 dark:text-gray-200">{{ $this->viewingOrder->fecha_requerida_pedido->format('d/m/Y') }}</dd></div><div><dt class="text-gray-500">Registrado</dt><dd class="mt-1 font-medium text-gray-800 dark:text-gray-200">{{ $this->viewingOrder->fecha_pedido_local->format('d/m/Y H:i') }} por {{ $this->viewingOrder->registradoPor->display_name }}</dd></div><div><dt class="text-gray-500">Estado</dt><dd class="mt-1 font-medium text-gray-800 dark:text-gray-200">{{ $this->viewingOrder->estado_pedido->label() }}</dd></div></dl>
                @if ($this->viewingOrder->observaciones_pedido)<div class="mt-4 rounded-xl border border-gray-200 p-3 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-300"><span class="font-semibold">Observaciones:</span> {{ $this->viewingOrder->observaciones_pedido }}</div>@endif
                <div class="mt-5 overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800"><div class="divide-y divide-gray-100 dark:divide-gray-800">@foreach ($this->viewingOrder->detalles as $detalle)<div class="grid gap-2 p-4 sm:grid-cols-[1fr_auto] sm:items-center"><div><p class="font-semibold text-gray-800 dark:text-gray-200">{{ $detalle->articulo->nombre_articulo }}</p><p class="text-xs text-gray-500">{{ $detalle->presentacionArticulo->nombre_presentacion_articulo }}@if($detalle->observaciones_detalle_pedido) · {{ $detalle->observaciones_detalle_pedido }}@endif</p></div><p class="text-sm font-semibold text-brand-600 dark:text-brand-400">{{ rtrim(rtrim($detalle->cantidad_solicitada_detalle_pedido, '0'), '.') }} {{ $detalle->presentacionArticulo->nombre_presentacion_articulo }}</p></div>@endforeach</div></div>
                @if ($this->viewingOrder->estado_pedido === App\Enums\EstadoPedido::Cancelado)<div class="mt-4 rounded-xl bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400"><p class="font-semibold">Motivo de cancelación</p><p class="mt-1">{{ $this->viewingOrder->motivo_cancelacion_pedido }}</p></div>@endif
                <button type="button" wire:click="closeDetailModal" class="mt-5 h-11 w-full rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Cerrar</button>
            </div>
        @endif
    </div>

    <div wire:show="showCancelModal" x-cloak x-on:keydown.escape.window="$wire.closeCancelModal()" class="fixed inset-0 z-99999 flex items-center justify-center p-5" role="alertdialog" aria-modal="true" aria-labelledby="order-cancel-title">
        <button type="button" wire:click="closeCancelModal" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/60 backdrop-blur-sm" aria-label="Cerrar confirmación"></button>
        <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-theme-xl dark:bg-gray-900"><h2 id="order-cancel-title" class="text-lg font-semibold text-gray-900 dark:text-white">Cancelar {{ $cancellingOrderCode }}</h2><p class="mt-2 text-sm text-gray-500">Esta acción conserva el pedido y su historial, pero ya no permitirá editarlo.</p><label class="mt-4 block"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo <span class="text-error-500">*</span></span><textarea wire:model="cancellationReason" rows="3" maxlength="500" class="w-full rounded-xl border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 outline-none focus:border-error-500 dark:border-gray-700 dark:text-white"></textarea>@error('cancellationReason')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror @error('cancelacion')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror</label><div class="mt-5 grid grid-cols-2 gap-3"><button type="button" wire:click="closeCancelModal" class="h-11 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Volver</button><button type="button" wire:click="cancelOrder" wire:loading.attr="disabled" wire:target="cancelOrder" class="h-11 rounded-xl bg-error-500 text-sm font-semibold text-white hover:bg-error-600 disabled:opacity-70"><span wire:loading.remove wire:target="cancelOrder">Sí, cancelar</span><span wire:loading wire:target="cancelOrder">Cancelando...</span></button></div></div>
    </div>
</div>
