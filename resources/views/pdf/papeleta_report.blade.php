<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Papeletas de Salida</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 9px;
            color: #000;
        }
        h2 {
            text-align: center;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .sub-header {
            text-align: center;
            font-size: 9px;
            color: #666;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 3px 5px;
            text-align: left;
        }
        th {
            background-color: #e5e7eb;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 2px;
        }
        .badge-aprobado { background-color: #dcfce7; color: #166534; }
        .badge-desaprobado { background-color: #fee2e2; color: #991b1b; }
        .badge-pendiente { background-color: #fef9c3; color: #854d0e; }
        .footer {
            text-align: center;
            font-size: 7px;
            color: #9ca3af;
            margin-top: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <h2>REPORTE DE PAPELETAS DE SALIDA</h2>
    <div class="sub-header">
        DIRECCION REGIONAL DE EDUCACION HUANUCO
        @if($filtros['fecha_desde'] || $filtros['fecha_hasta'])
            | Periodo: {{ $filtros['fecha_desde'] ?? '---' }} al {{ $filtros['fecha_hasta'] ?? '---' }}
        @endif
        @if($filtros['estado'] !== 'TODOS')
            | Estado: {{ $filtros['estado'] }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 6%;">N° Papeleta</th>
                <th style="width: 15%;">Servidor</th>
                <th style="width: 7%;">DNI</th>
                <th style="width: 12%;">Direccion</th>
                <th style="width: 10%;">Motivo</th>
                <th style="width: 7%;">Fecha</th>
                <th style="width: 5%;">H. Salida</th>
                <th style="width: 5%;">H. Retorno</th>
                <th style="width: 5%;">Turno</th>
                <th style="width: 6%;">Estado</th>
                <th style="width: 10%;">Aprobado por</th>
                <th style="width: 12%;">Comentario</th>
            </tr>
        </thead>
        <tbody>
            @forelse($papeletas as $p)
            <tr>
                <td>{{ $p->numero_papeleta }}</td>
                <td>{{ $p->employee?->apellidos }}, {{ $p->employee?->nombres }}</td>
                <td>{{ $p->employee?->dni }}</td>
                <td>{{ $p->employee?->direction?->nombre ?? '-' }}</td>
                <td>{{ $p->reason?->nombre ?? '-' }}</td>
                <td>{{ $p->fecha_salida?->format('d/m/Y') }}</td>
                <td>{{ $p->hora_salida_estimada }}</td>
                <td>{{ $p->hora_retorno_estimada ?? '-' }}</td>
                <td>{{ $p->turno }}</td>
                <td><span class="badge badge-{{ strtolower($p->estado) }}">{{ $p->estado }}</span></td>
                <td>{{ $p->aprobador ? $p->aprobador->apellidos . ', ' . $p->aprobador->nombres : '-' }}</td>
                <td>{{ $p->comentario_aprobacion ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="12" style="text-align: center; padding: 20px;">No se encontraron papeletas con los filtros aplicados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Total: {{ $papeletas->count() }} papeleta(s) | Generado: {{ now()->format('d/m/Y H:i:s') }} | Sistema de Control - DRE Huanuco
    </div>
</body>
</html>
