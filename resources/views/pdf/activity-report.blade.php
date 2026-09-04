<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de registro de actividad</title>
    <link rel="stylesheet" href="{{ public_path('css/activity-report-pdf.css') }}">
</head>
<body>
    <footer class="document-footer">
        <span>Documento generado automáticamente por HortyFru</span>
        <span class="confidentiality">Uso administrativo y confidencial</span>
    </footer>

    <table class="report-header" role="presentation">
        <tr>
            <td class="brand-column">
                <span class="brand-mark">HF</span>
                <span class="brand-name">HortyFru</span>
            </td>
            <td class="title-column">
                <h1>Reporte de registro de actividad</h1>
                <p>Historial de acciones y accesos registrados en el sistema</p>
            </td>
            <td class="metadata-column">
                <strong>Generado:</strong> {{ $generatedAt->format('d/m/Y H:i:s') }}<br>
                <strong>Responsable:</strong> {{ $generatedBy }}
            </td>
        </tr>
    </table>

    <table class="summary" role="presentation">
        <tr>
            <td><span>Módulo</span><strong>{{ $filters['module'] }}</strong></td>
            <td><span>Evento</span><strong>{{ $filters['event'] }}</strong></td>
            <td><span>Periodo</span><strong>{{ $filters['period'] }}</strong></td>
            <td><span>Búsqueda</span><strong>{{ $filters['search'] }}</strong></td>
            <td class="total"><span>Total</span><strong>{{ $activities->count() }}</strong></td>
        </tr>
    </table>

    @if ($activities->isEmpty())
        <div class="empty-state">
            <h2>Sin actividades registradas</h2>
            <p>No se encontraron actividades que coincidan con los filtros seleccionados.</p>
        </div>
    @else
        <table class="activity-table">
            <thead>
                <tr>
                    <th class="record-column">Registro</th>
                    <th class="date-column">Fecha y hora</th>
                    <th class="module-column">Módulo / evento</th>
                    <th class="activity-column">Actividad y detalles</th>
                    <th class="responsible-column">Responsable</th>
                    <th class="subject-column">Registro afectado</th>
                    <th class="ip-column">Dirección IP</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($activities as $activity)
                    <tr>
                        <td class="record">#{{ $activity->id }}</td>
                        <td>{{ $activity->local_created_at?->format('d/m/Y') }}<br><span class="secondary">{{ $activity->local_created_at?->format('H:i:s') }}</span></td>
                        <td><strong>{{ $activity->module_label }}</strong><br><span class="event-label">{{ $activity->event_label }}</span></td>
                        <td>
                            <strong>{{ $activity->description }}</strong>
                            @if (($activity->properties?->count() ?? 0) > 0)
                                <p class="activity-details"><span>Detalle:</span> {{ $formatProperties($activity) }}</p>
                            @endif
                        </td>
                        <td>{{ $activity->causer_name }}</td>
                        <td>{{ $activity->subject_label }}</td>
                        <td>{{ $activity->properties?->get('ip_address', '—') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</body>
</html>
