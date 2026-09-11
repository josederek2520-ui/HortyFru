<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2 text-sm font-medium text-brand-500 dark:text-brand-400">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                Inventario
            </div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Productos</h1>
            <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">Administra el catálogo de materias primas, productos terminados e insumos.</p>
        </div>

        @can('create', App\Models\Articulo::class)
            <button type="button" wire:click="openCreateModal" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                Nuevo producto
            </button>
        @endcan
    </div>

    <section aria-labelledby="articles-table-heading" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <h2 id="articles-table-heading" class="sr-only">Listado de productos</h2>

        <div class="grid grid-cols-1 gap-4 border-b border-gray-200 p-4 dark:border-gray-800 xl:grid-cols-[auto_minmax(18rem,32rem)_auto] xl:items-center xl:justify-between">
            <div class="grid grid-cols-3 rounded-xl bg-gray-100 p-1 dark:bg-gray-900" role="group" aria-label="Filtrar productos por estado">
                <button type="button" wire:click="$set('status', 'all')" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $status === 'all' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">Todos <span class="rounded-full bg-brand-50 px-1.5 py-0.5 text-xs text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ $this->totalArticles }}</span></button>
                <button type="button" wire:click="$set('status', 'active')" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $status === 'active' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">Activos <span class="rounded-full bg-white px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $this->activeArticles }}</span></button>
                <button type="button" wire:click="$set('status', 'inactive')" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $status === 'inactive' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">Inactivos <span class="rounded-full bg-white px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $this->totalArticles - $this->activeArticles }}</span></button>
            </div>

            <label class="relative block w-full xl:justify-self-center">
                <span class="sr-only">Buscar productos</span>
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7" /><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
                <input type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar producto, categoría o unidad..." class="h-11 w-full rounded-xl border border-gray-300 bg-transparent py-2.5 pl-11 pr-4 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-brand-500" />
            </label>

            <div class="hidden items-center justify-end gap-2 text-xs font-medium text-gray-500 xl:flex dark:text-gray-400"><span class="h-2 w-2 rounded-full bg-brand-500"></span>{{ $this->articles->total() }} {{ $this->articles->total() === 1 ? 'resultado' : 'resultados' }}</div>
        </div>

        @if ($this->articles->isEmpty())
            <div class="flex flex-col items-center gap-3 px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg></span>
                <div><h3 class="font-semibold text-gray-800 dark:text-white/90">No encontramos productos</h3><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Prueba con otra búsqueda o registra el primer producto.</p></div>
                @if ($search !== '' || $status !== 'all')<button type="button" wire:click="clearFilters" class="text-sm font-semibold text-brand-500">Limpiar filtros</button>@endif
            </div>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left">
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"><th class="px-5 py-3.5">Producto</th><th class="px-5 py-3.5">Categoría</th><th class="px-5 py-3.5">Unidad</th><th class="px-5 py-3.5">Estado</th><th class="px-5 py-3.5 text-right">Acciones</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->articles as $articulo)
                            <tr wire:key="article-row-{{ $articulo->id }}" class="transition hover:bg-gray-50/80 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-4"><div class="flex items-center gap-3">@if ($articulo->imagen_articulo)<img src="{{ $articulo->imagen_url }}" alt="{{ $articulo->nombre_articulo }}" loading="lazy" class="h-11 w-11 shrink-0 rounded-xl border border-gray-200 object-cover dark:border-gray-700" />@else<span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg></span>@endif<span class="font-semibold text-gray-800 dark:text-white/90">{{ $articulo->nombre_articulo }}</span></div></td>
                                <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $articulo->categoriaArticulo->nombre_categoria_articulo }}</td>
                                <td class="px-5 py-4"><span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 font-mono text-sm font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300" title="{{ $articulo->unidadMedida->nombre_unidad_medida }}">{{ $articulo->unidadMedida->abreviatura_unidad_medida }}</span></td>
                                <td class="px-5 py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $articulo->estado_articulo ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}"><span class="h-1.5 w-1.5 rounded-full {{ $articulo->estado_articulo ? 'bg-success-500' : 'bg-gray-400' }}"></span>{{ $articulo->estado_articulo ? 'Activo' : 'Inactivo' }}</span></td>
                                <td class="px-5 py-4"><div class="flex justify-end gap-2">@can('update', $articulo)<button type="button" wire:click="openEditModal({{ $articulo->id }})" class="h-9 rounded-lg border border-gray-300 px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Editar</button>@endcan @can('changeStatus', $articulo)<button type="button" wire:click="openStatusModal({{ $articulo->id }})" class="h-9 rounded-lg px-3 text-xs font-semibold {{ $articulo->estado_articulo ? 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' : 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' }}">{{ $articulo->estado_articulo ? 'Desactivar' : 'Activar' }}</button>@endcan</div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-gray-100 md:hidden dark:divide-gray-800">
                @foreach ($this->articles as $articulo)
                    <article wire:key="article-card-{{ $articulo->id }}" class="flex flex-col gap-4 p-5">
                        <div class="flex items-start gap-3">@if ($articulo->imagen_articulo)<img src="{{ $articulo->imagen_url }}" alt="{{ $articulo->nombre_articulo }}" loading="lazy" class="h-14 w-14 shrink-0 rounded-xl border border-gray-200 object-cover dark:border-gray-700" />@else<span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg></span>@endif<div class="min-w-0 grow"><h3 class="truncate font-semibold text-gray-800 dark:text-white/90">{{ $articulo->nombre_articulo }}</h3><p class="mt-1 text-sm text-gray-500">{{ $articulo->categoriaArticulo->nombre_categoria_articulo }} · {{ $articulo->unidadMedida->abreviatura_unidad_medida }}</p></div><span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $articulo->estado_articulo ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">{{ $articulo->estado_articulo ? 'Activo' : 'Inactivo' }}</span></div>
                        <div class="grid grid-cols-2 gap-2">@can('update', $articulo)<button type="button" wire:click="openEditModal({{ $articulo->id }})" class="h-10 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Editar</button>@endcan @can('changeStatus', $articulo)<button type="button" wire:click="openStatusModal({{ $articulo->id }})" class="h-10 rounded-lg text-sm font-semibold {{ $articulo->estado_articulo ? 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' : 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' }}">{{ $articulo->estado_articulo ? 'Desactivar' : 'Activar' }}</button>@endcan</div>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $this->articles->links() }}</div>
        @endif
    </section>

    <div wire:show="showFormModal" x-cloak x-on:article-form-opened.window="$nextTick(() => $refs.articleName.focus())" x-on:keydown.escape.window="$wire.closeFormModal()" class="fixed inset-0 z-99999 flex items-end justify-center overflow-y-auto sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="article-form-title">
        <button type="button" wire:click="closeFormModal" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/55 backdrop-blur-sm" aria-label="Cerrar modal"></button>
        <div class="relative max-h-[95vh] w-full overflow-y-auto rounded-t-3xl bg-white shadow-theme-xl sm:max-w-2xl sm:rounded-3xl dark:bg-gray-900">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-gray-200 bg-white px-5 py-5 sm:px-6 dark:border-gray-800 dark:bg-gray-900"><div><h2 id="article-form-title" class="text-lg font-semibold text-gray-900 dark:text-white">{{ $editingArticleId === null ? 'Registrar producto' : 'Editar producto' }}</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Asigna su categoría y la unidad con la que se controlará.</p></div><button type="button" wire:click="closeFormModal" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700" aria-label="Cerrar">×</button></div>

            <form wire:submit="save" x-data="{ uploading: false, progress: 0 }" x-on:article-image-cleared.window="$refs.articleImage.value = ''" class="flex flex-col gap-6 p-5 sm:p-6">
                <label class="flex flex-col gap-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del producto <span class="text-error-500">*</span></span><input x-ref="articleName" type="text" wire:model.blur="form.nombre_articulo" autocomplete="off" placeholder="Ej. Tomate" class="h-11 rounded-xl border bg-transparent px-4 text-sm text-gray-800 outline-none focus:ring-3 dark:text-white {{ $errors->has('form.nombre_articulo') ? 'border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-400 focus:ring-brand-500/10 dark:border-gray-700' }}" />@error('form.nombre_articulo')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>

                <div class="flex flex-col gap-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Imagen del producto <span class="font-normal text-gray-400">(opcional)</span></span>
                    <div x-on:dragover.prevent x-on:drop.prevent="$refs.articleImage.files = $event.dataTransfer.files; $refs.articleImage.dispatchEvent(new Event('change', { bubbles: true }))" class="grid gap-4 rounded-2xl border border-dashed p-4 transition {{ $errors->has('form.imagen_articulo') ? 'border-error-400 bg-error-50/40 dark:bg-error-500/5' : 'border-gray-300 bg-gray-50/70 hover:border-brand-400 dark:border-gray-700 dark:bg-white/[0.02]' }} sm:grid-cols-[7rem_minmax(0,1fr)] sm:items-center">
                        <div class="relative mx-auto flex h-28 w-28 items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 sm:mx-0">
                            @if ($this->newImagePreviewUrl)
                                <img src="{{ $this->newImagePreviewUrl }}" alt="Vista previa de la nueva imagen" class="h-full w-full object-cover" />
                            @elseif ($this->currentImageUrl)
                                <img src="{{ $this->currentImageUrl }}" alt="Imagen actual del producto" class="h-full w-full object-cover" />
                            @else
                                <svg class="h-10 w-10 text-gray-300 dark:text-gray-600" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v14H4zM7 15l3-3 3 3 2-2 3 3M9 9h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                            @endif
                        </div>

                        <div class="flex min-w-0 flex-col gap-3 text-center sm:text-left">
                            <div><p class="text-sm font-semibold text-gray-800 dark:text-white/90">Selecciona o arrastra una imagen</p><p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">JPG, JPEG, PNG o WebP. Máximo 3 MB y 4000 × 4000 px.</p></div>
                            <div class="flex flex-wrap justify-center gap-2 sm:justify-start">
                                <label class="inline-flex h-10 cursor-pointer items-center justify-center rounded-xl bg-brand-500 px-4 text-sm font-semibold text-white transition hover:bg-brand-600"><span>Seleccionar imagen</span><input x-ref="articleImage" type="file" wire:model="form.imagen_articulo" accept="image/jpeg,image/png,image/webp" class="sr-only" x-on:livewire-upload-start="uploading = true; progress = 0" x-on:livewire-upload-finish="uploading = false" x-on:livewire-upload-error="uploading = false" x-on:livewire-upload-progress="progress = $event.detail.progress" /></label>
                                @if ($this->newImagePreviewUrl || $this->currentImageUrl)
                                    <button type="button" wire:click="removeImage" class="h-10 rounded-xl border border-error-200 px-4 text-sm font-semibold text-error-600 transition hover:bg-error-50 dark:border-error-500/30 dark:hover:bg-error-500/10">Quitar imagen</button>
                                @endif
                            </div>
                            <div x-show="uploading" x-cloak class="flex items-center gap-3"><div class="h-1.5 grow overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"><div class="h-full rounded-full bg-brand-500 transition-all" x-bind:style="`width: ${progress}%`"></div></div><span class="text-xs font-semibold text-brand-600" x-text="`${progress}%`"></span></div>
                        </div>
                    </div>
                    @error('form.imagen_articulo')<span class="text-xs text-error-600">{{ $message }}</span>@enderror
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <label class="flex flex-col gap-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Categoría <span class="text-error-500">*</span></span><select wire:model="form.categoria_articulo_id" class="h-11 rounded-xl border bg-transparent px-4 text-sm text-gray-800 outline-none focus:ring-3 dark:bg-gray-900 dark:text-white {{ $errors->has('form.categoria_articulo_id') ? 'border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-400 focus:ring-brand-500/10 dark:border-gray-700' }}"><option value="">Selecciona una categoría</option>@foreach ($this->categoryOptions as $categoriaArticulo)<option value="{{ $categoriaArticulo->id }}">{{ $categoriaArticulo->nombre_categoria_articulo }}</option>@endforeach</select>@error('form.categoria_articulo_id')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>
                    <label class="flex flex-col gap-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Unidad de medida <span class="text-error-500">*</span></span><select wire:model="form.unidad_medida_id" class="h-11 rounded-xl border bg-transparent px-4 text-sm text-gray-800 outline-none focus:ring-3 dark:bg-gray-900 dark:text-white {{ $errors->has('form.unidad_medida_id') ? 'border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-400 focus:ring-brand-500/10 dark:border-gray-700' }}"><option value="">Selecciona una unidad</option>@foreach ($this->measurementUnitOptions as $unidadMedida)<option value="{{ $unidadMedida->id }}">{{ $unidadMedida->nombre_unidad_medida }} ({{ $unidadMedida->abreviatura_unidad_medida }})</option>@endforeach</select>@error('form.unidad_medida_id')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>
                </div>

                @if ($editingArticleId === null || auth()->user()->can(App\Enums\PermissionName::ArticlesChangeStatus->value))
                    <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 p-4 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]"><span><span class="block text-sm font-semibold text-gray-800 dark:text-white/90">Producto activo</span><span class="mt-1 block text-xs leading-5 text-gray-500 dark:text-gray-400">Los productos inactivos conservan su historial, pero no estarán disponibles para nuevas operaciones.</span></span><input type="checkbox" wire:model="form.estado_articulo" class="peer sr-only" /><span class="relative mt-0.5 h-6 w-11 shrink-0 rounded-full bg-gray-300 transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:bg-brand-500 peer-checked:after:translate-x-5 dark:bg-gray-700"></span></label>
                @endif

                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end dark:border-gray-800"><button type="button" wire:click="closeFormModal" class="h-11 rounded-xl border border-gray-300 px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Cancelar</button><button type="submit" wire:loading.attr="disabled" wire:target="save" class="h-11 rounded-xl bg-brand-500 px-5 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:opacity-70"><span wire:loading.remove wire:target="save">{{ $editingArticleId === null ? 'Registrar producto' : 'Guardar cambios' }}</span><span wire:loading wire:target="save">Guardando...</span></button></div>
            </form>
        </div>
    </div>

    <div wire:show="showStatusModal" x-cloak x-on:keydown.escape.window="$wire.closeStatusModal()" class="fixed inset-0 z-99999 flex items-center justify-center p-5" role="alertdialog" aria-modal="true" aria-labelledby="article-status-title">
        <button type="button" wire:click="closeStatusModal" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/55 backdrop-blur-sm" aria-label="Cerrar confirmación"></button>
        <div class="relative w-full max-w-md rounded-3xl bg-white p-6 text-center shadow-theme-xl dark:bg-gray-900"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl {{ $statusArticleIsActive ? 'bg-error-50 text-error-600 dark:bg-error-500/10 dark:text-error-400' : 'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400' }}"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 4.4 2.7 18a2 2 0 0 0 1.75 3h15.1a2 2 0 0 0 1.75-3L13.7 4.4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg></span><h2 id="article-status-title" class="mt-5 text-lg font-semibold text-gray-900 dark:text-white">{{ $statusArticleIsActive ? '¿Desactivar este producto?' : '¿Activar este producto?' }}</h2><p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400"><strong class="font-semibold text-gray-700 dark:text-gray-300">{{ $statusArticleName }}</strong> {{ $statusArticleIsActive ? 'dejará de estar disponible para nuevas operaciones, pero conservará su historial.' : 'volverá a estar disponible para nuevas operaciones.' }}</p><div class="mt-6 grid grid-cols-2 gap-3"><button type="button" wire:click="closeStatusModal" class="h-11 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Cancelar</button><button type="button" wire:click="changeStatus" wire:loading.attr="disabled" wire:target="changeStatus" class="h-11 rounded-xl text-sm font-semibold text-white disabled:opacity-70 {{ $statusArticleIsActive ? 'bg-error-500 hover:bg-error-600' : 'bg-success-500 hover:bg-success-600' }}"><span wire:loading.remove wire:target="changeStatus">{{ $statusArticleIsActive ? 'Sí, desactivar' : 'Sí, activar' }}</span><span wire:loading wire:target="changeStatus">Procesando...</span></button></div></div>
    </div>
</div>
