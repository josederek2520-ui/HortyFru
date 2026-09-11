<div class="space-y-6">
    @php
        $compra = $this->purchase;
        $articleOptions = $this->articleOptions->map(fn ($articulo) => [
            'id' => $articulo->id,
            'name' => $articulo->nombre_articulo,
            'categoryId' => $articulo->categoria_articulo_id,
            'category' => $articulo->categoriaArticulo->nombre_categoria_articulo,
        ])->values();
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('panel.compras.index') }}" wire:navigate class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-brand-600 dark:text-gray-400">← Volver a compras</a>
            <h1 class="mt-3 text-2xl font-semibold text-gray-900 dark:text-white">Productos de {{ $compra->codigo_compra }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Registra lo comprado y su cantidad real. Este paso todavía no ingresa productos al inventario.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('update', $compra)
                <button type="button" wire:click="openCreateModal" class="h-11 rounded-xl border border-brand-300 px-4 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 dark:border-brand-700 dark:text-brand-400">+ Agregar producto</button>
            @endcan
            @can('register', $compra)
                <button type="button" wire:click="$set('showRegisterModal', true)" class="h-11 rounded-xl bg-brand-500 px-5 text-sm font-semibold text-white transition hover:bg-brand-600">Registrar compra</button>
            @endcan
        </div>
    </div>

    <section class="grid gap-4 rounded-2xl border border-gray-200 bg-white p-5 sm:grid-cols-2 xl:grid-cols-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div><span class="text-xs font-medium uppercase tracking-wide text-gray-400">Proveedor</span><p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">{{ $compra->proveedor->nombre_proveedor }}</p></div>
        <div><span class="text-xs font-medium uppercase tracking-wide text-gray-400">Empleado comprador</span><p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">{{ $compra->empleado->nombre_empleado }} {{ $compra->empleado->apellido_empleado }}</p></div>
        <div><span class="text-xs font-medium uppercase tracking-wide text-gray-400">Fecha</span><p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">{{ $compra->fecha_compra_local?->format('d/m/Y H:i') }}</p></div>
        <div><span class="text-xs font-medium uppercase tracking-wide text-gray-400">Estado</span><p class="mt-1 text-sm font-semibold {{ $compra->estaEnBorrador() ? 'text-warning-600' : ($compra->estado_compra->value === 'REGISTRADA' ? 'text-success-600' : 'text-error-600') }}">{{ $compra->estado_compra->label() }}</p></div>
        <div><span class="text-xs font-medium uppercase tracking-wide text-gray-400">Total</span><p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Bs {{ number_format((float) $compra->total_compra, 2, ',', '.') }}</p></div>
    </section>

    <section aria-labelledby="purchase-needs-title" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <header class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div>
                <h2 id="purchase-needs-title" class="font-semibold text-gray-900 dark:text-white">Necesidad de compra del {{ $compra->fecha_compra_local->format('d/m/Y') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Consolidado de los pedidos activos por sucursal. Úsalo como referencia y registra únicamente lo que realmente compres a este proveedor.</p>
            </div>
            <span class="inline-flex shrink-0 self-start rounded-full bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 sm:self-auto dark:bg-brand-500/10 dark:text-brand-400">
                {{ $this->purchaseNeeds->count() }} {{ $this->purchaseNeeds->count() === 1 ? 'línea solicitada' : 'líneas solicitadas' }}
            </span>
        </header>

        @if ($this->purchaseNeeds->isEmpty())
            <div class="px-6 py-10 text-center">
                <p class="font-semibold text-gray-800 dark:text-white">No hay pedidos activos para esta fecha</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Puedes usar “Agregar producto” para registrar una compra libre o imprevista.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-white/[0.02]">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Categoría</th>
                            <th class="px-5 py-3 font-semibold">Producto solicitado</th>
                            <th class="px-5 py-3 font-semibold">Pedidos por sucursal</th>
                            <th class="px-5 py-3 text-right font-semibold">Total solicitado</th>
                            <th class="px-5 py-3 font-semibold">Compra habitual</th>
                            <th class="px-5 py-3 text-right font-semibold">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->purchaseNeeds as $need)
                            <tr wire:key="purchase-need-{{ $need['key'] }}" class="text-sm text-gray-700 transition hover:bg-gray-50/70 dark:text-gray-300 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $need['articulo']->categoriaArticulo->nombre_categoria_articulo }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        @if ($need['articulo']->imagen_articulo)
                                            <img src="{{ $need['articulo']->imagen_url }}" alt="{{ $need['articulo']->nombre_articulo }}" loading="lazy" class="h-10 w-10 shrink-0 rounded-xl border border-gray-200 object-cover dark:border-gray-700">
                                        @endif
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $need['articulo']->nombre_articulo }}</p>
                                            <p class="mt-0.5 text-xs text-gray-400">Pedido en {{ $need['presentacion_pedido']->nombre_presentacion_articulo }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($need['sucursales'] as $branch)
                                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-900" title="{{ $branch['pedidos']->implode(', ') }}">
                                                <span class="text-gray-500 dark:text-gray-400">{{ $branch['nombre'] }}</span>
                                                <strong class="text-gray-800 dark:text-white">{{ rtrim(rtrim(number_format($branch['cantidad'], 3, ',', '.'), '0'), ',') }}</strong>
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ rtrim(rtrim(number_format($need['cantidad_total'], 3, ',', '.'), '0'), ',') }}</span>
                                    <span class="ml-1 text-xs text-gray-500">{{ $need['presentacion_pedido']->nombre_presentacion_articulo }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    @if ($need['presentacion_compra'])
                                        <p class="font-medium text-gray-800 dark:text-white">{{ $need['presentacion_compra']->nombre_presentacion_articulo }}</p>
                                        @if ($need['presentacion_compra']->tipo_equivalencia_presentacion_articulo === App\Enums\TipoEquivalenciaPresentacionArticulo::Variable)
                                            <p class="mt-0.5 text-xs text-warning-600 dark:text-warning-400">Cantidad a definir al comprar</p>
                                        @endif
                                    @else
                                        <p class="font-medium text-gray-800 dark:text-white">Unidad base</p>
                                        <p class="mt-0.5 text-xs text-gray-400">{{ $need['articulo']->unidadMedida->nombre_unidad_medida }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @can('update', $compra)
                                        @if (! $need['articulo']->estado_articulo)
                                            <span class="text-xs font-semibold text-error-500">Producto inactivo</span>
                                        @elseif ($need['ya_agregado'])
                                            <span class="inline-flex h-9 items-center rounded-lg bg-success-50 px-3 text-xs font-semibold text-success-700 dark:bg-success-500/10 dark:text-success-400">Ya agregado</span>
                                        @else
                                            <button type="button" wire:click="openCreateModalFromNeed({{ $need['articulo']->id }})" class="h-9 rounded-lg border border-brand-300 px-3 text-xs font-semibold text-brand-700 transition hover:bg-brand-50 dark:border-brand-700 dark:text-brand-400 dark:hover:bg-brand-500/10">Agregar a compra</button>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400">Solo lectura</span>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @error('registro')
        <p class="rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400">{{ $message }}</p>
    @enderror

    <section aria-labelledby="purchased-products-title" class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <header class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h2 id="purchased-products-title" class="font-semibold text-gray-900 dark:text-white">Productos comprados a {{ $compra->proveedor->nombre_proveedor }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Aquí se registra lo adquirido realmente, sus cantidades y el total pagado.</p>
        </header>
        @if ($compra->detalles->isEmpty())
            <div class="px-6 py-14 text-center"><p class="text-base font-semibold text-gray-800 dark:text-white">Esta compra todavía no tiene productos</p><p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Agrega el primer producto para calcular el total.</p></div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[950px] text-left">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-white/[0.02]"><tr><th class="px-5 py-3">Producto</th><th class="px-5 py-3">Presentación</th><th class="px-5 py-3 text-right">N.º present.</th><th class="px-5 py-3 text-right">Cantidad real</th><th class="px-5 py-3 text-right">Total pagado</th><th class="px-5 py-3 text-right">Acciones</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($compra->detalles as $detalle)
                            <tr wire:key="purchase-detail-{{ $detalle->id }}" class="text-sm text-gray-700 dark:text-gray-300">
                                <td class="px-5 py-4"><p class="font-semibold text-gray-900 dark:text-white">{{ $detalle->articulo->nombre_articulo }}</p>@if ($detalle->observaciones_detalle_compra)<p class="mt-1 max-w-xs text-xs text-gray-500">{{ $detalle->observaciones_detalle_compra }}</p>@endif</td>
                                <td class="px-5 py-4">{{ $detalle->presentacionArticulo?->nombre_presentacion_articulo ?? 'Compra directa' }}</td>
                                <td class="px-5 py-4 text-right">{{ $detalle->cantidad_presentaciones_detalle_compra === null ? '—' : number_format((float) $detalle->cantidad_presentaciones_detalle_compra, 3, ',', '.') }}</td>
                                <td class="px-5 py-4 text-right">{{ $detalle->cantidad_real_detalle_compra === null ? 'No registrada' : number_format((float) $detalle->cantidad_real_detalle_compra, 3, ',', '.').' '.$detalle->unidadMedida?->abreviatura_unidad_medida }}</td>
                                <td class="px-5 py-4 text-right font-semibold text-gray-900 dark:text-white">Bs {{ number_format((float) $detalle->subtotal_detalle_compra, 2, ',', '.') }}</td>
                                <td class="px-5 py-4 text-right">@can('update', $compra)<div class="flex justify-end gap-2"><button type="button" wire:click="openEditModal({{ $detalle->id }})" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold hover:bg-gray-50 dark:border-gray-700">Editar</button><button type="button" wire:click="openDeleteModal({{ $detalle->id }})" class="rounded-lg bg-error-50 px-3 py-2 text-xs font-semibold text-error-700 dark:bg-error-500/10 dark:text-error-400">Quitar</button></div>@else<span class="text-xs text-gray-400">Solo lectura</span>@endcan</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div wire:show="showFormModal" x-cloak x-on:keydown.escape.window="$wire.closeFormModal()" class="fixed inset-0 z-99999 flex items-end justify-center overflow-y-auto sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="detail-form-title">
        <button type="button" wire:click="closeFormModal" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/55 backdrop-blur-sm" aria-label="Cerrar formulario"></button>
        <div class="relative max-h-[95vh] w-full overflow-y-auto rounded-t-3xl bg-white shadow-theme-xl sm:max-w-7xl sm:rounded-3xl dark:bg-gray-900">
            <div class="sticky top-0 z-20 flex items-start justify-between border-b border-gray-200 bg-white px-5 py-5 dark:border-gray-800 dark:bg-gray-900"><div><h2 id="detail-form-title" class="text-lg font-semibold text-gray-900 dark:text-white">{{ $editingDetailId === null ? 'Agregar producto' : 'Editar producto' }}</h2><p class="mt-1 text-sm text-gray-500">Completa la línea de compra en una sola fila.</p></div><button type="button" wire:click="closeFormModal" class="h-10 w-10 rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800">×</button></div>
            <form wire:submit="save" class="space-y-5 p-5 sm:p-6">
                @error('form.detalle')<p class="rounded-xl bg-error-50 px-4 py-3 text-sm text-error-700">{{ $message }}</p>@enderror
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12 xl:items-end">
                    <div x-data="purchaseArticleSelect(@js($articleOptions))" x-on:purchase-detail-form-opened.window="$nextTick(() => category = selected ? String(selected.categoryId) : '')" @keydown.escape.stop.prevent="close(true)" @focusout="if (!$el.contains($event.relatedTarget)) close()" class="grid gap-3 sm:grid-cols-2 xl:col-span-5">
                        <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Categoría</span><select x-model="category" @change="changeCategory()" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"><option value="">Todas las categorías</option><template x-for="item in categories" :key="item.id"><option :value="item.id" x-text="item.name"></option></template></select></label>
                        <div class="relative" @click.outside="close()"><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Producto <span class="text-error-500">*</span></label><button x-ref="trigger" type="button" @click="open ? close() : show()" :disabled="busy" class="flex h-11 w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-3 text-left text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"><span x-text="selected?.name || 'Busca o selecciona'"></span><span>⌄</span></button><div x-show="open" x-cloak class="absolute z-30 mt-1 w-full rounded-xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-700 dark:bg-gray-900"><input x-ref="search" x-model="query" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="if (filteredOptions[activeIndex]) choose(filteredOptions[activeIndex])" type="text" placeholder="Escribe el producto…" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"><div x-ref="results" class="mt-2 max-h-56 overflow-y-auto"><template x-for="(item, position) in filteredOptions" :key="item.id"><button type="button" @mousedown.prevent @click="choose(item)" @mouseenter="activeIndex = position" :class="activeIndex === position ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/15' : ''" class="block w-full rounded-lg px-3 py-2 text-left text-sm"><span class="block" x-text="item.name"></span><span class="text-xs text-gray-400" x-text="item.category"></span></button></template><p x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-500">Sin resultados.</p></div></div>@error('form.articulo_id')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror</div>
                    </div>
                    <label class="block xl:col-span-2"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Presentación</span><select wire:model.live="form.presentacion_articulo_id" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"><option value="">Directa en unidad base</option>@foreach ($this->presentationOptions as $presentacion)<option value="{{ $presentacion->id }}">{{ $presentacion->nombre_presentacion_articulo }}</option>@endforeach</select>@error('form.presentacion_articulo_id')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror</label>
                    <label class="block xl:col-span-1"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">N.º present.</span><input wire:model="form.cantidad_presentaciones_detalle_compra" type="text" inputmode="decimal" @disabled($form->presentacion_articulo_id === '') placeholder="0" class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white">@error('form.cantidad_presentaciones_detalle_compra')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror</label>
                    <label class="block xl:col-span-1"><span class="mb-1.5 block whitespace-nowrap text-sm font-medium text-gray-700 dark:text-gray-300">Cant. real @if ($form->presentacion_articulo_id === '')<span class="text-error-500">*</span>@elseif (! $this->hasFixedEquivalence)<span class="font-normal text-gray-400">(op.)</span>@endif</span><input wire:model="form.cantidad_real_detalle_compra" type="text" inputmode="decimal" @disabled($this->hasFixedEquivalence) placeholder="0,000" class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white">@error('form.cantidad_real_detalle_compra')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror</label>
                    <label class="block xl:col-span-3"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Total pagado <span class="text-error-500">*</span></span><div class="relative"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-gray-500">Bs</span><input wire:model="form.total_pagado_detalle_compra" type="text" inputmode="decimal" placeholder="0,00" class="h-11 w-full rounded-xl border border-gray-300 pl-10 pr-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"></div>@error('form.total_pagado_detalle_compra')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror</label>
                </div>
                @if ($this->selectedArticle)
                    <p class="text-xs text-gray-500">Unidad base: <strong>{{ $this->selectedArticle->unidadMedida->nombre_unidad_medida }} ({{ $this->selectedArticle->unidadMedida->abreviatura_unidad_medida }})</strong>@if ($this->selectedPresentation?->equivalencia_base_presentacion_articulo) · Equivalencia: 1 {{ $this->selectedPresentation->nombre_presentacion_articulo }} = {{ number_format((float) $this->selectedPresentation->equivalencia_base_presentacion_articulo, 3, ',', '.') }} {{ $this->selectedArticle->unidadMedida->abreviatura_unidad_medida }}@endif @if ($this->hasFixedEquivalence) · La cantidad real se calculará automáticamente.@elseif ($this->selectedPresentation) · Si la conoces, registra la cantidad total contada o pesada.@endif</p>
                @endif
                <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observación <span class="font-normal text-gray-400">(opcional)</span></span><input wire:model="form.observaciones_detalle_compra" type="text" maxlength="500" placeholder="Calidad, madurez u otra referencia" class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">@error('form.observaciones_detalle_compra')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror</label>
                <div class="flex justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-800"><button type="button" wire:click="closeFormModal" class="h-11 rounded-xl border border-gray-300 px-5 text-sm font-semibold dark:border-gray-700">Cancelar</button><button type="submit" wire:loading.attr="disabled" wire:target="save" class="h-11 rounded-xl bg-brand-500 px-5 text-sm font-semibold text-white disabled:opacity-60"><span wire:loading.remove wire:target="save">Guardar producto</span><span wire:loading wire:target="save">Guardando…</span></button></div>
            </form>
        </div>
    </div>

    <div wire:show="showDeleteModal" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-5" role="alertdialog" aria-modal="true"><button type="button" wire:click="closeDeleteModal" class="fixed inset-0 bg-gray-950/55"></button><div class="relative w-full max-w-md rounded-3xl bg-white p-6 text-center dark:bg-gray-900"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">¿Quitar {{ $deletingArticleName }}?</h2><p class="mt-2 text-sm text-gray-500">El total de la compra se recalculará.</p><div class="mt-6 grid grid-cols-2 gap-3"><button type="button" wire:click="closeDeleteModal" class="h-11 rounded-xl border border-gray-300 text-sm font-semibold dark:border-gray-700">Volver</button><button type="button" wire:click="deleteDetail" class="h-11 rounded-xl bg-error-500 text-sm font-semibold text-white">Sí, quitar</button></div></div></div>

    <div wire:show="showRegisterModal" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-5" role="alertdialog" aria-modal="true"><button type="button" wire:click="$set('showRegisterModal', false)" class="fixed inset-0 bg-gray-950/55"></button><div class="relative w-full max-w-md rounded-3xl bg-white p-6 text-center dark:bg-gray-900"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">¿Registrar esta compra?</h2><p class="mt-2 text-sm leading-6 text-gray-500">Confirma {{ $compra->detalles->count() }} producto(s) por un total de Bs {{ number_format((float) $compra->total_compra, 2, ',', '.') }}. Después quedará en modo de solo lectura.</p><div class="mt-6 grid grid-cols-2 gap-3"><button type="button" wire:click="$set('showRegisterModal', false)" class="h-11 rounded-xl border border-gray-300 text-sm font-semibold dark:border-gray-700">Revisar</button><button type="button" wire:click="registerPurchase" class="h-11 rounded-xl bg-brand-500 text-sm font-semibold text-white">Sí, registrar</button></div></div></div>
</div>
