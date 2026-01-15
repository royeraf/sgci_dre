<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte Semanal de Ocurrencias</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
        }
        .container {
            padding: 20px 25px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 16px;
            color: #4f46e5;
            margin-bottom: 3px;
        }
        .header h2 {
            font-size: 11px;
            color: #64748b;
            font-weight: normal;
            margin-bottom: 10px;
        }
        .header h3 {
            font-size: 13px;
            color: #1e293b;
            margin-bottom: 5px;
        }
        .header .periodo {
            font-size: 10px;
            color: #64748b;
        }
        .summary-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        .summary-title {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .stats-grid {
            display: table;
            width: 100%;
        }
        .stat-item {
            display: table-cell;
            text-align: center;
            padding: 5px;
        }
        .stat-value {
            font-size: 18px;
            font-weight: bold;
        }
        .stat-value.total { color: #3b82f6; }
        .stat-value.emergencia { color: #dc2626; }
        .stat-value.incidente { color: #f59e0b; }
        .stat-value.rutina { color: #22c55e; }
        .stat-value.aviso { color: #0ea5e9; }
        .stat-value.otros { color: #64748b; }
        .stat-label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section-title {
            font-size: 10px;
            color: #1e293b;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e2e8f0;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th {
            background-color: #4f46e5;
            color: white;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 5px;
            text-align: left;
        }
        table.data-table td {
            padding: 6px 5px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9px;
            vertical-align: top;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        table.data-table tr:hover {
            background-color: #f1f5f9;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-emergencia { background-color: #fecaca; color: #b91c1c; }
        .badge-robo { background-color: #fecaca; color: #b91c1c; }
        .badge-incidente { background-color: #fef3c7; color: #92400e; }
        .badge-rutina { background-color: #dcfce7; color: #166534; }
        .badge-aviso { background-color: #dbeafe; color: #1e40af; }
        .badge-otros { background-color: #f1f5f9; color: #475569; }
        .badge-pendiente { background-color: #fef3c7; color: #92400e; }
        .badge-aprobado { background-color: #dcfce7; color: #166534; }
        .badge-cerrado { background-color: #f1f5f9; color: #475569; }
        .description-cell {
            max-width: 200px;
            word-wrap: break-word;
        }
        .empty-message {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
            font-style: italic;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
        .footer p {
            font-size: 8px;
            color: #94a3b8;
        }
        .page-number {
            position: fixed;
            bottom: 10px;
            right: 25px;
            font-size: 8px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>DIRECCION REGIONAL DE EDUCACION HUANUCO</h1>
            <h2>Sistema de Gestion y Control Institucional</h2>
            <h3>REPORTE SEMANAL DE OCURRENCIAS</h3>
            <p class="periodo">Periodo: {{ $startDate }} al {{ $endDate }}</p>
        </div>

        <div class="summary-box">
            <div class="summary-title">Resumen Estadistico</div>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value total">{{ $stats['total'] }}</div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value emergencia">{{ $stats['emergencias'] }}</div>
                    <div class="stat-label">Emergencias</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value incidente">{{ $stats['incidentes'] }}</div>
                    <div class="stat-label">Incidentes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value rutina">{{ $stats['rutina'] }}</div>
                    <div class="stat-label">Rutina</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value aviso">{{ $stats['avisos'] }}</div>
                    <div class="stat-label">Avisos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value otros">{{ $stats['otros'] }}</div>
                    <div class="stat-label">Otros</div>
                </div>
            </div>
        </div>

        <div class="section-title">Detalle de Ocurrencias</div>

        @if(count($occurrences) > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Fecha</th>
                        <th style="width: 45px;">Hora</th>
                        <th style="width: 50px;">Turno</th>
                        <th style="width: 70px;">Tipo</th>
                        <th>Descripcion</th>
                        <th style="width: 80px;">Responsable</th>
                        <th style="width: 60px;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($occurrences as $occ)
                        <tr>
                            <td>{{ $occ->fecha->format('d/m/Y') }}</td>
                            <td>{{ $occ->hora ? $occ->hora->format('H:i') : '-' }}</td>
                            <td>{{ $occ->turno ?? '-' }}</td>
                            <td>
                                <span class="badge badge-{{ strtolower($occ->tipo) }}">
                                    {{ $occ->tipo }}
                                </span>
                            </td>
                            <td class="description-cell">{{ Str::limit($occ->descripcion, 100) }}</td>
                            <td>{{ $occ->vigilante?->full_name ?? '-' }}</td>
                            <td>
                                <span class="badge badge-{{ strtolower($occ->estado ?? 'pendiente') }}">
                                    {{ $occ->estado ?? 'Pendiente' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-message">
                No se registraron ocurrencias en este periodo semanal.
            </div>
        @endif

        <div class="footer">
            <p>Documento generado el {{ now()->format('d/m/Y H:i:s') }} | SGCI DRE Huanuco</p>
            @if($generatedBy)
                <p>Generado por: {{ $generatedBy }}</p>
            @endif
        </div>
    </div>
</body>
</html>
