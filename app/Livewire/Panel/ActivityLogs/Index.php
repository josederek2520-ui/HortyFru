<?php

namespace App\Livewire\Panel\ActivityLogs;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoCompra;
use App\Enums\EstadoLote;
use App\Enums\EstadoMovimientoInventario;
use App\Enums\EstadoPedido;
use App\Enums\EstadoRecepcion;
use App\Enums\PermissionName;
use App\Enums\PrecioPorDetalleCompra;
use App\Enums\TipoEquivalenciaPresentacionArticulo;
use App\Enums\TipoMovimientoInventario;
use App\Enums\TipoOrigenLote;
use App\Enums\TipoReferenciaMovimientoInventario;
use App\Enums\UsoPresentacionArticulo;
use App\Models\Activity;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $module = 'all';

    #[Url(except: 'all')]
    public string $event = 'all';

    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    public bool $showDetailModal = false;

    public ?int $selectedActivityId = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Activity::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedModule(): void
    {
        $this->resetPage();
    }

    public function updatedEvent(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'module', 'event', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    public function openDetail(int $activityId): void
    {
        $activity = $this->activityWithCauser()->findOrFail($activityId);
        Gate::authorize('view', $activity);

        $this->selectedActivityId = $activity->id;
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedActivityId = null;
        unset($this->selectedActivity);
    }

    public function export(): StreamedResponse
    {
        Gate::authorize('export', Activity::class);

        $query = $this->activitiesForExportQuery();

        return response()->streamDownload(function () use ($query): void {
            $stream = fopen('php://output', 'w');

            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");

            fputcsv($stream, [
                'ID',
                'Fecha',
                'Módulo',
                'Evento',
                'Descripción',
                'Responsable',
                'Registro afectado',
                'Propiedades',
            ], escape: '\\');

            $query->chunkById(500, function (Collection $activities) use ($stream): void {
                foreach ($activities as $activity) {
                    fputcsv($stream, [
                        $activity->id,
                        $activity->local_created_at?->format('d/m/Y H:i:s'),
                        $this->safeCsvValue($activity->module_label),
                        $this->safeCsvValue($activity->event_label),
                        $this->safeCsvValue($activity->description),
                        $this->safeCsvValue($activity->causer_name),
                        $this->safeCsvValue($activity->subject_label),
                        $this->safeCsvValue($this->formatPropertiesForExport($activity)),
                    ], escape: '\\');
                }
            });

            fclose($stream);
        }, 'registro-actividad-'.now($this->displayTimezone())->format('d-m-Y-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(): StreamedResponse
    {
        Gate::authorize('export', Activity::class);

        $generatedAt = now($this->displayTimezone());
        $authenticatedUser = auth()->user();
        $activities = $this->activitiesForExportQuery()->get();

        $pdf = Pdf::loadView('pdf.activity-report', [
            'activities' => $activities,
            'filters' => $this->reportFilters(),
            'formatProperties' => fn (Activity $activity): string => $this->formatPropertiesForExport($activity),
            'generatedAt' => $generatedAt,
            'generatedBy' => $authenticatedUser instanceof User ? $authenticatedUser->display_name : 'Sistema',
        ])->setPaper('letter', 'landscape');

        $pdf->render();
        $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans');
        $pdf->getDomPDF()->getCanvas()->page_text(
            690,
            588,
            'Página {PAGE_NUM} de {PAGE_COUNT}',
            $font,
            7,
            [0.42, 0.45, 0.50],
        );

        return response()->streamDownload(
            static function () use ($pdf): void {
                echo $pdf->output();
            },
            'registro-actividad-'.$generatedAt->format('d-m-Y-His').'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        return $this->filteredActivities()
            ->select([
                'id',
                'log_name',
                'description',
                'subject_type',
                'subject_id',
                'event',
                'causer_type',
                'causer_id',
                'properties',
                'created_at',
            ])
            ->with(['causer' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    User::class => ['empleado:id,user_id,nombre_empleado,apellido_empleado'],
                ]);
            }])
            ->latest('id')
            ->paginate(15);
    }

    #[Computed]
    public function selectedActivity(): ?Activity
    {
        if ($this->selectedActivityId === null) {
            return null;
        }

        $activity = $this->activityWithCauser()->findOrFail($this->selectedActivityId);
        Gate::authorize('view', $activity);

        return $activity;
    }

    #[Computed]
    public function totalActivities(): int
    {
        return Activity::query()->count();
    }

    /** @return list<ActivityLogName> */
    #[Computed]
    public function modules(): array
    {
        return ActivityLogName::cases();
    }

    /** @return list<ActivityEvent> */
    #[Computed]
    public function events(): array
    {
        return ActivityEvent::cases();
    }

    public function propertyLabel(string $property): string
    {
        return match ($property) {
            'ip_address' => 'Dirección IP',
            'route' => 'Acción realizada',
            'method' => 'Tipo de solicitud',
            'user_agent' => 'Navegador y dispositivo',
            'email' => 'Correo electrónico',
            'guard' => 'Canal de acceso',
            'remember' => 'Recordar sesión',
            'reason' => 'Motivo',
            'user_id' => 'Usuario asociado',
            'nombre_empleado' => 'Nombre',
            'apellido_empleado' => 'Apellido',
            'ci_empleado' => 'Carnet de identidad',
            'telefono_empleado' => 'Teléfono',
            'direccion_empleado' => 'Dirección',
            'cargo_empleado' => 'Cargo',
            'fecha_ingreso_empleado' => 'Fecha de ingreso',
            'activo_empleado' => 'Estado laboral',
            'razon_social' => 'Razón social',
            'nit' => 'NIT',
            'telefono_cliente' => 'Teléfono',
            'email_cliente' => 'Correo electrónico',
            'activo_cliente' => 'Estado del cliente',
            'cliente_id' => 'Cliente',
            'nombre_sucursal' => 'Nombre de la sucursal',
            'direccion_sucursal' => 'Dirección',
            'telefono_sucursal' => 'Teléfono',
            'referencia_sucursal' => 'Referencia',
            'activo_sucursal' => 'Estado de la sucursal',
            'nombre_proveedor' => 'Nombre del proveedor',
            'telefono_proveedor' => 'Teléfono',
            'mercado_proveedor' => 'Mercado',
            'direccion_proveedor' => 'Dirección',
            'observacion_proveedor' => 'Observación',
            'estado_proveedor' => 'Estado del proveedor',
            'placa_vehiculo' => 'Placa',
            'marca_vehiculo' => 'Marca',
            'tipo_vehiculo' => 'Tipo de vehículo',
            'estado_vehiculo' => 'Estado del vehículo',
            'nombre_almacen' => 'Nombre del almacén',
            'direccion_almacen' => 'Dirección',
            'estado_almacen' => 'Estado del almacén',
            'nombre_categoria_articulo' => 'Nombre de la categoría',
            'estado_categoria_articulo' => 'Estado de la categoría',
            'nombre_unidad_medida' => 'Nombre de la unidad de medida',
            'abreviatura_unidad_medida' => 'Abreviatura',
            'estado_unidad_medida' => 'Estado de la unidad de medida',
            'nombre_articulo' => 'Nombre del producto',
            'categoria_articulo_id' => 'Categoría',
            'unidad_medida_id' => 'Unidad de medida',
            'imagen_articulo' => 'Imagen del producto',
            'estado_articulo' => 'Estado del producto',
            'articulo_id' => 'Producto',
            'nombre_presentacion_articulo' => 'Nombre de la presentación',
            'uso_presentacion_articulo' => 'Uso de la presentación',
            'tipo_equivalencia_presentacion_articulo' => 'Tipo de equivalencia',
            'equivalencia_base_presentacion_articulo' => 'Equivalencia base',
            'permite_fraccion_presentacion_articulo' => 'Permite fracción',
            'predeterminada_pedido_presentacion_articulo' => 'Predeterminada para pedido',
            'predeterminada_compra_presentacion_articulo' => 'Predeterminada para compra',
            'estado_presentacion_articulo' => 'Estado de la presentación',
            'activo_usuario' => 'Estado de la cuenta',
            'codigo_pedido' => 'Código del pedido',
            'sucursal_id' => 'Sucursal',
            'fecha_pedido' => 'Fecha de registro',
            'fecha_requerida_pedido' => 'Fecha requerida',
            'estado_pedido' => 'Estado del pedido',
            'observaciones_pedido' => 'Observaciones',
            'registrado_por' => 'Registrado por',
            'preparado_por' => 'Preparado por',
            'preparado_en' => 'Fecha de preparación',
            'cancelado_por' => 'Cancelado por',
            'cancelado_en' => 'Fecha de cancelación',
            'motivo_cancelacion_pedido' => 'Motivo de cancelación',
            'cantidad_items_pedido' => 'Cantidad de productos',
            'detalles_pedido' => 'Detalle de productos',
            'codigo_compra' => 'Código de la compra',
            'proveedor_id' => 'Proveedor',
            'empleado_id' => 'Empleado comprador',
            'fecha_compra' => 'Fecha de compra',
            'estado_compra' => 'Estado de la compra',
            'total_compra' => 'Total de la compra',
            'observaciones_compra' => 'Observaciones',
            'motivo_cancelacion_compra' => 'Motivo de cancelación',
            'cantidad_items_compra' => 'Cantidad de productos',
            'detalle_compra_id' => 'Línea de la compra',
            'cantidad_presentaciones_detalle_compra' => 'Cantidad de presentaciones',
            'equivalencia_base_aplicada_detalle_compra' => 'Equivalencia aplicada',
            'cantidad_real_detalle_compra' => 'Cantidad real',
            'precio_unitario_detalle_compra' => 'Precio unitario',
            'precio_por_detalle_compra' => 'Precio aplicado por',
            'subtotal_detalle_compra' => 'Total pagado del producto',
            'observaciones_detalle_compra' => 'Observación del producto',
            'codigo_lote' => 'Código del lote',
            'tipo_origen_lote' => 'Origen del lote',
            'fecha_ingreso_lote' => 'Fecha de ingreso',
            'fecha_vencimiento_lote' => 'Fecha de vencimiento',
            'calidad_lote' => 'Calidad o condición',
            'estado_lote' => 'Estado del lote',
            'motivo_bloqueo_lote' => 'Motivo del bloqueo',
            'observaciones_lote' => 'Observaciones del lote',
            'codigo_movimiento_inventario' => 'Código del movimiento',
            'almacen_id' => 'Almacén',
            'tipo_movimiento_inventario' => 'Tipo de movimiento',
            'fecha_movimiento_inventario' => 'Fecha del movimiento',
            'estado_movimiento_inventario' => 'Estado del movimiento',
            'tipo_referencia_movimiento_inventario' => 'Tipo de origen',
            'referencia_id_movimiento_inventario' => 'Registro de origen',
            'observaciones_movimiento_inventario' => 'Observaciones',
            'movimiento_inventario_id' => 'Movimiento de inventario',
            'lote_id' => 'Lote',
            'cantidad_movimiento_inventario' => 'Cantidad en unidad base',
            'detalles_movimiento_inventario' => 'Productos y lotes',
            'codigo_recepcion' => 'Código de la recepción',
            'compra_id' => 'Compra relacionada',
            'fecha_recepcion' => 'Fecha de recepción',
            'estado_recepcion' => 'Estado de la recepción',
            'observaciones_recepcion' => 'Observaciones',
            'confirmado_por' => 'Confirmado por',
            'confirmado_en' => 'Fecha de confirmación',
            'motivo_cancelacion_recepcion' => 'Motivo de cancelación',
            'detalle_recepcion_id' => 'Línea de la recepción',
            'cantidad_presentaciones_detalle_recepcion' => 'Cantidad de presentaciones recibidas',
            'equivalencia_base_aplicada_detalle_recepcion' => 'Equivalencia aplicada en recepción',
            'cantidad_base_detalle_recepcion' => 'Cantidad física recibida',
            'fecha_vencimiento_detalle_recepcion' => 'Fecha de vencimiento',
            'calidad_detalle_recepcion' => 'Calidad o condición',
            'observaciones_detalle_recepcion' => 'Observación del producto recibido',
            'cantidad_items_recepcion' => 'Cantidad de productos recibidos',
            'detalle_pedido_id' => 'Línea del pedido',
            'presentacion_articulo_id' => 'Presentación',
            'preparado_detalle_pedido' => 'Producto preparado',
            'name' => 'Nombre',
            'role' => 'Rol',
            'roles' => 'Roles',
            'permissions' => 'Permisos',
            default => str($property)->replace('_', ' ')->headline()->toString(),
        };
    }

    public function formatPropertyValue(mixed $value, ?string $property = null): string
    {
        if ($property === 'imagen_articulo') {
            return $value === null || $value === '' ? 'Sin imagen' : 'Con imagen';
        }

        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            if (in_array($property, ['activo_empleado', 'activo_cliente', 'activo_sucursal', 'activo_usuario', 'estado_proveedor', 'estado_vehiculo', 'estado_almacen', 'estado_categoria_articulo', 'estado_unidad_medida', 'estado_articulo', 'estado_presentacion_articulo'], true)) {
                return $value ? 'Activo' : 'Inactivo';
            }

            return $value ? 'Sí' : 'No';
        }

        if (is_array($value)) {
            return $this->formatArrayValue($value, $property);
        }

        $stringValue = (string) $value;

        return match ($property) {
            'route' => $this->formatRouteValue($stringValue),
            'method' => $this->formatMethodValue($stringValue),
            'guard' => $stringValue === 'web' ? 'Navegador web' : 'Acceso del sistema',
            'reason' => $stringValue === 'rate_limit' ? 'Demasiados intentos de inicio de sesión' : 'Acceso restringido',
            'user_agent' => $this->formatUserAgent($stringValue),
            'user_id' => 'Usuario #'.$stringValue,
            'sucursal_id' => 'Sucursal #'.$stringValue,
            'proveedor_id' => 'Proveedor #'.$stringValue,
            'empleado_id' => 'Empleado #'.$stringValue,
            'registrado_por', 'preparado_por', 'confirmado_por', 'cancelado_por' => 'Usuario #'.$stringValue,
            'estado_pedido' => EstadoPedido::tryFrom($stringValue)?->label() ?? $stringValue,
            'estado_compra' => EstadoCompra::tryFrom($stringValue)?->label() ?? $stringValue,
            'estado_recepcion' => EstadoRecepcion::tryFrom($stringValue)?->label() ?? $stringValue,
            'estado_lote' => EstadoLote::tryFrom($stringValue)?->label() ?? $stringValue,
            'tipo_origen_lote' => TipoOrigenLote::tryFrom($stringValue)?->label() ?? $stringValue,
            'tipo_movimiento_inventario' => TipoMovimientoInventario::tryFrom($stringValue)?->label() ?? $stringValue,
            'estado_movimiento_inventario' => EstadoMovimientoInventario::tryFrom($stringValue)?->label() ?? $stringValue,
            'tipo_referencia_movimiento_inventario' => TipoReferenciaMovimientoInventario::tryFrom($stringValue)?->label() ?? $stringValue,
            'cliente_id' => 'Cliente #'.$stringValue,
            'categoria_articulo_id' => 'Categoría #'.$stringValue,
            'unidad_medida_id' => 'Unidad de medida #'.$stringValue,
            'articulo_id' => 'Producto #'.$stringValue,
            'compra_id' => 'Compra #'.$stringValue,
            'almacen_id' => 'Almacén #'.$stringValue,
            'lote_id' => 'Lote #'.$stringValue,
            'movimiento_inventario_id' => 'Movimiento #'.$stringValue,
            'referencia_id_movimiento_inventario' => 'Registro #'.$stringValue,
            'detalle_pedido_id' => 'Línea #'.$stringValue,
            'detalle_compra_id' => 'Línea #'.$stringValue,
            'detalle_recepcion_id' => 'Línea #'.$stringValue,
            'presentacion_articulo_id' => 'Presentación #'.$stringValue,
            'precio_por_detalle_compra' => PrecioPorDetalleCompra::tryFrom($stringValue)?->label() ?? $stringValue,
            'uso_presentacion_articulo' => UsoPresentacionArticulo::tryFrom($stringValue)?->label() ?? $stringValue,
            'tipo_equivalencia_presentacion_articulo' => TipoEquivalenciaPresentacionArticulo::tryFrom($stringValue)?->label() ?? $stringValue,
            'fecha_ingreso_empleado', 'fecha_compra', 'fecha_recepcion', 'fecha_ingreso_lote', 'fecha_vencimiento_lote', 'fecha_vencimiento_detalle_recepcion', 'fecha_movimiento_inventario', 'confirmado_en', 'cancelado_en' => $this->formatStoredDate($stringValue),
            default => $stringValue,
        };
    }

    /** @param array<mixed> $values */
    private function formatArrayValue(array $values, ?string $property): string
    {
        if ($values === []) {
            return 'Ninguno';
        }

        if ($property === 'permissions') {
            return collect($values)
                ->map(fn (mixed $permission): string => PermissionName::tryFrom((string) $permission)?->label() ?? (string) $permission)
                ->join(', ');
        }

        if (array_is_list($values)) {
            return collect($values)
                ->map(fn (mixed $item): string => is_array($item)
                    ? $this->formatArrayValue($item, null)
                    : (string) $item)
                ->join(' | ');
        }

        return collect($values)
            ->map(fn (mixed $item, string $key): string => $this->propertyLabel($key).': '.$this->formatPropertyValue($item, $key))
            ->join('; ');
    }

    public function render(): View
    {
        return view('livewire.panel.activity-logs.index');
    }

    private function filteredActivities(): Builder
    {
        $dateFrom = $this->localDateBoundary($this->dateFrom);
        $dateTo = $this->localDateBoundary($this->dateTo, endOfDay: true);
        $selectedModule = ActivityLogName::tryFrom($this->module);
        $selectedEvent = ActivityEvent::tryFrom($this->event);

        return Activity::query()
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $search = '%'.$this->search.'%';

                    $query->where('description', 'like', $search)
                        ->orWhere('log_name', 'like', $search)
                        ->orWhere('event', 'like', $search)
                        ->orWhereHasMorph('causer', [User::class], function (Builder $query) use ($search): void {
                            $query->where('email', 'like', $search)
                                ->orWhere('name', 'like', $search)
                                ->orWhereHas('empleado', function (Builder $query) use ($search): void {
                                    $query->where('nombre_empleado', 'like', $search)
                                        ->orWhere('apellido_empleado', 'like', $search);
                                });
                        });
                });
            })
            ->when($selectedModule !== null, fn (Builder $query) => $query->where('log_name', $selectedModule->value))
            ->when($selectedEvent !== null, fn (Builder $query) => $query->where('event', $selectedEvent->value))
            ->when($dateFrom !== null, fn (Builder $query) => $query->where('created_at', '>=', $dateFrom))
            ->when($dateTo !== null, fn (Builder $query) => $query->where('created_at', '<=', $dateTo));
    }

    private function activitiesForExportQuery(): Builder
    {
        return $this->filteredActivities()
            ->select([
                'id',
                'log_name',
                'description',
                'subject_type',
                'subject_id',
                'event',
                'causer_type',
                'causer_id',
                'properties',
                'created_at',
            ])
            ->with(['causer' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    User::class => ['empleado:id,user_id,nombre_empleado,apellido_empleado'],
                ]);
            }])
            ->oldest('id');
    }

    /** @return array{module: string, event: string, period: string, search: string} */
    private function reportFilters(): array
    {
        $module = ActivityLogName::tryFrom($this->module)?->label() ?? 'Todos los módulos';
        $event = ActivityEvent::tryFrom($this->event)?->label() ?? 'Todos los eventos';

        $period = match (true) {
            $this->dateFrom !== '' && $this->dateTo !== '' => $this->formatStoredDate($this->dateFrom).' al '.$this->formatStoredDate($this->dateTo),
            $this->dateFrom !== '' => 'Desde el '.$this->formatStoredDate($this->dateFrom),
            $this->dateTo !== '' => 'Hasta el '.$this->formatStoredDate($this->dateTo),
            default => 'Todo el historial',
        };

        return [
            'module' => $module,
            'event' => $event,
            'period' => $period,
            'search' => $this->search !== '' ? $this->search : 'Sin búsqueda específica',
        ];
    }

    private function activityWithCauser(): Builder
    {
        return Activity::query()->with(['causer' => function (MorphTo $morphTo): void {
            $morphTo->morphWith([
                User::class => ['empleado:id,user_id,nombre_empleado,apellido_empleado'],
            ]);
        }]);
    }

    private function validDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function localDateBoundary(string $value, bool $endOfDay = false): ?CarbonImmutable
    {
        $validDate = $this->validDate($value);

        if ($validDate === null) {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $validDate, $this->displayTimezone());

        if ($date === false) {
            return null;
        }

        return ($endOfDay ? $date->endOfDay() : $date->startOfDay())->utc();
    }

    private function displayTimezone(): string
    {
        return (string) config('app.display_timezone', 'America/La_Paz');
    }

    private function formatRouteValue(string $route): string
    {
        return match ($route) {
            'default-livewire.update' => 'Actualización de la interfaz',
            'livewire.upload-file' => 'Carga de archivos',
            'login', 'login.store' => 'Inicio de sesión',
            'logout' => 'Cierre de sesión',
            'panel.inicio' => 'Panel principal',
            'panel.users.index' => 'Gestión de usuarios',
            'panel.employees.index' => 'Gestión de empleados',
            'panel.clients.index' => 'Gestión de clientes',
            'panel.branches.index' => 'Gestión de sucursales',
            'panel.proveedores.index' => 'Gestión de proveedores',
            'panel.vehiculos.index' => 'Gestión de vehículos',
            'panel.almacenes.index' => 'Gestión de almacenes',
            'panel.lotes.index' => 'Gestión de lotes',
            'panel.movimientos-inventario.index' => 'Movimientos de inventario',
            'panel.recepciones.index' => 'Gestión de recepciones',
            'panel.recepciones.details' => 'Productos de la recepción',
            'panel.categorias-articulos.index' => 'Gestión de categorías de productos',
            'panel.unidades-medida.index' => 'Gestión de unidades de medida',
            'panel.articulos.index' => 'Gestión de productos',
            'panel.presentaciones-articulos.index' => 'Gestión de presentaciones de productos',
            'panel.roles.index' => 'Gestión de roles y permisos',
            'panel.activity.index' => 'Registro de actividad',
            default => 'Acción interna del sistema',
        };
    }

    private function formatMethodValue(string $method): string
    {
        return match ($method) {
            'GET' => 'Consulta de información',
            'POST' => 'Envío de información',
            'PUT', 'PATCH' => 'Actualización de información',
            'DELETE' => 'Eliminación de información',
            default => 'Operación del sistema',
        };
    }

    private function formatUserAgent(string $userAgent): string
    {
        $browser = match (true) {
            str($userAgent)->contains('Edg/') => 'Microsoft Edge',
            str($userAgent)->contains('Chrome/') => 'Google Chrome',
            str($userAgent)->contains('Firefox/') => 'Mozilla Firefox',
            str($userAgent)->contains('Safari/') => 'Safari',
            default => 'Navegador no identificado',
        };

        $device = match (true) {
            str($userAgent)->contains('Windows') => 'computadora con Windows',
            str($userAgent)->contains('Android') => 'dispositivo Android',
            str($userAgent)->contains(['iPhone', 'iPad']) => 'dispositivo Apple',
            str($userAgent)->contains('Mac OS') => 'computadora Apple',
            str($userAgent)->contains('Linux') => 'computadora con Linux',
            default => 'dispositivo no identificado',
        };

        return $browser.' en '.$device;
    }

    private function formatStoredDate(string $value): string
    {
        try {
            return (new DateTimeImmutable($value))->format('d/m/Y');
        } catch (Exception) {
            return $value;
        }
    }

    private function formatPropertiesForExport(Activity $activity): string
    {
        $properties = $activity->properties ?? collect();
        $sections = collect();
        $oldValues = $properties->get('old');
        $newValues = $properties->get('attributes');

        if (is_array($oldValues) && $oldValues !== []) {
            $sections->push('Valores anteriores: '.$this->formatArrayValue($oldValues, null));
        }

        if (is_array($newValues) && $newValues !== []) {
            $sections->push('Valores nuevos: '.$this->formatArrayValue($newValues, null));
        }

        $properties->except(['old', 'attributes'])
            ->each(function (mixed $value, string $property) use ($sections): void {
                $sections->push($this->propertyLabel($property).': '.$this->formatPropertyValue($value, $property));
            });

        return $sections->isEmpty() ? 'Sin información adicional' : $sections->join(' | ');
    }

    private function safeCsvValue(mixed $value): string
    {
        $stringValue = $this->formatPropertyValue($value);

        return preg_match('/^[=+\-@]/u', $stringValue) === 1
            ? "'{$stringValue}"
            : $stringValue;
    }
}
