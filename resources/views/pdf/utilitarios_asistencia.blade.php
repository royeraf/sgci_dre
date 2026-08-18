<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Reporte de Asistencia</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #1e293b; background: #fff; }

        .header { background-color: #d97706; background-image: linear-gradient(135deg, #d97706, #ea580c); color: #fff; padding: 14px 20px; margin-bottom: 16px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .header h1 { font-size: 16px; font-weight: 800; letter-spacing: -0.5px; }
        .header p { font-size: 9px; opacity: 0.85; margin-top: 2px; }
        .header .meta { text-align: right; font-size: 8.5px; opacity: 0.9; }

        .stats-table { width: calc(100% - 40px); margin: 0 20px 14px; border-collapse: separate; border-spacing: 5px 0; }
        .stat-box { border-radius: 8px; padding: 8px 10px; text-align: center; }
        .stat-box .num { font-size: 20px; font-weight: 900; }
        .stat-box .lbl { font-size: 7.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
        .stat-total { background: #fff7ed; border: 1.5px solid #fed7aa; color: #9a3412; }
        .stat-dias  { background: #fefce8; border: 1.5px solid #fde68a; color: #92400e; }

        .table-wrap { margin: 0 20px; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead tr { background: #7c2d12; color: #fff; }
        thead th { padding: 6px 5px; text-align: center; font-size: 7px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }
        thead th.col-name { text-align: left; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr { border-bottom: 1px solid #e2e8f0; }
        tbody td { padding: 5px; vertical-align: middle; text-align: center; word-wrap: break-word; overflow-wrap: break-word; }
        tbody td.col-name { text-align: left; }

        .mark-yes { color: #15803d; font-weight: bold; font-size: 10px; }
        .mark-no  { color: #cbd5e1; }

        .footer-table { width: calc(100% - 40px); margin: 14px 20px 0; border-top: 1px solid #e2e8f0; border-collapse: collapse; }
        .footer-table td { padding-top: 8px; font-size: 7.5px; color: #94a3b8; }
        .footer-table .right { text-align: right; }
        .no-data { text-align: center; padding: 30px; color: #94a3b8; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <h1>Reporte de Asistencia</h1>
                    <p>{{ $evento->titulo }}</p>
                </td>
                <td class="meta">
                    <div>Periodo: <strong>{{ $evento->fecha_inicio->format('d/m/Y') }} — {{ $evento->fecha_fin->format('d/m/Y') }}</strong></div>
                    <div>Generado: {{ now()->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="stats-table">
        <tr>
            <td class="stat-box stat-total">
                <div class="num">{{ $inscritos->count() }}</div>
                <div class="lbl">Total Inscritos</div>
            </td>
            <td class="stat-box stat-dias">
                <div class="num">{{ count($dias) }}</div>
                <div class="lbl">Días del Evento</div>
            </td>
        </tr>
    </table>

    @if($inscritos->isEmpty() || empty($dias))
        <div class="no-data">No se encontraron inscritos o días registrados para este evento.</div>
    @else
    @php
        $diaColWidth = count($dias) > 0 ? round(45 / count($dias), 2) : 0;
    @endphp
    <div class="table-wrap">
    <table class="data">
        <colgroup>
            <col style="width: 30%;">
            <col style="width: 15%;">
            @foreach ($dias as $dia)
                <col style="width: {{ $diaColWidth }}%;">
            @endforeach
            <col style="width: 10%;">
        </colgroup>
        <thead>
            <tr>
                <th class="col-name">Nombres y Apellidos</th>
                <th>Documento</th>
                @foreach ($dias as $dia)
                    <th>{{ \Carbon\Carbon::parse($dia)->format('d/m') }}</th>
                @endforeach
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($inscritos as $i)
            <tr>
                <td class="col-name">{{ trim(($i['nombres'] ?? '') . ' ' . ($i['apellidos'] ?? '')) }}</td>
                <td>{{ $i['numero_documento'] ?? '-' }}</td>
                @foreach ($dias as $dia)
                    <td>
                        @if($i['marcas'][$dia] ?? false)
                            <span class="mark-yes">&#10003;</span>
                        @else
                            <span class="mark-no">&#8212;</span>
                        @endif
                    </td>
                @endforeach
                <td><strong>{{ $i['total_asistencias'] }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @endif

    <table class="footer-table">
        <tr>
            <td>Utilitarios — DRE Sistema de Control</td>
            <td class="right">Total de inscritos: {{ $inscritos->count() }}</td>
        </tr>
    </table>
</body>
</html>
