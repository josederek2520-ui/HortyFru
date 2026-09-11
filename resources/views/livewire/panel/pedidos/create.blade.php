<div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex flex-col gap-1">
            <a href="{{ route('panel.pedidos.index') }}" wire:navigate class="inline-flex w-fit items-center gap-2 text-sm font-semibold text-gray-500 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                Volver a pedidos
            </a>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white sm:text-3xl">{{ $editing ? 'Editar pedido' : 'Registrar pedido' }}</h1>
            <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">{{ $editing ? "Actualiza los datos de {$codigoPedido}. La equivalencia histórica de los productos existentes se conservará." : 'Registra lo solicitado por la sucursal. El código y la fecha de recepción se asignarán automáticamente.' }}</p>
        </div>
        <div class="inline-flex w-fit items-center gap-2 rounded-full bg-warning-50 px-3 py-1.5 text-xs font-semibold text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
            <span class="h-2 w-2 rounded-full bg-warning-500"></span>
            {{ $editing ? $codigoPedido : 'Estado inicial: Pendiente' }}
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <section aria-labelledby="order-general-information" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h12v18H6zM9 7h6M9 11h6M9 15h3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </span>
                    <div><h2 id="order-general-information" class="font-semibold text-gray-900 dark:text-white">Información del pedido</h2><p class="text-sm text-gray-500 dark:text-gray-400">Indica quién solicita y para qué fecha necesita los productos.</p></div>
                </div>
            </div>

            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Sucursal solicitante <span class="text-error-500">*</span></span>
                    <select wire:model="form.sucursal_id" autofocus class="h-12 w-full rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 shadow-theme-xs outline-none transition focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">Selecciona una sucursal</option>
                        @foreach ($this->branchOptions as $sucursal)
                            <option value="{{ $sucursal->id }}">{{ $sucursal->cliente->razon_social }} — {{ $sucursal->nombre_sucursal }}</option>
                        @endforeach
                    </select>
                    @error('form.sucursal_id')<span class="mt-1.5 block text-xs text-error-500">{{ $message }}</span>@enderror
                </label>

                <div class="block">
                    <label for="order-required-date" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha requerida <span class="text-error-500">*</span></label>
                    <x-panel.form.date-picker
                        id="order-required-date"
                        model="form.fecha_requerida_pedido"
                        :default-date="$form->fecha_requerida_pedido"
                        :min-date="today()->toDateString()"
                        :invalid="$errors->has('form.fecha_requerida_pedido')"
                        wire:key="order-required-date-{{ $form->fecha_requerida_pedido }}"
                    />
                    @error('form.fecha_requerida_pedido')<span class="mt-1.5 block text-xs text-error-500">{{ $message }}</span>@enderror
                </div>

                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones generales <span class="font-normal text-gray-400">(opcional)</span></span>
                    <textarea wire:model="form.observaciones_pedido" rows="3" maxlength="2000" placeholder="Horario de entrega, referencia o indicaciones proporcionadas por la sucursal" class="w-full resize-y rounded-xl border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 shadow-theme-xs outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                    @error('form.observaciones_pedido')<span class="mt-1.5 block text-xs text-error-500">{{ $message }}</span>@enderror
                </label>
            </div>
        </section>

        <section aria-labelledby="order-products" x-data="{ orderArticleOptions: @js($this->articleOptions->map(fn ($articulo) => ['id' => $articulo->id, 'name' => $articulo->nombre_articulo, 'categoryId' => $articulo->categoria_articulo_id, 'category' => $articulo->categoriaArticulo?->nombre_categoria_articulo ?? 'Sin categoría'])->values()) }" class=" rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16v10H4zM7 4h10v3M8 11h8M8 14h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </span>
                    <div><h2 id="order-products" class="font-semibold text-gray-900 dark:text-white">Productos solicitados</h2><p class="text-sm text-gray-500 dark:text-gray-400">Agrega una fila por cada producto y presentación solicitada.</p></div>
                </div>
                <button type="button" wire:click="addDetail" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-brand-200 px-4 text-sm font-semibold text-brand-600 transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 dark:border-brand-500/30 dark:text-brand-400 dark:hover:bg-brand-500/10">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                    Agregar producto
                </button>
            </div>

            <div class="flex flex-col gap-4 p-5 sm:p-6">
                @error('form.detalles')<p class="text-sm text-error-500">{{ $message }}</p>@enderror

                @foreach ($form->detalles as $index => $detalle)
                    @php($articuloSeleccionado = $this->articleOptions->firstWhere('id', (int) ($detalle['articulo_id'] ?? 0)))
                    @php($presentacionSeleccionada = $articuloSeleccionado?->presentaciones->firstWhere('id', (int) ($detalle['presentacion_articulo_id'] ?? 0)))
                    <article wire:key="create-order-detail-{{ count($form->detalles) }}-{{ $index }}" class="rounded-2xl border border-gray-200 bg-gray-50/60 p-4 dark:border-gray-800 dark:bg-white/[0.02] sm:p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-xs font-bold text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400">{{ $index + 1 }}</span><h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Producto</h3></div>
                            <button type="button" wire:click="removeDetail({{ $index }})" @disabled(count($form->detalles) === 1) class="inline-flex min-h-9 items-center gap-1 rounded-lg px-2.5 text-xs font-semibold text-error-600 transition hover:bg-error-50 disabled:cursor-not-allowed disabled:opacity-40 dark:text-error-400 dark:hover:bg-error-500/10">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 7h14M9 7V4h6v3M8 10v7m4-7v7m4-7v7M7 7l1 14h8l1-14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                Quitar
                            </button>
                        </div>

                        <div class="mt-4 grid items-end gap-4 lg:grid-cols-12">
                            <x-panel.form.order-article-select :index="$index" />
                            <label class="block lg:col-span-2"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Presentación <span class="text-error-500">*</span></span><select wire:model="form.detalles.{{ $index }}.presentacion_articulo_id" @disabled($articuloSeleccionado === null) class="h-12 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white"><option value="">Selecciona</option>@foreach ($articuloSeleccionado?->presentaciones ?? [] as $presentacion)<option value="{{ $presentacion->id }}">{{ $presentacion->nombre_presentacion_articulo }}</option>@endforeach</select>@error("form.detalles.$index.presentacion_articulo_id")<span class="mt-1.5 block text-xs text-error-500">{{ $message }}</span>@enderror</label>
                            <label class="block lg:col-span-2"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cantidad <span class="text-error-500">*</span></span><input type="number" min="0.001" step="0.001" wire:model="form.detalles.{{ $index }}.cantidad_solicitada_detalle_pedido" class="h-12 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">@error("form.detalles.$index.cantidad_solicitada_detalle_pedido")<span class="mt-1.5 block text-xs text-error-500">{{ $message }}</span>@enderror</label>
                            <label class="block lg:col-span-3"><span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observación <span class="font-normal text-gray-400">(opcional)</span></span><input type="text" maxlength="500" wire:model="form.detalles.{{ $index }}.observaciones_detalle_pedido" placeholder="Ej. maduro o verde" class="h-12 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">@error("form.detalles.$index.observaciones_detalle_pedido")<span class="mt-1.5 block text-xs text-error-500">{{ $message }}</span>@enderror</label>
                        </div>

                        @if ($presentacionSeleccionada)
                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Unidad base: {{ $articuloSeleccionado->unidadMedida->abreviatura_unidad_medida }} · {{ $presentacionSeleccionada->permite_fraccion_presentacion_articulo ? 'Admite cantidades fraccionadas' : 'Solo cantidades enteras' }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        <div class="sticky bottom-4 z-10 rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-theme-lg backdrop-blur dark:border-gray-800 dark:bg-gray-900/95">
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('panel.pedidos.index') }}" wire:navigate class="inline-flex min-h-12 items-center justify-center rounded-xl border border-gray-300 px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Cancelar y volver</a>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-brand-500 px-6 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 disabled:cursor-wait disabled:opacity-70">
                    <svg wire:loading.remove wire:target="save" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h12l2 2v14H5zM8 4v6h8V4M8 16h8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    <span wire:loading.remove wire:target="save">{{ $editing ? 'Guardar cambios' : 'Registrar pedido' }}</span><span wire:loading wire:target="save">{{ $editing ? 'Guardando...' : 'Registrando...' }}</span>
                </button>
            </div>
        </div>
    </form>
</div>
