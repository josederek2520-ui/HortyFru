<?php

namespace App\Livewire\Panel\Lotes;

use App\Actions\Lotes\ActualizarLoteAction;
use App\Actions\Lotes\CambiarEstadoLoteAction;
use App\Enums\EstadoLote;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Forms\FormularioLote;
use App\Models\Lote;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
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

    public FormularioLote $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    public bool $showEditModal = false;

    public bool $showStatusModal = false;

    public ?int $editingLotId = null;

    public ?int $statusLotId = null;

    public string $statusLotCode = '';

    public string $statusLotArticle = '';

    public string $statusLotState = '';

    public string $motivoBloqueo = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Lote::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status');
        $this->resetPage();
    }

    public function openEditModal(int $lotId): void
    {
        $lote = Lote::query()->with('articulo:id,nombre_articulo')->findOrFail($lotId);
        Gate::authorize('update', $lote);

        $this->editingLotId = $lote->id;
        $this->form->limpiar();
        $this->form->llenarDesde($lote);
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingLotId = null;
        $this->form->limpiar();
    }

    public function save(ActualizarLoteAction $actualizarLote): void
    {
        $lote = Lote::query()->findOrFail($this->editingLotId);
        Gate::authorize('update', $lote);

        $actualizarLote($lote, $this->form->validarParaActualizar($lote));
        $this->closeEditModal();
        $this->toast(
            'Lote actualizado',
            "La información de {$lote->codigo_lote} fue actualizada.",
            'info',
        );
    }

    public function openStatusModal(int $lotId): void
    {
        $lote = Lote::query()->with('articulo:id,nombre_articulo')->findOrFail($lotId);
        Gate::authorize('changeStatus', $lote);

        $this->statusLotId = $lote->id;
        $this->statusLotCode = $lote->codigo_lote;
        $this->statusLotArticle = $lote->articulo->nombre_articulo;
        $this->statusLotState = $lote->estado_lote->value;
        $this->motivoBloqueo = '';
        $this->resetValidation();
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->reset(
            'statusLotId',
            'statusLotCode',
            'statusLotArticle',
            'statusLotState',
            'motivoBloqueo',
        );
        $this->resetValidation();
    }

    public function changeStatus(CambiarEstadoLoteAction $cambiarEstadoLote): void
    {
        $lote = Lote::query()->findOrFail($this->statusLotId);
        Gate::authorize('changeStatus', $lote);
        /** @var User $usuario */
        $usuario = auth()->user();

        if ($lote->estado_lote === EstadoLote::Disponible) {
            $this->motivoBloqueo = Str::squish($this->motivoBloqueo);
            $this->validate([
                'motivoBloqueo' => ['required', 'string', 'max:500', 'not_regex:/[<>\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
            ], [
                'motivoBloqueo.required' => 'Explica por qué se bloqueará el lote.',
                'motivoBloqueo.max' => 'El motivo no debe superar los 500 caracteres.',
                'motivoBloqueo.not_regex' => 'El motivo contiene caracteres no permitidos.',
            ]);
        }

        $lote = $cambiarEstadoLote($lote, $this->motivoBloqueo, $usuario);
        $this->closeStatusModal();
        $this->toast(
            $lote->estado_lote === EstadoLote::Bloqueado ? 'Lote bloqueado' : 'Lote habilitado',
            $lote->estado_lote === EstadoLote::Bloqueado
                ? 'El lote no estará disponible para preparación ni despacho.'
                : 'El lote vuelve a estar disponible para movimientos FEFO.',
            $lote->estado_lote === EstadoLote::Bloqueado ? 'warning' : 'success',
        );
    }

    #[Computed]
    public function lots(): LengthAwarePaginator
    {
        $estado = EstadoLote::tryFrom($this->status);

        return Lote::query()
            ->select([
                'id',
                'codigo_lote',
                'articulo_id',
                'tipo_origen_lote',
                'fecha_ingreso_lote',
                'fecha_vencimiento_lote',
                'calidad_lote',
                'estado_lote',
                'motivo_bloqueo_lote',
                'observaciones_lote',
                'created_at',
            ])
            ->with([
                'articulo:id,nombre_articulo,categoria_articulo_id,unidad_medida_id',
                'articulo.categoriaArticulo:id,nombre_categoria_articulo',
                'articulo.unidadMedida:id,nombre_unidad_medida,abreviatura_unidad_medida',
            ])
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('codigo_lote', 'like', $search)
                        ->orWhere('calidad_lote', 'like', $search)
                        ->orWhereHas('articulo', function ($query) use ($search): void {
                            $query->where('nombre_articulo', 'like', $search)
                                ->orWhereHas('categoriaArticulo', fn ($query) => $query->where('nombre_categoria_articulo', 'like', $search));
                        });
                });
            })
            ->when($estado !== null, fn ($query) => $query->where('estado_lote', $estado->value))
            ->enOrdenFefo()
            ->paginate(10);
    }

    /** @return array<string, int> */
    #[Computed]
    public function statusCounts(): array
    {
        $counts = Lote::query()
            ->selectRaw('estado_lote, COUNT(*) as total')
            ->groupBy('estado_lote')
            ->pluck('total', 'estado_lote');

        return collect(EstadoLote::cases())
            ->mapWithKeys(fn (EstadoLote $estado): array => [
                $estado->value => (int) ($counts[$estado->value] ?? 0),
            ])
            ->all();
    }

    #[Computed]
    public function totalLots(): int
    {
        return Lote::query()->count();
    }

    public function render(): View
    {
        return view('livewire.panel.lotes.index');
    }
}
