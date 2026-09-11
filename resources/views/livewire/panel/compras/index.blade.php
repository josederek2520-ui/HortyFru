<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2 text-sm font-medium text-brand-500 dark:text-brand-400">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16v13H4zM7 7V4h10v3M4 11h16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                Operaciones
            </div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Compras</h1>
            <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">Registra cada compra por proveedor y el empleado responsable. Los productos se agregarán en el siguiente módulo.</p>
        </div>

        @can('create', App\Models\Compra::class)
            <button type="button" wire:click="openCreateModal" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                Nueva compra
            </button>
        @endcan
    </div>

    <section aria-labelledby="purchases-table-heading" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <h2 id="purchases-table-heading" class="sr-only">Listado de compras</h2>

        <div class="overflow-x-auto border-b border-gray-200 dark:border-gray-800">
            <div class="flex min-w-[76rem] items-center gap-3 p-4 xl:min-w-0">
                <div class="grid w-[34rem] shrink-0 grid-cols-4 gap-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-900" role="group" aria-label="Filtrar compras por estado">
                    <button type="button" wire:click="$set('status', 'all')" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $status === 'all' ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">Todas <span class="text-xs">{{ $this->statusCounts['all'] }}</span></button>
                    @foreach (App\Enums\EstadoCompra::cases() as $estado)
                        <button type="button" wire:click="$set('status', '{{ $estado->value }}')" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $status === $estado->value ? 'bg-white text-brand-600 shadow-theme-xs dark:bg-gray-800 dark:text-brand-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">{{ $estado->label() }} <span class="text-xs">{{ $this->statusCounts[$estado->value] }}</span></button>
                    @endforeach
                </div>

                <label class="relative block min-w-64 flex-1">
                    <span class="sr-only">Buscar compras</span>
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7" /><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
                    <input type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar por código, proveedor o comprador..." class="h-11 w-full rounded-xl border border-gray-300 bg-transparent py-2.5 pl-11 pr-4 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white dark:placeholder:text-gray-500" />
                </label>

                <div class="w-48 shrink-0">
                    <x-panel.form.date-picker
                        id="purchase-date-filter"
                        model="purchaseDate"
                        :default-date="$purchaseDate ?: null"
                        placeholder="dd/mm/aaaa"
                        aria-label="Filtrar compras por fecha"
                        :disable-mobile="true"
                        compact
                        wire:key="purchase-date-filter-{{ $purchaseDate ?: 'empty' }}"
                    />
                </div>

                <button type="button" wire:click="clearFilters" @disabled($search === '' && $status === 'all' && $purchaseDate === '') class="h-11 shrink-0 rounded-xl border border-gray-300 px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Limpiar</button>
            </div>
        </div>

        @if ($this->purchases->isEmpty())
            <div class="flex flex-col items-center gap-3 px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16v13H4zM7 7V4h10v3M4 11h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg></span>
                <div><h3 class="font-semibold text-gray-800 dark:text-white/90">No encontramos compras</h3><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Prueba con otros filtros o registra la primera compra.</p></div>
                @if ($search !== '' || $status !== 'all' || $purchaseDate !== '')<button type="button" wire:click="clearFilters" class="text-sm font-semibold text-brand-500">Limpiar filtros</button>@endif
            </div>
        @else
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full text-left">
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"><th class="px-5 py-3.5">Compra</th><th class="px-5 py-3.5">Proveedor</th><th class="px-5 py-3.5">Comprador</th><th class="px-5 py-3.5">Fecha</th><th class="px-5 py-3.5 text-right">Total</th><th class="px-5 py-3.5">Estado</th><th class="px-5 py-3.5 text-right">Acciones</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->purchases as $compra)
                            @php($estadoCompra = $compra->estado_compra)
                            <tr wire:key="purchase-row-{{ $compra->id }}" class="transition hover:bg-gray-50/80 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-4"><span class="font-mono text-sm font-semibold tracking-wide text-gray-800 dark:text-white/90">{{ $compra->codigo_compra }}</span></td>
                                <td class="px-5 py-4"><div class="flex flex-col gap-0.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $compra->proveedor->nombre_proveedor }}</span><span class="text-xs text-gray-400">{{ $compra->proveedor->mercado_proveedor ?? 'Mercado sin registrar' }}</span></div></td>
                                <td class="px-5 py-4"><div class="text-sm text-gray-700 dark:text-gray-300">{{ $compra->empleado->nombre_completo }}</div><div class="text-xs text-gray-400">{{ $compra->empleado->cargo_empleado }}</div></td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300"><span class="block">{{ $compra->fecha_compra_local->format('d/m/Y') }}</span><span class="text-xs text-gray-400">{{ $compra->fecha_compra_local->format('H:i') }}</span></td>
                                <td class="px-5 py-4 text-right text-sm font-semibold text-gray-800 dark:text-white/90">Bs {{ number_format((float) $compra->total_compra, 2, ',', '.') }}</td>
                                <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ match ($estadoCompra) { App\Enums\EstadoCompra::Borrador => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400', App\Enums\EstadoCompra::Registrada => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400', App\Enums\EstadoCompra::Cancelada => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' } }}">{{ $estadoCompra->label() }}</span></td>
                                <td class="px-5 py-4"><div class="flex justify-end gap-2"><a href="{{ route('panel.compras.details', $compra) }}" wire:navigate class="inline-flex h-9 items-center rounded-lg bg-brand-50 px-3 text-xs font-semibold text-brand-700 transition hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-400">Productos</a>@can('update', $compra)<button type="button" wire:click="openEditModal({{ $compra->id }})" class="h-9 rounded-lg border border-gray-300 px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Editar</button>@endcan @can('cancel', $compra)<button type="button" wire:click="openCancelModal({{ $compra->id }})" class="h-9 rounded-lg bg-error-50 px-3 text-xs font-semibold text-error-700 transition hover:bg-error-100 dark:bg-error-500/10 dark:text-error-400">Cancelar</button>@endcan</div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-gray-100 lg:hidden dark:divide-gray-800">
                @foreach ($this->purchases as $compra)
                    <article wire:key="purchase-card-{{ $compra->id }}" class="flex flex-col gap-4 p-5">
                        <div class="flex items-start justify-between gap-3"><div><h3 class="font-mono font-semibold tracking-wide text-gray-800 dark:text-white/90">{{ $compra->codigo_compra }}</h3><p class="mt-1 text-sm text-gray-500">{{ $compra->proveedor->nombre_proveedor }}</p></div><span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ match ($compra->estado_compra) { App\Enums\EstadoCompra::Borrador => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400', App\Enums\EstadoCompra::Registrada => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400', App\Enums\EstadoCompra::Cancelada => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' } }}">{{ $compra->estado_compra->label() }}</span></div>
                        <dl class="grid grid-cols-2 gap-3 rounded-xl bg-gray-50 p-3 text-xs dark:bg-gray-900/60"><div><dt class="text-gray-500">Comprador</dt><dd class="mt-1 font-medium text-gray-700 dark:text-gray-300">{{ $compra->empleado->nombre_completo }}</dd></div><div><dt class="text-gray-500">Fecha</dt><dd class="mt-1 font-medium text-gray-700 dark:text-gray-300">{{ $compra->fecha_compra_local->format('d/m/Y H:i') }}</dd></div><div class="col-span-2"><dt class="text-gray-500">Total</dt><dd class="mt-1 font-semibold text-gray-800 dark:text-white/90">Bs {{ number_format((float) $compra->total_compra, 2, ',', '.') }}</dd></div></dl>
                        <div class="grid grid-cols-3 gap-2"><a href="{{ route('panel.compras.details', $compra) }}" wire:navigate class="inline-flex h-10 items-center justify-center rounded-lg bg-brand-50 text-sm font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">Productos</a>@can('update', $compra)<button type="button" wire:click="openEditModal({{ $compra->id }})" class="h-10 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Editar</button>@endcan @can('cancel', $compra)<button type="button" wire:click="openCancelModal({{ $compra->id }})" class="h-10 rounded-lg bg-error-50 text-sm font-semibold text-error-700 dark:bg-error-500/10 dark:text-error-400">Cancelar</button>@endcan</div>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $this->purchases->links() }}</div>
        @endif
    </section>

    <div wire:show="showFormModal" x-cloak x-on:purchase-form-opened.window="$nextTick(() => $refs.purchaseProvider.focus())" class="fixed inset-0 z-99999 flex items-end justify-center overflow-y-auto sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="purchase-form-title">
        <div class="fixed inset-0 bg-gray-950/55 backdrop-blur-sm" aria-hidden="true"></div>
        <div class="relative max-h-[95vh] w-full overflow-y-auto rounded-t-3xl bg-white shadow-theme-xl sm:max-w-2xl sm:rounded-3xl dark:bg-gray-900">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-gray-200 bg-white px-5 py-5 sm:px-6 dark:border-gray-800 dark:bg-gray-900"><div><h2 id="purchase-form-title" class="text-lg font-semibold text-gray-900 dark:text-white">{{ $editingPurchaseId === null ? 'Nueva compra' : 'Editar compra' }}</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">La compra se guardará como borrador hasta agregar sus productos.</p></div><button type="button" wire:click="closeFormModal" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition hover:bg-gray-200 dark:bg-gray-800" aria-label="Cerrar">×</button></div>

            <form wire:submit="save" class="flex flex-col gap-6 p-5 sm:p-6">
                @error('form.compra')<p class="rounded-xl bg-error-50 px-4 py-3 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">{{ $message }}</p>@enderror
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <label class="flex flex-col gap-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Proveedor <span class="text-error-500">*</span></span><select x-ref="purchaseProvider" wire:model="form.proveedor_id" class="h-11 rounded-xl border bg-transparent px-3 text-sm text-gray-800 outline-none focus:ring-3 dark:bg-gray-900 dark:text-white {{ $errors->has('form.proveedor_id') ? 'border-error-400 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-400 focus:ring-brand-500/10 dark:border-gray-700' }}"><option value="">Selecciona un proveedor</option>@foreach ($this->providerOptions as $proveedor)<option value="{{ $proveedor->id }}">{{ $proveedor->nombre_proveedor }}{{ $proveedor->mercado_proveedor ? ' — '.$proveedor->mercado_proveedor : '' }}{{ ! $proveedor->estado_proveedor ? ' (inactivo)' : '' }}</option>@endforeach</select>@error('form.proveedor_id')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>
                    <div class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Empleado comprador</span>
                        @if ($this->formBuyer)
                            <div class="flex min-h-11 items-center gap-3 rounded-xl border px-3 py-2 {{ $this->formBuyer->activo_empleado || $editingPurchaseId !== null ? 'border-success-200 bg-success-50/60 dark:border-success-500/20 dark:bg-success-500/10' : 'border-error-300 bg-error-50 dark:border-error-500/30 dark:bg-error-500/10' }}">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-sm font-bold text-brand-600 shadow-theme-xs dark:bg-gray-800">{{ mb_strtoupper(mb_substr($this->formBuyer->nombre_empleado, 0, 1).mb_substr($this->formBuyer->apellido_empleado, 0, 1)) }}</span>
                                <div class="min-w-0"><p class="truncate text-sm font-semibold text-gray-800 dark:text-white">{{ $this->formBuyer->nombre_completo }}</p><p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $this->formBuyer->cargo_empleado }}</p></div>
                            </div>
                        @else
                            <div class="min-h-11 rounded-xl border border-error-300 bg-error-50 px-3 py-2 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">Tu cuenta no está vinculada a un empleado.</div>
                        @endif
                        @error('form.empleado_id')<span class="text-xs text-error-600">{{ $message }}</span>@enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Fecha y hora de compra <span class="text-error-500">*</span></span>
                        <x-panel.form.date-picker
                            id="purchase-date"
                            model="form.fecha_compra"
                            :default-date="$form->fecha_compra"
                            date-format="Y-m-d\TH:i"
                            display-format="d/m/Y h:i K"
                            :max-date="now((string) config('app.display_timezone', 'America/La_Paz'))->format('Y-m-d\TH:i')"
                            :enable-time="true"
                            :time24hr="false"
                            :disable-mobile="true"
                            sync-event="purchase-date-sync"
                            placeholder="Selecciona fecha y hora"
                            aria-label="Fecha y hora de compra"
                            :invalid="$errors->has('form.fecha_compra')"
                            compact
                        />
                        @error('form.fecha_compra')<span class="text-xs text-error-600">{{ $message }}</span>@enderror
                    </div>
                    <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-500/20 dark:bg-warning-500/10"><span class="block text-sm font-semibold text-warning-800 dark:text-warning-300">Estado inicial: Borrador</span><span class="mt-1 block text-xs leading-5 text-warning-700 dark:text-warning-400">El total permanecerá en Bs 0,00 hasta agregar el detalle de productos.</span></div>
                    <label class="flex flex-col gap-1.5 sm:col-span-2"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones <span class="font-normal text-gray-400">(opcional)</span></span><textarea wire:model="form.observaciones_compra" rows="3" maxlength="2000" placeholder="Calidad, mercado, forma de pago u otra referencia" class="resize-y rounded-xl border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 outline-none focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white"></textarea>@error('form.observaciones_compra')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end dark:border-gray-800"><button type="button" wire:click="closeFormModal" class="h-11 rounded-xl border border-gray-300 px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Cancelar</button><button type="submit" wire:loading.attr="disabled" wire:target="save" class="h-11 rounded-xl bg-brand-500 px-5 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:opacity-70"><span wire:loading.remove wire:target="save">{{ $editingPurchaseId === null ? 'Guardar borrador' : 'Guardar cambios' }}</span><span wire:loading wire:target="save">Guardando...</span></button></div>
            </form>
        </div>
    </div>

    <div wire:show="showCancelModal" x-cloak x-on:keydown.escape.window="$wire.closeCancelModal()" class="fixed inset-0 z-99999 flex items-center justify-center p-5" role="alertdialog" aria-modal="true" aria-labelledby="purchase-cancel-title">
        <button type="button" wire:click="closeCancelModal" class="fixed inset-0 h-full w-full cursor-default bg-gray-950/55 backdrop-blur-sm" aria-label="Cerrar confirmación"></button>
        <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-theme-xl dark:bg-gray-900"><div class="text-center"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-error-50 text-error-600 dark:bg-error-500/10 dark:text-error-400"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 4.4 2.7 18a2 2 0 0 0 1.75 3h15.1a2 2 0 0 0 1.75-3L13.7 4.4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg></span><h2 id="purchase-cancel-title" class="mt-5 text-lg font-semibold text-gray-900 dark:text-white">¿Cancelar {{ $cancellingPurchaseCode }}?</h2><p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">La compra permanecerá en el historial y ya no podrá modificarse.</p></div><label class="mt-5 flex flex-col gap-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">Motivo de cancelación <span class="text-error-500">*</span></span><textarea wire:model="cancellationReason" rows="3" maxlength="500" placeholder="Explica el motivo" class="resize-none rounded-xl border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 outline-none focus:border-error-400 focus:ring-3 focus:ring-error-500/10 dark:border-gray-700 dark:text-white"></textarea>@error('cancellationReason')<span class="text-xs text-error-600">{{ $message }}</span>@enderror @error('cancelacion')<span class="text-xs text-error-600">{{ $message }}</span>@enderror</label><div class="mt-6 grid grid-cols-2 gap-3"><button type="button" wire:click="closeCancelModal" class="h-11 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">Volver</button><button type="button" wire:click="cancelPurchase" wire:loading.attr="disabled" wire:target="cancelPurchase" class="h-11 rounded-xl bg-error-500 text-sm font-semibold text-white transition hover:bg-error-600 disabled:opacity-70"><span wire:loading.remove wire:target="cancelPurchase">Sí, cancelar</span><span wire:loading wire:target="cancelPurchase">Cancelando...</span></button></div></div>
    </div>
</div>
