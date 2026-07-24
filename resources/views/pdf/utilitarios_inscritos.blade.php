<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Reporte de Inscritos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1e293b; background: #fff; }

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

        table.data { width: calc(100% - 40px); margin: 0 20px; border-collapse: collapse; }
        thead tr { background: #7c2d12; color: #fff; }
        thead th { padding: 7px 8px; text-align: left; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr { border-bottom: 1px solid #e2e8f0; }
        tbody td { padding: 6px 8px; vertical-align: middle; }

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
                    <h1>Reporte de Inscritos</h1>
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
                <div class="num">{{ $total }}</div>
                <div class="lbl">Total Inscritos</div>
            </td>
        </tr>
    </table>

    @if($inscritos->isEmpty())
        <div class="no-data">No se encontraron inscritos para este evento.</div>
    @else
    <table class="data">
        <thead>
            <tr>
                <th>Nombres y Apellidos</th>
                <th>Documento</th>
                <th>Correo</th>
                <th>Dirección</th>
                <th>Oficina</th>
                <th>Cargo</th>
                <th>Profesión</th>
                <th>Régimen</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($inscritos as $i)
            <tr>
                <td style="max-width:130px;">{{ trim(($i['nombres'] ?? '') . ' ' . ($i['apellidos'] ?? '')) }}</td>
                <td>{{ $i['numero_documento'] ?? '-' }}</td>
                <td>{{ $i['correo'] ?? '-' }}</td>
                <td>{{ $i['direccion'] ?? '-' }}</td>
                <td>{{ $i['oficina'] ?? '-' }}</td>
                <td>{{ $i['cargo'] ?? '-' }}</td>
                <td>{{ $i['profesion'] ?? '-' }}</td>
                <td>{{ $i['regimen'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <table class="footer-table">
        <tr>
            <td>Utilitarios — DRE Sistema de Control</td>
            <td class="right">Total de registros: {{ $total }}</td>
        </tr>
    </table>
</body>
</html>
