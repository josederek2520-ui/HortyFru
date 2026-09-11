@props([
    'id' => 'datepicker-' . uniqid(),
    'mode' => 'single',
    'defaultDate' => null,
    'label' => null,
    'placeholder' => 'Selecciona una fecha',
    'name' => null,
    'dateFormat' => 'Y-m-d',
    'displayFormat' => 'd/m/Y',
    'minDate' => null,
    'maxDate' => null,
    'model' => null,
    'enableTime' => false,
    'time24hr' => true,
    'minuteIncrement' => 1,
    'disableMobile' => false,
    'syncEvent' => null,
    'invalid' => false,
    'compact' => false,
    'ariaLabel' => null,
])

<div {{ $attributes->class('w-full') }} x-data="{
    flatpickrInstance: null,
    init() {
        this.$nextTick(() => {
            this.flatpickrInstance = flatpickr(this.$refs.dateInput, {
                mode: @js($mode),
                static: false,
                monthSelectorType: 'dropdown',
                dateFormat: @js($dateFormat),
                altInput: true,
                altFormat: @js($displayFormat),
                defaultDate: @js($defaultDate),
                minDate: @js($minDate),
                maxDate: @js($maxDate),
                enableTime: @js($enableTime),
                time_24hr: @js($time24hr),
                minuteIncrement: @js($minuteIncrement),
                locale: window.flatpickrSpanish,
                allowInput: false,
                disableMobile: @js($disableMobile),
                onReady: (selectedDates, dateStr, instance) => {
                    if (instance.mobileInput) {
                        instance.input.id = @js($id.'-value');
                        instance.mobileInput.id = @js($id);
                        @if ($ariaLabel)
                            instance.mobileInput.setAttribute('aria-label', @js($ariaLabel));
                        @endif
                    } else if (instance.altInput) {
                        instance.input.id = @js($id.'-value');
                        instance.altInput.id = @js($id);
                        @if ($ariaLabel)
                            instance.altInput.setAttribute('aria-label', @js($ariaLabel));
                        @endif
                    }
                },
                onChange: (selectedDates, dateStr, instance) => {
                    @if ($model)
                        $wire.set(@js($model), dateStr);
                    @endif

                    this.$dispatch('date-change', {
                        selectedDates,
                        dateStr,
                        instance
                    });
                }
            });
        });
    },
    destroy() {
        if (this.flatpickrInstance) {
            this.flatpickrInstance.destroy();
            this.flatpickrInstance = null;
        }
    }
}" @if ($syncEvent) x-on:{{ $syncEvent }}.window="if (flatpickrInstance) { if ('minDate' in $event.detail) flatpickrInstance.set('minDate', $event.detail.minDate); if ('maxDate' in $event.detail) flatpickrInstance.set('maxDate', $event.detail.maxDate); flatpickrInstance.setDate($event.detail.value, false); }" @endif>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ $label }}
        </label>
    @endif

    <div wire:ignore class="custom-datepicker relative">
        <input
            x-ref="dateInput"
            type="text"
            id="{{ $id }}"
            name="{{ $name }}"
            @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
            placeholder="{{ $placeholder }}"
            class="{{ $compact ? 'h-11' : 'h-12' }} w-full appearance-none rounded-xl border bg-transparent px-4 py-2.5 pr-12 text-sm text-gray-800 shadow-theme-xs outline-none transition placeholder:text-gray-400 focus:ring-3 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 {{ $invalid ? 'border-error-400 focus:border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500/10 dark:border-gray-700' }}"
            autocomplete="off"
        />
        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" class="size-6">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M8 2C8.41421 2 8.75 2.33579 8.75 2.75V3.75H15.25V2.75C15.25 2.33579 15.5858 2 16 2C16.4142 2 16.75 2.33579 16.75 2.75V3.75H18.5C19.7426 3.75 20.75 4.75736 20.75 6V9V19C20.75 20.2426 19.7426 21.25 18.5 21.25H5.5C4.25736 21.25 3.25 20.2426 3.25 19V9V6C3.25 4.75736 4.25736 3.75 5.5 3.75H7.25V2.75C7.25 2.33579 7.58579 2 8 2ZM8 5.25H5.5C5.08579 5.25 4.75 5.58579 4.75 6V8.25H19.25V6C19.25 5.58579 18.9142 5.25 18.5 5.25H16H8ZM19.25 9.75H4.75V19C4.75 19.4142 5.08579 19.75 5.5 19.75H18.5C18.9142 19.75 19.25 19.4142 19.25 19V9.75Z" fill="currentColor"></path>
            </svg>
        </span>
    </div>
</div>
