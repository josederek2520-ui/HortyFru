<div class="space-y-6">
    @php
        $recepcion = $this->reception;
        $articleOptions = $this->articleOptions->map(fn ($articulo) => [
            'id' => $articulo->id,
            'name' => $articulo->nombre_articulo,
            'categoryId' => $articulo->categoria_articulo_id,
            'category' => $articulo->categoriaArticulo->nombre_categoria_articulo,
        ])->values();
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('panel.recepciones.index') }}" wire:navigate class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-brand-600 dark:text-gray-400">← Volver a recepciones</a>
            <h1 class="mt-3 text-2xl font-semibold text-gray-900 dark:text-white">Productos de {{ $recepcion->codigo_recepcion }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Registra exactamente lo que llegó. El inventario se actualiza al confirmar la recepción.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('update', $recepcion)
                <button type="button" wire:click="openCreateModal" class="h-11 rounded-xl border border-brand-300 px-4 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 dark:border-brand-700 dark:text-brand-400">+ Agregar producto</button>
            @endcan
            @can('confirm', $recepcion)
                <button type="button" wire:click="$set('showConfirmModal', true)" class="h-11 rounded-xl bg-brand-500 px-5 text-sm font-semibold text-white transition hover:bg-brand-600">Confirmar recepción</button>
            @endcan
        </div>
    </div>

    <section class="grid gap-4 rounded-2xl border border-gray-200 bg-white p-5 sm:grid-cols-2 xl:grid-cols-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div><span class="text-xs font-medium uppercase tracking-wide text-gray-400">Compra</span><p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">{{ $recepcion->compra?->codigo_compra ?? 'Sin compra asociada' }}</p>@if ($recepcion->compra)<p class="mt-1 text-xs text-gray-500">{{ $recepcion->compra->proveedor->nombre_proveedor }}</p>@endif</div>
        <div><span class="text-xs font-medium uppercase tracking-wide text-gray-400">Almacén</span><p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">{{ $recepcion->almacen->nombre_almacen }}</p></div>
        <div><span class="text-xs font-medium uppercase tracking-wide text-gray-400">Empleado receptor</span><p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">{{ $recepcion->empleado->nombre_completo }}</p></div>
        <div><span class="text-xs font-medium uppercase tracking-wide text-gray-400">Fecha</span><p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">{{ $recepcion->fecha_recepcion_local->format('d/m/Y H:i') }}</p></div>
        <div><span class="text-xs font-medium uppercase tracking-wide text-gray-400">Estado</span><p class="mt-1 text-sm font-semibold {{ $recepcion->estaEnBorrador() ? 'text-warning-600' : ($recepcion->estado_recepcion->value === 'CONFIRMADA' ? 'text-success-600' : 'text-error-600') }}">{{ $recepcion->estado_recepcion->label() }}</p><p class="mt-1 text-xs text-gray-500">{{ $recepcion->detalles->count() }} producto(s)</p></div>
    </section>

    <div class="rounded-xl border border-blue-light-200 bg-blue-light-50 px-4 py-3 text-sm text-blue-light-700 dark:border-blue-light-500/20 dark:bg-blue-light-500/10 dark:text-blue-light-300">
        Todo lo registrado aquí ingresa al almacén. Los daños encontrados durante la preparación se registrarán después como merma o aprovechamiento, según corresponda.
    </div>

    @error('confirmacion')
        <p class="rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400">{{ $message }}</p>
    @enderror

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        @if ($recepcion->detalles->isEmpty())
            <div class="px-6 py-14 text-center"><p class="text-base font-semibold text-gray-800 dark:text-white">Esta recepción todavía no tiene productos</p><p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Agrega lo que llegó físicamente antes de confirmar.</p></div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-white/[0.02]"><tr><th class="px-5 py-3">Producto</th><th class="px-5 py-3">Presentación</th><th class="px-5 py-3 text-right">N.º present.</th><th class="px-5 py-3 text-right">Cantidad recibida</th><th class="px-5 py-3">Vencimiento</th><th class="px-5 py-3">Lote</th><th class="px-5 py-3 text-right">Acciones</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($recepcion->detalles as $detalle)
                            <tr wire:key="reception-detail-{{ $detalle->id }}" class="text-sm text-gray-700 dark:text-gray-300">
                                <td class="px-5 py-4"><p class="font-semibold text-gray-900 dark:text-white">{{ $detalle->articulo->nombre_articulo }}</p><p class="mt-1 text-xs text-gray-500">{{ $detalle->articulo->categoriaArticulo->nombre_categoria_articulo }}</p>@if ($detalle->calidad_detalle_recepcion)<p class="mt-1 text-xs text-brand-600">{{ $detalle->calidad_detalle_recepcion }}</p>@endif @if ($detalle->observaciones_detalle_recepcion)<p class="mt-1 max-w-xs text-xs text-gray-500">{{ $detalle->observaciones_detalle_recepcion }}</p>@endif</td>
                                <td class="px-5 py-4">{{ $detalle->presentacionArticulo?->nombre_presentacion_articulo ?? 'Directa en unidad base' }}</td>
                                <td class="px-5 py-4 text-right">{{ $detalle->cantidad_presentaciones_detalle_recepcion === null ? '—' : number_format((float) $detalle->cantidad_presentaciones_detalle_recepcion, 3, ',', '.') }}</td>
                                <td class="px-5 py-4 text-right font-semibold text-gray-900 dark:text-white">{{ number_format((float) $detalle->cantidad_base_detalle_recepcion, 3, ',', '.') }} {{ $detalle->unidadMedida->abreviatura_unidad_medida }}</td>
                                <td class="px-5 py-4">{{ $detalle->fecha_vencimiento_detalle_recepcion?->format('d/m/Y') ?? 'Sin fecha' }}</td>
                                <td class="px-5 py-4">@if ($detalle->lote)<span class="font-mono text-xs font-semibold text-success-700 dark:text-success-400">{{ $detalle->lote->codigo_lote }}</span>@else<span class="text-xs text-gray-400">Se genera al confirmar</span>@endif</td>
                                <td class="px-5 py-4 text-right">@can('update', $recepcion)<div class="flex justify-end gap-2"><button type="button" wire:click="openEditModal({{ $detalle->id }})" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold hover:bg-gray-50 dark:border-gray-700">Editar</button><button type="button" wire:click="openDeleteModal({{ $detalle->id }})" class="rounded-lg bg-error-50 px-3 py-2 text-xs font-semibold text-error-700 dark:bg-error-500/10 dark:text-error-400">Quitar</button></div>@else<span class="text-xs text-gray-400">Solo lectura</span>@endcan</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div wire:show="showFormModal" x-cloak x-on:keydown.escape.window="$wire.closeFormModal()" class="fixed inset-0 z-99999 flex items-end justify-center overflow-y-auto sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="reception-detail-form-title">
        <button type="button" wire:click="closeFormModal" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/55 backdrop-blur-sm" aria-label="Cerrar formulario"></button>
        <div class="relative max-h-[95vh] w-full overflow-y-auto rounded-t-3xl bg-white shadow-theme-xl sm:max-w-6xl sm:rounded-3xl dark:bg-gray-900">
            <div class="sticky top-0 z-20 flex items-start justify-between border-b border-gray-200 bg-white px-5 py-5 dark:border-gray-800 dark:bg-gray-900"><div><h2 id="reception-detail-form-title" class="text-lg font-semibold text-gray-900 dark:text-white">{{ $editingDetailId === null ? 'Agregar producto recibido' : 'Editar producto recibido' }}</h2><p class="mt-1 text-sm text-gray-500">Registra la cantidad física en la unidad base del producto.</p></div><button type="button" wire:click="closeFormModal" class="h-10 w-10 rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800">×</button></div>
            <form wire:submit="save" class="space-y-5 p-5 sm:p-6">
                @error('form.detalle')<p class="rounded-xl bg-error-50 px-4 py-3 text-sm text-error-700">{{ $message }}</p>@enderror

                @if ($recepcion->compra_id !== null)
                    <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Producto de la compra <span class="text-error-500">*</span></span><select wire:model="form.detalle_compra_id" wire:change="selectPurchaseDetail($event.target.value)" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"><option value="">Selecciona un producto comprado</option>@foreach ($this->purchaseDetailOptions as $detalleCompra)<option value="{{ $detalleCompra->id }}">{{ $detalleCompra->articulo->nombre_articulo }} — {{ $detalleCompra->presentacionArticulo?->nombre_presentacion_articulo ?? 'Compra directa' }}</option>@endforeach</select>@error('form.detalle_compra_id')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror</label>
                @else
                    <div x-data="purchaseArticleSelect(@js($articleOptions))" @keydown.escape.stop.prevent="close(true)" @focusout="if (!$el.contains($event.relatedTarget)) close()" class="grid gap-3 sm:grid-cols-2">
                        <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Categoría</span><select x-model="category" @change="changeCategory()" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"><option value="">Todas las categorías</option><template x-for="item in categories" :key="item.id"><option :value="item.id" x-text="item.name"></option></template></select></label>
                        <div class="relative" @click.outside="close()"><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Producto <span class="text-error-500">*</span></label><button x-ref="trigger" type="button" @click="open ? close() : show()" :disabled="busy" class="flex h-11 w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-3 text-left text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"><span x-text="selected?.name || 'Busca o selecciona'"></span><span>⌄</span></button><div x-show="open" x-cloak class="absolute z-30 mt-1 w-full rounded-xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-700 dark:bg-gray-900"><input x-ref="search" x-model="query" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="if (filteredOptions[activeIndex]) choose(filteredOptions[activeIndex])" type="text" placeholder="Escribe el producto…" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"><div x-ref="results" class="mt-2 max-h-56 overflow-y-auto"><template x-for="(item, position) in filteredOptions" :key="item.id"><button type="button" @mousedown.prevent @click="choose(item)" @mouseenter="activeIndex = position" :class="activeIndex === position ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/15' : ''" class="block w-full rounded-lg px-3 py-2 text-left text-sm"><span class="block" x-text="item.name"></span><span class="text-xs text-gray-400" x-text="item.category"></span></button></template><p x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-500">Sin resultados.</p></div></div>@error('form.articulo_id')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror</div>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4 xl:items-end">
                    <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Presentación</span><select wire:model.live="form.presentacion_articulo_id" @disabled($recepcion->compra_id !== null) class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-3 text-sm disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white"><option value="">Directa en unidad base</option>@foreach ($this->presentationOptions as $presentacion)<option value="{{ $presentacion->id }}">{{ $presentacion->nombre_presentacion_articulo }}</option>@endforeach</select>@error('form.presentacion_articulo_id')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">N.º de presentaciones @if ($form->presentacion_articulo_id !== '')<span class="text-error-500">*</span>@endif</span><input wire:model="form.cantidad_presentaciones_detalle_recepcion" type="text" inputmode="decimal" @disabled($form->presentacion_articulo_id === '') placeholder="0" class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white">@error('form.cantidad_presentaciones_detalle_recepcion')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cantidad física recibida @if (! $this->hasFixedEquivalence)<span class="text-error-500">*</span>@endif</span><div class="relative"><input wire:model="form.cantidad_base_detalle_recepcion" type="text" inputmode="decimal" @disabled($this->hasFixedEquivalence) placeholder="0,000" class="h-11 w-full rounded-xl border border-gray-300 px-3 pr-14 text-sm disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white"><span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-gray-400">{{ $this->selectedArticle?->unidadMedida?->abreviatura_unidad_medida }}</span></div>@error('form.cantidad_base_detalle_recepcion')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror</label>
                    <div class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Vencimiento <span class="font-normal text-gray-400">(opcional)</span></span><x-panel.form.date-picker id="reception-detail-expiration" model="form.fecha_vencimiento_detalle_recepcion" :default-date="$form->fecha_vencimiento_detalle_recepcion" date-format="Y-m-d" display-format="d/m/Y" :min-date="$recepcion->fecha_recepcion_local->format('Y-m-d')" :disable-mobile="true" sync-event="reception-detail-expiration-sync" placeholder="Selecciona una fecha" compact />@error('form.fecha_vencimiento_detalle_recepcion')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror</div>
                </div>

                @if ($this->selectedArticle)
                    <p class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500 dark:bg-gray-800/70">Unidad base: <strong>{{ $this->selectedArticle->unidadMedida->nombre_unidad_medida }} ({{ $this->selectedArticle->unidadMedida->abreviatura_unidad_medida }})</strong>@if ($this->selectedPresentation?->equivalencia_base_presentacion_articulo) · Equivalencia aplicada: 1 {{ $this->selectedPresentation->nombre_presentacion_articulo }} = {{ number_format((float) $this->selectedPresentation->equivalencia_base_presentacion_articulo, 3, ',', '.') }} {{ $this->selectedArticle->unidadMedida->abreviatura_unidad_medida }}. La cantidad física se calculará automáticamente.@elseif ($this->selectedPresentation) · Esta presentación es variable; pesa o cuenta el total recibido.@endif</p>
                @endif

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Calidad <span class="font-normal text-gray-400">(opcional)</span></span><input wire:model="form.calidad_detalle_recepcion" type="text" maxlength="100" placeholder="Ej. primera, madura" class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">@error('form.calidad_detalle_recepcion')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror</label>
                    <label class="block sm:col-span-2"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observación <span class="font-normal text-gray-400">(opcional)</span></span><input wire:model="form.observaciones_detalle_recepcion" type="text" maxlength="500" placeholder="Estado de los envases u otra referencia" class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">@error('form.observaciones_detalle_recepcion')<span class="mt-1 block text-xs text-error-500">{{ $message }}</span>@enderror</label>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-800"><button type="button" wire:click="closeFormModal" class="h-11 rounded-xl border border-gray-300 px-5 text-sm font-semibold dark:border-gray-700">Cancelar</button><button type="submit" wire:loading.attr="disabled" wire:target="save" class="h-11 rounded-xl bg-brand-500 px-5 text-sm font-semibold text-white disabled:opacity-60"><span wire:loading.remove wire:target="save">Guardar producto</span><span wire:loading wire:target="save">Guardando…</span></button></div>
            </form>
        </div>
    </div>

    <div wire:show="showDeleteModal" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-5" role="alertdialog" aria-modal="true"><button type="button" wire:click="closeDeleteModal" class="fixed inset-0 bg-gray-950/55"></button><div class="relative w-full max-w-md rounded-3xl bg-white p-6 text-center dark:bg-gray-900"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">¿Quitar {{ $deletingArticleName }}?</h2><p class="mt-2 text-sm text-gray-500">El producto dejará de formar parte de esta recepción.</p><div class="mt-6 grid grid-cols-2 gap-3"><button type="button" wire:click="closeDeleteModal" class="h-11 rounded-xl border border-gray-300 text-sm font-semibold dark:border-gray-700">Volver</button><button type="button" wire:click="deleteDetail" class="h-11 rounded-xl bg-error-500 text-sm font-semibold text-white">Sí, quitar</button></div></div></div>

    <div wire:show="showConfirmModal" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-5" role="alertdialog" aria-modal="true"><button type="button" wire:click="$set('showConfirmModal', false)" class="fixed inset-0 bg-gray-950/55"></button><div class="relative w-full max-w-lg rounded-3xl bg-white p-6 text-center dark:bg-gray-900"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">¿Confirmar esta recepción?</h2><p class="mt-2 text-sm leading-6 text-gray-500">Se crearán {{ $recepcion->detalles->count() }} lote(s) y una entrada de inventario en {{ $recepcion->almacen->nombre_almacen }}. Después quedará en modo de solo lectura.</p><div class="mt-4 rounded-xl bg-warning-50 px-4 py-3 text-left text-xs leading-5 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">Revisa las cantidades antes de continuar. La confirmación representa el ingreso físico al stock.</div><div class="mt-6 grid grid-cols-2 gap-3"><button type="button" wire:click="$set('showConfirmModal', false)" class="h-11 rounded-xl border border-gray-300 text-sm font-semibold dark:border-gray-700">Revisar</button><button type="button" wire:click="confirmReception" wire:loading.attr="disabled" wire:target="confirmReception" class="h-11 rounded-xl bg-brand-500 text-sm font-semibold text-white disabled:opacity-60"><span wire:loading.remove wire:target="confirmReception">Sí, confirmar</span><span wire:loading wire:target="confirmReception">Confirmando…</span></button></div></div></div>
</div>
