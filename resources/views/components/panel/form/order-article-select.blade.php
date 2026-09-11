@props(['index'])

<div x-data="orderArticleSelect(orderArticleOptions, {{ $index }})" class="grid gap-4 sm:grid-cols-2 lg:contents" @keydown.escape.stop.prevent="close(true)" @focusout="if (!$el.contains($event.relatedTarget)) close()">
    <label class="block lg:col-span-2">
        <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Categoría</span>
        <select x-model="category" @change="changeCategory()" :disabled="busy" class="h-12 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            <option value="">Todas las categorías</option>
            <template x-for="item in categories" :key="item.id">
                <option :value="item.id" x-text="item.name"></option>
            </template>
        </select>
    </label>

    <div class="relative lg:col-span-3" @click.outside="close()">
        <label for="order-article-{{ $index }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Producto <span class="text-error-500">*</span></label>
        <button id="order-article-{{ $index }}" x-ref="trigger" type="button" @click="open ? close() : show()" @keydown.arrow-down.prevent="show()" :disabled="busy" :aria-expanded="open" aria-haspopup="listbox" aria-controls="order-article-options-{{ $index }}" class="flex min-h-12 w-full items-center justify-between gap-2 rounded-xl border border-gray-300 bg-white px-3 py-2 text-left text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            <span x-text="selected?.name || 'Selecciona o busca un producto'"></span>
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
        </button>

        <div x-show="open" x-cloak class="absolute z-30 mt-1 w-full rounded-xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-700 dark:bg-gray-900">
            <input x-ref="search" x-model="query" @input="activeIndex = 0" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="if (filteredOptions[activeIndex]) choose(filteredOptions[activeIndex])" type="text" role="combobox" aria-label="Buscar producto" aria-autocomplete="list" :aria-expanded="open" aria-controls="order-article-options-{{ $index }}" :aria-activedescendant="filteredOptions.length ? 'order-article-option-{{ $index }}-' + filteredOptions[activeIndex]?.id : null" autocomplete="off" placeholder="Escribe el nombre del producto…" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white">
            <div id="order-article-options-{{ $index }}" x-ref="results" role="listbox" aria-label="Productos por categoría" class="mt-2 max-h-60 overflow-y-auto">
                <template x-for="(item, position) in filteredOptions" :key="item.id">
                    <div role="presentation">
                        <p x-show="position === 0 || filteredOptions[position - 1].categoryId !== item.categoryId" x-text="item.category" class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400"></p>
                        <button type="button" role="option" :id="'order-article-option-{{ $index }}-' + item.id" :aria-selected="selected?.id === item.id" tabindex="-1" @mousedown.prevent @click="choose(item)" @mouseenter="activeIndex = position" :class="activeIndex === position ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-400' : 'text-gray-800 dark:text-gray-200'" class="block w-full rounded-lg px-3 py-2.5 text-left text-sm" x-text="item.name"></button>
                    </div>
                </template>
            </div>
            <p x-show="filteredOptions.length === 0" role="status" class="px-3 py-3 text-sm text-gray-500 dark:text-gray-400">No se encontraron productos.</p>
        </div>
        @error("form.detalles.$index.articulo_id")<span class="mt-1.5 block text-xs text-error-500">{{ $message }}</span>@enderror
    </div>
</div>
