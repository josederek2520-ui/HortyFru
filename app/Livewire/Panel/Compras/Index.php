<?php

namespace App\Livewire\Panel\Compras;

use App\Actions\Compras\ActualizarCompraAction;
use App\Actions\Compras\CancelarCompraAction;
use App\Actions\Compras\CrearCompraAction;
use App\Enums\EstadoCompra;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\FormularioCompra;
use App\Models\Compra;
use App\Models\Empleado;
use App\Models\Proveedor;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    public FormularioCompra $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    #[Url(as: 'fecha', except: '')]
    public string $purchaseDate = '';

    public bool $showFormModal = false;

    public bool $showCancelModal = false;

    public ?int $editingPurchaseId = null;

    public ?int $cancellingPurchaseId = null;

    public string $cancellingPurchaseCode = '';

    public string $cancellationReason = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Compra::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPurchaseDate(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'purchaseDate');
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', Compra::class);
        $this->editingPurchaseId = null;
        $this->form->limpiar();
        $this->showFormModal = true;
        $this->dispatch('purchase-form-opened');
        $this->syncPurchaseDatePicker();
    }

    public function openEditModal(int $purchaseId): void
    {
        $compra = Compra::query()->findOrFail($purchaseId);
        Gate::authorize('update', $compra);
        $this->editingPurchaseId = $compra->id;
        $this->form->limpiar();
        $this->form->llenarDesde($compra);
        $this->showFormModal = true;
        $this->dispatch('purchase-form-opened');
        $this->syncPurchaseDatePicker();
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingPurchaseId = null;
        $this->form->limpiar();
    }

    public function save(
        CrearCompraAction $crearCompra,
        ActualizarCompraAction $actualizarCompra,
    ): void {
        /** @var User $usuario */
        $usuario = auth()->user();

        if ($this->editingPurchaseId === null) {
            Gate::authorize('create', Compra::class);
            $compra = $crearCompra($this->form->validarParaCrear(), $usuario);
            $titulo = 'Compra creada';
            $mensaje = "La compra {$compra->codigo_compra} fue guardada como borrador.";
            $tipo = 'success';
        } else {
            $compra = Compra::query()->findOrFail($this->editingPurchaseId);
            Gate::authorize('update', $compra);
            $compra = $actualizarCompra($compra, $this->form->validarParaActualizar($compra), $usuario);
            $titulo = 'Compra actualizada';
            $mensaje = "Los datos de {$compra->codigo_compra} fueron actualizados.";
            $tipo = 'info';
        }

        $this->closeFormModal();
        $this->resetPage();
        $this->toast($titulo, $mensaje, $tipo);
    }

    public function openCancelModal(int $purchaseId): void
    {
        $compra = Compra::query()->findOrFail($purchaseId);
        Gate::authorize('cancel', $compra);
        $this->cancellingPurchaseId = $compra->id;
        $this->cancellingPurchaseCode = $compra->codigo_compra;
        $this->cancellationReason = '';
        $this->resetValidation('cancellationReason');
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
        $this->reset('cancellingPurchaseId', 'cancellingPurchaseCode', 'cancellationReason');
        $this->resetValidation('cancellationReason');
    }

    public function cancelPurchase(CancelarCompraAction $cancelarCompra): void
    {
        $this->cancellationReason = Str::squish($this->cancellationReason);
        $this->validate([
            'cancellationReason' => ['required', 'string', 'min:5', 'max:500', 'not_regex:/[<>\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
        ], [
            'cancellationReason.required' => 'Explica por qué se cancela la compra.',
            'cancellationReason.min' => 'El motivo debe tener al menos 5 caracteres.',
            'cancellationReason.not_regex' => 'El motivo contiene caracteres no permitidos.',
        ]);

        $compra = Compra::query()->findOrFail($this->cancellingPurchaseId);
        Gate::authorize('cancel', $compra);
        /** @var User $usuario */
        $usuario = auth()->user();
        $cancelarCompra($compra, $this->cancellationReason, $usuario);
        $codigo = $compra->codigo_compra;
        $this->closeCancelModal();
        $this->toast('Compra cancelada', "La compra {$codigo} fue cancelada y conserva su historial.", 'warning');
    }

    #[Computed]
    public function purchases(): LengthAwarePaginator
    {
        $estado = EstadoCompra::tryFrom($this->status);
        $fechaValida = preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->purchaseDate) === 1;
        $zonaHoraria = (string) config('app.display_timezone', 'America/La_Paz');
        $inicioFecha = $fechaValida
            ? CarbonImmutable::createFromFormat('!Y-m-d', $this->purchaseDate, $zonaHoraria)->startOfDay()->utc()
            : null;
        $finFecha = $inicioFecha?->addDay();

        return Compra::query()
            ->with([
                'proveedor:id,nombre_proveedor,mercado_proveedor',
                'empleado:id,nombre_empleado,apellido_empleado,cargo_empleado',
                'registradoPor:id,name,email',
                'registradoPor.empleado:id,user_id,nombre_empleado,apellido_empleado',
            ])
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('codigo_compra', 'like', "%{$this->search}%")
                        ->orWhereHas('proveedor', fn (Builder $query): Builder => $query->where('nombre_proveedor', 'like', "%{$this->search}%"))
                        ->orWhereHas('empleado', function (Builder $query): void {
                            $query->where('nombre_empleado', 'like', "%{$this->search}%")
                                ->orWhere('apellido_empleado', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($estado !== null, fn (Builder $query): Builder => $query->where('estado_compra', $estado))
            ->when($inicioFecha !== null, fn (Builder $query): Builder => $query
                ->where('fecha_compra', '>=', $inicioFecha)
                ->where('fecha_compra', '<', $finFecha))
            ->latest('fecha_compra')
            ->paginate(10);
    }

    /** @return array{all: int, BORRADOR: int, REGISTRADA: int, CANCELADA: int} */
    #[Computed]
    public function statusCounts(): array
    {
        $cantidades = Compra::query()
            ->selectRaw('estado_compra, COUNT(*) as total')
            ->groupBy('estado_compra')
            ->pluck('total', 'estado_compra');

        return [
            'all' => (int) $cantidades->sum(),
            EstadoCompra::Borrador->value => (int) ($cantidades[EstadoCompra::Borrador->value] ?? 0),
            EstadoCompra::Registrada->value => (int) ($cantidades[EstadoCompra::Registrada->value] ?? 0),
            EstadoCompra::Cancelada->value => (int) ($cantidades[EstadoCompra::Cancelada->value] ?? 0),
        ];
    }

    #[Computed]
    public function providerOptions(): Collection
    {
        $proveedorActualId = $this->editingPurchaseId === null
            ? null
            : Compra::query()->whereKey($this->editingPurchaseId)->value('proveedor_id');

        return Proveedor::query()
            ->select(['id', 'nombre_proveedor', 'mercado_proveedor', 'estado_proveedor'])
            ->where(function (Builder $query) use ($proveedorActualId): void {
                $query->where('estado_proveedor', true)
                    ->when($proveedorActualId !== null, fn (Builder $query): Builder => $query->orWhere('id', $proveedorActualId));
            })
            ->orderBy('nombre_proveedor')
            ->get();
    }

    #[Computed]
    public function formBuyer(): ?Empleado
    {
        if ($this->editingPurchaseId !== null) {
            return Compra::query()
                ->with('empleado:id,user_id,nombre_empleado,apellido_empleado,cargo_empleado,activo_empleado')
                ->find($this->editingPurchaseId)
                ?->empleado;
        }

        return Empleado::query()
            ->select(['id', 'user_id', 'nombre_empleado', 'apellido_empleado', 'cargo_empleado', 'activo_empleado'])
            ->where('user_id', auth()->id())
            ->first();
    }

    public function render(): View
    {
        return view('livewire.panel.compras.index');
    }

    private function syncPurchaseDatePicker(): void
    {
        $this->dispatch(
            'purchase-date-sync',
            value: $this->form->fecha_compra,
            maxDate: now((string) config('app.display_timezone', 'America/La_Paz'))->format('Y-m-d\TH:i'),
        );
    }
}
