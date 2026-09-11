<?php

namespace App\Livewire\Panel\MovimientosInventario;

use App\Enums\EstadoMovimientoInventario;
use App\Enums\TipoMovimientoInventario;
use App\Models\Almacen;
use App\Models\MovimientoInventario;
use DateTimeImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $type = 'all';

    #[Url(except: 'all')]
    public string $warehouse = 'all';

    #[Url(except: 'all')]
    public string $status = 'all';

    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    public bool $showDetailModal = false;

    public ?int $selectedMovementId = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', MovimientoInventario::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'type', 'warehouse', 'status', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'type', 'warehouse', 'status', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    public function openDetailModal(int $movementId): void
    {
        $movimiento = MovimientoInventario::query()->findOrFail($movementId);
        Gate::authorize('view', $movimiento);

        $this->selectedMovementId = $movimiento->id;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedMovementId = null;
    }

    #[Computed]
    public function movements(): LengthAwarePaginator
    {
        return $this->filteredMovements()
            ->with([
                'almacen:id,nombre_almacen',
                'registradoPor:id,name',
                'registradoPor.empleado:id,user_id,nombre_empleado,apellido_empleado',
            ])
            ->withCount('detalles')
            ->latest('fecha_movimiento_inventario')
            ->latest('id')
            ->paginate(12);
    }

    #[Computed]
    public function selectedMovement(): ?MovimientoInventario
    {
        if ($this->selectedMovementId === null) {
            return null;
        }

        $movimiento = MovimientoInventario::query()
            ->with([
                'almacen:id,nombre_almacen,direccion_almacen',
                'registradoPor:id,name',
                'registradoPor.empleado:id,user_id,nombre_empleado,apellido_empleado',
                'detalles' => fn ($query) => $query->oldest('id'),
                'detalles.articulo:id,nombre_articulo,categoria_articulo_id,unidad_medida_id',
                'detalles.articulo.categoriaArticulo:id,nombre_categoria_articulo',
                'detalles.lote:id,codigo_lote,fecha_vencimiento_lote',
                'detalles.unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida',
            ])
            ->findOrFail($this->selectedMovementId);

        Gate::authorize('view', $movimiento);

        return $movimiento;
    }

    /** @return list<TipoMovimientoInventario> */
    #[Computed]
    public function movementTypes(): array
    {
        return TipoMovimientoInventario::cases();
    }

    /** @return list<EstadoMovimientoInventario> */
    #[Computed]
    public function movementStatuses(): array
    {
        return EstadoMovimientoInventario::cases();
    }

    /** @return Collection<int, Almacen> */
    #[Computed]
    public function warehouses(): Collection
    {
        return Almacen::query()
            ->select(['id', 'nombre_almacen', 'estado_almacen'])
            ->where(function (Builder $query): void {
                $query->where('estado_almacen', true)
                    ->orWhereHas('movimientosInventario');
            })
            ->orderBy('nombre_almacen')
            ->get();
    }

    #[Computed]
    public function totalMovements(): int
    {
        return MovimientoInventario::query()->count();
    }

    public function render(): View
    {
        return view('livewire.panel.movimientos-inventario.index');
    }

    private function filteredMovements(): Builder
    {
        $tipo = TipoMovimientoInventario::tryFrom($this->type);
        $estado = EstadoMovimientoInventario::tryFrom($this->status);
        $almacenId = ctype_digit($this->warehouse) ? (int) $this->warehouse : null;
        $fechaDesde = $this->validDate($this->dateFrom);
        $fechaHasta = $this->validDate($this->dateTo);

        return MovimientoInventario::query()
            ->select([
                'id',
                'codigo_movimiento_inventario',
                'almacen_id',
                'tipo_movimiento_inventario',
                'fecha_movimiento_inventario',
                'estado_movimiento_inventario',
                'tipo_referencia_movimiento_inventario',
                'referencia_id_movimiento_inventario',
                'observaciones_movimiento_inventario',
                'registrado_por',
                'created_at',
            ])
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('codigo_movimiento_inventario', 'like', $search)
                        ->orWhere('observaciones_movimiento_inventario', 'like', $search)
                        ->orWhereHas('detalles.lote', fn (Builder $query) => $query->where('codigo_lote', 'like', $search))
                        ->orWhereHas('detalles.articulo', fn (Builder $query) => $query->where('nombre_articulo', 'like', $search));
                });
            })
            ->when($tipo !== null, fn (Builder $query) => $query->where('tipo_movimiento_inventario', $tipo->value))
            ->when($estado !== null, fn (Builder $query) => $query->where('estado_movimiento_inventario', $estado->value))
            ->when($almacenId !== null, fn (Builder $query) => $query->where('almacen_id', $almacenId))
            ->when($fechaDesde !== null, fn (Builder $query) => $query->where('fecha_movimiento_inventario', '>=', $this->localDateBoundary($fechaDesde)))
            ->when($fechaHasta !== null, fn (Builder $query) => $query->where('fecha_movimiento_inventario', '<=', $this->localDateBoundary($fechaHasta, true)));
    }

    private function validDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function localDateBoundary(string $date, bool $endOfDay = false): DateTimeImmutable
    {
        $boundary = new DateTimeImmutable(
            $date.' '.($endOfDay ? '23:59:59' : '00:00:00'),
            new \DateTimeZone((string) config('app.display_timezone', 'America/La_Paz')),
        );

        return $boundary->setTimezone(new \DateTimeZone('UTC'));
    }
}
