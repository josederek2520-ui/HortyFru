<?php

namespace App\Livewire\Panel\Recepciones;

use App\Actions\Recepciones\ActualizarRecepcionAction;
use App\Actions\Recepciones\CancelarRecepcionAction;
use App\Actions\Recepciones\CrearRecepcionAction;
use App\Enums\EstadoCompra;
use App\Enums\EstadoRecepcion;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\FormularioRecepcion;
use App\Models\Almacen;
use App\Models\Compra;
use App\Models\Empleado;
use App\Models\Recepcion;
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

    public FormularioRecepcion $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    #[Url(as: 'fecha', except: '')]
    public string $receptionDate = '';

    public bool $showFormModal = false;

    public bool $showCancelModal = false;

    public ?int $editingReceptionId = null;

    public ?int $cancellingReceptionId = null;

    public string $cancellingReceptionCode = '';

    public string $cancellationReason = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Recepcion::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedReceptionDate(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'receptionDate');
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', Recepcion::class);
        $this->editingReceptionId = null;
        $this->form->limpiar();
        $this->showFormModal = true;
        $this->dispatch('reception-form-opened');
        $this->syncReceptionDatePicker();
    }

    public function openEditModal(int $receptionId): void
    {
        $recepcion = Recepcion::query()->findOrFail($receptionId);
        Gate::authorize('update', $recepcion);
        $this->editingReceptionId = $recepcion->id;
        $this->form->limpiar();
        $this->form->llenarDesde($recepcion);
        $this->showFormModal = true;
        $this->dispatch('reception-form-opened');
        $this->syncReceptionDatePicker();
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingReceptionId = null;
        $this->form->limpiar();
    }

    public function save(
        CrearRecepcionAction $crearRecepcion,
        ActualizarRecepcionAction $actualizarRecepcion,
    ): void {
        /** @var User $usuario */
        $usuario = auth()->user();

        if ($this->editingReceptionId === null) {
            Gate::authorize('create', Recepcion::class);
            $recepcion = $crearRecepcion($this->form->validarParaCrear(), $usuario);
            $titulo = 'Recepción creada';
            $mensaje = "La recepción {$recepcion->codigo_recepcion} fue guardada como borrador.";
            $tipo = 'success';
        } else {
            $recepcion = Recepcion::query()->findOrFail($this->editingReceptionId);
            Gate::authorize('update', $recepcion);
            $recepcion = $actualizarRecepcion(
                $recepcion,
                $this->form->validarParaActualizar($recepcion),
                $usuario,
            );
            $titulo = 'Recepción actualizada';
            $mensaje = "Los datos de {$recepcion->codigo_recepcion} fueron actualizados.";
            $tipo = 'info';
        }

        $this->closeFormModal();
        $this->resetPage();
        $this->toast($titulo, $mensaje, $tipo);
    }

    public function openCancelModal(int $receptionId): void
    {
        $recepcion = Recepcion::query()->findOrFail($receptionId);
        Gate::authorize('cancel', $recepcion);
        $this->cancellingReceptionId = $recepcion->id;
        $this->cancellingReceptionCode = $recepcion->codigo_recepcion;
        $this->cancellationReason = '';
        $this->resetValidation('cancellationReason');
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
        $this->reset('cancellingReceptionId', 'cancellingReceptionCode', 'cancellationReason');
        $this->resetValidation('cancellationReason');
    }

    public function cancelReception(CancelarRecepcionAction $cancelarRecepcion): void
    {
        $this->cancellationReason = Str::squish($this->cancellationReason);
        $this->validate([
            'cancellationReason' => ['required', 'string', 'min:5', 'max:500', 'not_regex:/[<>\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
        ], [
            'cancellationReason.required' => 'Explica por qué se cancela la recepción.',
            'cancellationReason.min' => 'El motivo debe tener al menos 5 caracteres.',
            'cancellationReason.max' => 'El motivo no puede superar los 500 caracteres.',
            'cancellationReason.not_regex' => 'El motivo contiene caracteres no permitidos.',
        ]);

        $recepcion = Recepcion::query()->findOrFail($this->cancellingReceptionId);
        Gate::authorize('cancel', $recepcion);
        /** @var User $usuario */
        $usuario = auth()->user();
        $cancelarRecepcion($recepcion, $this->cancellationReason, $usuario);
        $codigo = $recepcion->codigo_recepcion;
        $this->closeCancelModal();
        $this->toast('Recepción cancelada', "La recepción {$codigo} fue cancelada y conserva su historial.", 'warning');
    }

    #[Computed]
    public function receptions(): LengthAwarePaginator
    {
        $estado = EstadoRecepcion::tryFrom($this->status);
        $fechaValida = preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->receptionDate) === 1;
        $zonaHoraria = (string) config('app.display_timezone', 'America/La_Paz');
        $inicioFecha = $fechaValida
            ? CarbonImmutable::createFromFormat('!Y-m-d', $this->receptionDate, $zonaHoraria)->startOfDay()->utc()
            : null;
        $finFecha = $inicioFecha?->addDay();

        return Recepcion::query()
            ->with([
                'compra:id,codigo_compra,proveedor_id,fecha_compra,estado_compra',
                'compra.proveedor:id,nombre_proveedor',
                'almacen:id,nombre_almacen,direccion_almacen',
                'empleado:id,nombre_empleado,apellido_empleado,cargo_empleado',
                'registradoPor:id,name,email',
                'registradoPor.empleado:id,user_id,nombre_empleado,apellido_empleado',
            ])
            ->when($this->search !== '', function (Builder $query): void {
                $search = "%{$this->search}%";
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('codigo_recepcion', 'like', $search)
                        ->orWhereHas('compra', function (Builder $query) use ($search): void {
                            $query->where('codigo_compra', 'like', $search)
                                ->orWhereHas('proveedor', fn (Builder $query): Builder => $query->where('nombre_proveedor', 'like', $search));
                        })
                        ->orWhereHas('almacen', fn (Builder $query): Builder => $query->where('nombre_almacen', 'like', $search))
                        ->orWhereHas('empleado', function (Builder $query) use ($search): void {
                            $query->where('nombre_empleado', 'like', $search)
                                ->orWhere('apellido_empleado', 'like', $search);
                        });
                });
            })
            ->when($estado !== null, fn (Builder $query): Builder => $query->where('estado_recepcion', $estado))
            ->when($inicioFecha !== null, fn (Builder $query): Builder => $query
                ->where('fecha_recepcion', '>=', $inicioFecha)
                ->where('fecha_recepcion', '<', $finFecha))
            ->latest('fecha_recepcion')
            ->latest('id')
            ->paginate(10);
    }

    /** @return array{all: int, BORRADOR: int, CONFIRMADA: int, CANCELADA: int} */
    #[Computed]
    public function statusCounts(): array
    {
        $cantidades = Recepcion::query()
            ->selectRaw('estado_recepcion, COUNT(*) as total')
            ->groupBy('estado_recepcion')
            ->pluck('total', 'estado_recepcion');

        return [
            'all' => (int) $cantidades->sum(),
            EstadoRecepcion::Borrador->value => (int) ($cantidades[EstadoRecepcion::Borrador->value] ?? 0),
            EstadoRecepcion::Confirmada->value => (int) ($cantidades[EstadoRecepcion::Confirmada->value] ?? 0),
            EstadoRecepcion::Cancelada->value => (int) ($cantidades[EstadoRecepcion::Cancelada->value] ?? 0),
        ];
    }

    #[Computed]
    public function purchaseOptions(): Collection
    {
        $compraActualId = $this->editingReceptionId === null
            ? null
            : Recepcion::query()->whereKey($this->editingReceptionId)->value('compra_id');

        return Compra::query()
            ->select(['id', 'codigo_compra', 'proveedor_id', 'fecha_compra', 'estado_compra', 'total_compra'])
            ->with('proveedor:id,nombre_proveedor')
            ->where(function (Builder $query) use ($compraActualId): void {
                $query->where('estado_compra', EstadoCompra::Registrada)
                    ->when($compraActualId !== null, fn (Builder $query): Builder => $query->orWhere('id', $compraActualId));
            })
            ->latest('fecha_compra')
            ->limit(150)
            ->get();
    }

    #[Computed]
    public function warehouseOptions(): Collection
    {
        $almacenActualId = $this->editingReceptionId === null
            ? null
            : Recepcion::query()->whereKey($this->editingReceptionId)->value('almacen_id');

        return Almacen::query()
            ->select(['id', 'nombre_almacen', 'direccion_almacen', 'estado_almacen'])
            ->where(function (Builder $query) use ($almacenActualId): void {
                $query->where('estado_almacen', true)
                    ->when($almacenActualId !== null, fn (Builder $query): Builder => $query->orWhere('id', $almacenActualId));
            })
            ->orderBy('nombre_almacen')
            ->get();
    }

    #[Computed]
    public function formReceiver(): ?Empleado
    {
        if ($this->editingReceptionId !== null) {
            return Recepcion::query()
                ->with('empleado:id,user_id,nombre_empleado,apellido_empleado,cargo_empleado,activo_empleado')
                ->find($this->editingReceptionId)
                ?->empleado;
        }

        return Empleado::query()
            ->select(['id', 'user_id', 'nombre_empleado', 'apellido_empleado', 'cargo_empleado', 'activo_empleado'])
            ->where('user_id', auth()->id())
            ->first();
    }

    public function render(): View
    {
        return view('livewire.panel.recepciones.index');
    }

    private function syncReceptionDatePicker(): void
    {
        $this->dispatch(
            'reception-date-sync',
            value: $this->form->fecha_recepcion,
            maxDate: now((string) config('app.display_timezone', 'America/La_Paz'))->format('Y-m-d\TH:i'),
        );
    }
}
