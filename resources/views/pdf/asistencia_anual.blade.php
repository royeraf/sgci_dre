<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte Anual de Marcaciones</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 8mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8px;
            color: #000;
            margin: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 6px;
        }
        .header h1 {
            font-size: 11px;
            font-weight: bold;
            margin: 0 0 2px 0;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 9px;
            font-weight: bold;
            margin: 0 0 2px 0;
            text-transform: uppercase;
        }
        .header p {
            font-size: 8px;
            margin: 1px 0;
        }
        .employee-info {
            border: 1px solid #000;
            padding: 4px 6px;
            margin-bottom: 8px;
            background: #f5f5f5;
        }
        .employee-info table {
            width: 100%;
            border: none;
        }
        .employee-info td {
            border: none;
            padding: 1px 3px;
            font-size: 8px;
        }
        .employee-info .label {
            font-weight: bold;
            width: 90px;
        }
        table.resumen-anual {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.resumen-anual th {
            background-color: #1e3a5f;
            color: #fff;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            padding: 4px 3px;
            border: 1px solid #999;
        }
        table.resumen-anual td {
            border: 1px solid #ccc;
            padding: 3px 4px;
            font-size: 8px;
            text-align: center;
        }
        table.resumen-anual tr:nth-child(even) td {
            background-color: #f9f9f9;
        }
        table.resumen-anual td.mes {
            text-align: left;
            font-weight: bold;
        }
        table.resumen-anual tr.totales td {
            background-color: #1e3a5f;
            color: #fff;
            font-weight: bold;
            border: 1px solid #999;
        }
        table.resumen-anual td.sin-datos {
            color: #bbb;
            font-style: italic;
        }
        table.resumen-anual td.late {
            color: #dc2626;
            font-weight: bold;
        }
        table.resumen-anual td.extras {
            color: #059669;
            font-weight: bold;
        }
        .section-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 8px 0 4px 0;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
        }
        .footer {
            text-align: center;
            font-size: 7px;
            color: #666;
            margin-top: 10px;
            border-top: 1px solid #ccc;
            padding-top: 4px;
        }

        /* Tablas mensuales detalladas */
        .month-block {
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .month-title {
            background-color: #374151;
            color: #fff;
            font-size: 8px;
            font-weight: bold;
            padding: 3px 6px;
            text-transform: uppercase;
        }
        table.month-detail {
            width: 100%;
            border-collapse: collapse;
        }
        table.month-detail th {
            background-color: #e5e7eb;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
            padding: 2px;
            border: 1px solid #ccc;
        }
        table.month-detail td {
            border: 1px solid #e5e7eb;
            padding: 2px 3px;
            font-size: 7px;
            text-align: center;
        }
        table.month-detail tr.weekend td {
            background-color: #f3f4f6;
            color: #9ca3af;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Dirección Regional de Educación Huánuco</h1>
    <h2>Reporte Anual de Marcaciones</h2>
    <p>Año: <strong>{{ $year }}</strong></p>
</div>

<div class="employee-info">
    <table>
        <tr>
            <td class="label">DNI:</td>
            <td>{{ $employee->dni }}</td>
            <td class="label">Apellidos y Nombres:</td>
            <td><strong>{{ strtoupper($employee->apellidos . ', ' . $employee->nombres) }}</strong></td>
        </tr>
        <tr>
            <td class="label">Área/Dirección:</td>
            <td>{{ $employee->direction?->nombre ?? '-' }}</td>
            <td class="label">Categoría:</td>
            <td>{{ $employee->contractType?->nombre ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Cargo:</td>
            <td colspan="3">{{ $employee->position?->nombre ?? '-' }}</td>
        </tr>
    </table>
</div>

<!-- Resumen por mes -->
<div class="section-title">Resumen por Mes</div>
<table class="resumen-anual">
    <thead>
        <tr>
            <th style="width:12%; text-align:left; padding-left:5px;">Mes</th>
            <th style="width:9%">Días Lab.</th>
            <th style="width:9%">Asistidos</th>
            <th style="width:9%">Sin Marca</th>
            <th style="width:12%">Total Tardanza<br>(min)</th>
            <th style="width:13%">Total H. Normales</th>
            <th style="width:13%">Total Min. Extras</th>
            <th style="width:23%">Observación</th>
        </tr>
    </thead>
    <tbody>
        @foreach($meses as $mes)
        <tr>
            <td class="mes">{{ $mes['nombre'] }}</td>
            @if($mes['tiene_datos'])
                <td>{{ $mes['dias_laborables'] }}</td>
                <td>{{ $mes['dias_asistidos'] }}</td>
                <td class="{{ $mes['dias_sin_marca'] > 0 ? 'late' : '' }}">{{ $mes['dias_sin_marca'] }}</td>
                <td class="{{ $mes['total_tardanza_min'] > 0 ? 'late' : '' }}">
                    {{ $mes['total_tardanza_min'] > 0 ? $mes['total_tardanza_min'] . ' min' : '—' }}
                </td>
                <td>{{ number_format($mes['total_horas_normales'], 2) }} hrs</td>
                <td class="{{ $mes['total_min_extras'] > 0 ? 'extras' : '' }}">
                    {{ $mes['total_min_extras'] > 0 ? $mes['total_min_extras'] . ' min' : '—' }}
                </td>
                <td style="text-align:left; font-size:7px;">{{ $mes['observacion'] }}</td>
            @else
                <td class="sin-datos">{{ $mes['dias_laborables'] }}</td>
                <td class="sin-datos">—</td>
                <td class="sin-datos">—</td>
                <td class="sin-datos">—</td>
                <td class="sin-datos">—</td>
                <td class="sin-datos">—</td>
                <td class="sin-datos">Sin registros</td>
            @endif
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="totales">
            <td style="text-align:left; padding-left:5px;">TOTALES {{ $year }}</td>
            <td>{{ $totales['dias_laborables'] }}</td>
            <td>{{ $totales['dias_asistidos'] }}</td>
            <td>{{ $totales['dias_sin_marca'] }}</td>
            <td>{{ $totales['total_tardanza_min'] > 0 ? $totales['total_tardanza_min'] . ' min' : '—' }}</td>
            <td>{{ number_format($totales['total_horas_normales'], 2) }} hrs</td>
            <td>{{ $totales['total_min_extras'] > 0 ? $totales['total_min_extras'] . ' min' : '—' }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

<!-- Detalle mensual -->
<div class="section-title">Detalle por Mes</div>
@foreach($meses as $mes)
@if($mes['tiene_datos'])
<div class="month-block">
    <div class="month-title">{{ $mes['nombre'] }} {{ $year }}</div>
    <table class="month-detail">
        <thead>
            <tr>
                <th style="width:7%">Fecha</th>
                <th style="width:5%">Día</th>
                <th style="width:8%">Entrada</th>
                <th style="width:8%">S.Mediodía</th>
                <th style="width:8%">R.Mediodía</th>
                <th style="width:8%">Salida</th>
                <th style="width:7%">Tardanza</th>
                <th style="width:8%">H.Norm.</th>
                <th style="width:7%">M.Extra</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mes['dias'] as $dia)
            <tr class="{{ $dia['es_fin_semana'] ? 'weekend' : '' }}">
                <td>{{ $dia['fecha_label'] }}</td>
                <td>{{ $dia['dow_label'] }}</td>
                @if($dia['es_fin_semana'])
                    <td colspan="8" style="text-align:center;color:#bbb;font-style:italic;">
                        {{ $dia['es_domingo'] ? 'Dom.' : 'Sáb.' }}
                    </td>
                @else
                    <td>{{ $dia['marca']['entrada'] ?? '—' }}</td>
                    <td>{{ $dia['marca']['salida_mediodia'] ?? '—' }}</td>
                    <td>{{ $dia['marca']['retorno_mediodia'] ?? '—' }}</td>
                    <td>{{ $dia['marca']['salida'] ?? '—' }}</td>
                    <td style="{{ $dia['calculo']['tardanza'] > 0 ? 'color:#dc2626;font-weight:bold;' : '' }}">
                        {{ $dia['calculo']['tardanza'] > 0 ? $dia['calculo']['tardanza'] : '—' }}
                    </td>
                    <td>{{ $dia['marca'] ? number_format($dia['calculo']['horas_normales'], 2) : '—' }}</td>
                    <td style="{{ $dia['calculo']['min_extras'] > 0 ? 'color:#059669;' : '' }}">
                        {{ ($dia['marca'] && $dia['calculo']['min_extras'] > 0) ? $dia['calculo']['min_extras'] : '—' }}
                    </td>
                    <td style="text-align:left; font-size:7px;">
                        @if(!$dia['marca'])
                            <span style="color:#dc2626">SIN MARCA</span>
                        @else
                            {{ $dia['marca']['observaciones'] ?? '' }}
                        @endif
                    </td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endforeach

<div class="footer">
    Reporte generado el {{ now()->format('d/m/Y H:i:s') }} | Sistema de Control - DRE Huánuco
</div>

</body>
</html>
