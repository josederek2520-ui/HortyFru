@props([
    'initialMessage' => null,
    'initialTitle' => 'Operación completada',
    'initialType' => 'success',
])

<div
    {{ $attributes->merge(['class' => 'pointer-events-none fixed inset-x-4 top-20 z-999999 flex justify-end sm:inset-x-6']) }}
    x-data="toastNotification"
    x-on:toast.window="show($event.detail)"
    data-initial-message="{{ $initialMessage }}"
    data-initial-title="{{ $initialTitle }}"
    data-initial-type="{{ $initialType }}"
    aria-live="polite"
    aria-atomic="true"
>
    <div
        x-cloak
        x-show="visible"
        x-transition:enter="transition duration-300 ease-out"
        x-transition:enter-start="translate-y-3 opacity-0 sm:translate-x-4 sm:translate-y-0"
        x-transition:enter-end="translate-x-0 translate-y-0 opacity-100"
        x-transition:leave="transition duration-200 ease-in"
        x-transition:leave-start="translate-x-0 opacity-100"
        x-transition:leave-end="translate-y-2 opacity-0 sm:translate-x-4 sm:translate-y-0"
        class="pointer-events-auto relative w-full max-w-sm overflow-hidden rounded-2xl border bg-white shadow-theme-xl dark:bg-gray-900"
        x-bind:class="appearance.container"
        x-bind:role="type === 'error' ? 'alert' : 'status'"
    >
        <div class="flex items-start gap-3 p-4 pr-12">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" x-bind:class="appearance.iconBackground">
                <svg x-show="type === 'success'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 12.5 4.2 4.2L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                <svg x-show="type === 'info'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 17v-6M12 7.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" /><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8" /></svg>
                <svg x-show="type === 'warning'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 4.4 2.7 18a2 2 0 0 0 1.75 3h15.1a2 2 0 0 0 1.75-3L13.7 4.4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                <svg x-show="type === 'error'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m8 8 8 8M16 8l-8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" /><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8" /></svg>
            </span>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="title"></p>
                <p class="mt-1 text-sm leading-5 text-gray-600 dark:text-gray-300" x-text="message"></p>
            </div>
        </div>

        <button type="button" x-on:click="close" class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 dark:hover:bg-white/5 dark:hover:text-gray-200" aria-label="Cerrar notificación">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
        </button>

        <div class="h-1 bg-gray-100 dark:bg-gray-800">
            <div class="h-full origin-left transition-transform duration-[3000ms] ease-linear" x-bind:class="[appearance.progress, progressFull ? 'scale-x-100' : 'scale-x-0']"></div>
        </div>
    </div>
</div>
