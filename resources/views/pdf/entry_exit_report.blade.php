<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Reporte Control de Personal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1e293b; background: #fff; }

        .header { background: linear-gradient(135deg, #059669, #0d9488); color: #fff; padding: 14px 20px; margin-bottom: 16px; }
        .header-inner { display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 16px; font-weight: 800; letter-spacing: -0.5px; }
        .header p { font-size: 9px; opacity: 0.85; margin-top: 2px; }
        .header .meta { text-align: right; font-size: 8.5px; opacity: 0.9; }

        .stats { display: flex; gap: 10px; margin: 0 20px 14px; }
        .stat-box { flex: 1; border-radius: 8px; padding: 8px 10px; text-align: center; }
        .stat-box .num { font-size: 20px; font-weight: 900; }
        .stat-box .lbl { font-size: 7.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
        .stat-total    { background: #f0fdf4; border: 1.5px solid #bbf7d0; color: #166534; }
        .stat-return   { background: #f0fdf4; border: 1.5px solid #86efac; color: #15803d; }
        .stat-pending  { background: #fefce8; border: 1.5px solid #fde68a; color: #92400e; }
        .stat-comision { background: #faf5ff; border: 1.5px solid #d8b4fe; color: #6b21a8; }
        .stat-permiso  { background: #f0f9ff; border: 1.5px solid #bae6fd; color: #0c4a6e; }

        table { width: calc(100% - 40px); margin: 0 20px; border-collapse: collapse; }
        thead tr { background: #064e3b; color: #fff; }
        thead th { padding: 7px 8px; text-align: left; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr { border-bottom: 1px solid #e2e8f0; }
        tbody td { padding: 6px 8px; vertical-align: middle; }

        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 7.5px; font-weight: 700; }
        .badge-comision { background: #f3e8ff; color: #7e22ce; }
        .badge-permiso  { background: #e0f2fe; color: #075985; }
        .badge-ok  { background: #dcfce7; color: #15803d; }
        .badge-pend { background: #fef9c3; color: #854d0e; }

        .turno-manana { background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 4px; font-weight: 700; }
        .turno-tarde  { background: #ffedd5; color: #9a3412; padding: 2px 6px; border-radius: 4px; font-weight: 700; }
        .turno-noche  { background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 4px; font-weight: 700; }

        .footer { margin: 14px 20px 0; border-top: 1px solid #e2e8f0; padding-top: 8px; display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
        .no-data { text-align: center; padding: 30px; color: #94a3b8; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-inner">
            <div>
                <h1>Reporte de Control de Personal</h1>
                <p>Papeletas de salida registradas en el periodo seleccionado</p>
            </div>
            <div class="meta">
                <div>Periodo: <strong>{{ $start_date }} — {{ $end_date }}</strong></div>
                <div>Generado: {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    <div class="stats">
        <div class="stat-box stat-total">
            <div class="num">{{ $total }}</div>
            <div class="lbl">Total Papeletas</div>
        </div>
        <div class="stat-box stat-return">
            <div class="num">{{ $retornados }}</div>
            <div class="lbl">Retornaron</div>
        </div>
        <div class="stat-box stat-pending">
            <div class="num">{{ $pendientes }}</div>
            <div class="lbl">Pendientes</div>
        </div>
    </div>

    @if($entries->isEmpty())
        <div class="no-data">No se encontraron registros en el período seleccionado.</div>
    @else
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>DNI</th>
                <th>Personal</th>
                <th>Régimen</th>
                <th>Turno</th>
                <th>Hora Salida</th>
                <th>Hora Retorno</th>
                <th>Tipo</th>
                <th>Motivo</th>
                <th>Papeleta</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($entries as $e)
            <tr>
                <td>{{ $e['fecha'] }}</td>
                <td>{{ $e['dni'] }}</td>
                <td style="max-width:130px;">{{ $e['personal'] }}</td>
                <td>{{ $e['regimen'] }}</td>
                <td>
                    @if($e['turno'] === 'Mañana')
                        <span class="turno-manana">Mañana</span>
                    @elseif($e['turno'] === 'Tarde')
                        <span class="turno-tarde">Tarde</span>
                    @else
                        <span class="turno-noche">Noche</span>
                    @endif
                </td>
                <td>{{ $e['hora_salida'] }}</td>
                <td>
                    @if($e['hora_retorno'] !== '--:--')
                        <span class="badge badge-ok">{{ $e['hora_retorno'] }}</span>
                    @else
                        <span class="badge badge-pend">Pendiente</span>
                    @endif
                </td>
                <td>
                    @if($e['tipo_motivo'] === 'Comisión')
                        <span class="badge badge-comision">Comisión</span>
                    @else
                        <span class="badge badge-permiso">Permiso</span>
                    @endif
                </td>
                <td style="max-width:160px; font-size:8px;">{{ $e['motivo'] }}</td>
                <td><strong>{{ $e['papeleta'] }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        <span>Control de Personal — DRE Sistema de Control</span>
        <span>Total de registros: {{ $total }}</span>
    </div>
</body>
</html>
