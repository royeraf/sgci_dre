<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Reporte de Resultados de Examen</title>
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
        .stat-box .num { font-size: 18px; font-weight: 900; }
        .stat-box .lbl { font-size: 7.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
        .stat-total    { background: #fff7ed; border: 1.5px solid #fed7aa; color: #9a3412; }
        .stat-finaliz  { background: #f0f9ff; border: 1.5px solid #bae6fd; color: #0c4a6e; }
        .stat-aprob    { background: #f0fdf4; border: 1.5px solid #bbf7d0; color: #166534; }
        .stat-desaprob { background: #fef2f2; border: 1.5px solid #fecaca; color: #991b1b; }
        .stat-tasa     { background: #faf5ff; border: 1.5px solid #d8b4fe; color: #6b21a8; }
        .stat-promedio { background: #fefce8; border: 1.5px solid #fde68a; color: #92400e; }

        .table-wrap { margin: 0 20px; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead tr { background: #7c2d12; color: #fff; }
        thead th { padding: 7px 8px; text-align: left; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr { border-bottom: 1px solid #e2e8f0; }
        tbody td { padding: 6px 8px; vertical-align: middle; word-wrap: break-word; overflow-wrap: break-word; }

        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 7.5px; font-weight: 700; }
        .badge-aprobado    { background: #dcfce7; color: #15803d; }
        .badge-desaprobado { background: #fee2e2; color: #b91c1c; }
        .badge-en-curso    { background: #fef9c3; color: #854d0e; }

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
                    <h1>Reporte de Resultados de Examen</h1>
                    <p>{{ $evento->titulo }} — {{ $examen->titulo }}</p>
                </td>
                <td class="meta">
                    <div>Nota mínima aprobatoria: <strong>{{ $notaMinima !== null ? number_format($notaMinima, 2) : 'No definida' }}</strong></div>
                    <div>Generado: {{ now()->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="stats-table">
        <tr>
            <td class="stat-box stat-total">
                <div class="num">{{ $resumen['total'] }}</div>
                <div class="lbl">Total Intentos</div>
            </td>
            <td class="stat-box stat-finaliz">
                <div class="num">{{ $resumen['finalizados'] }}</div>
                <div class="lbl">Finalizados</div>
            </td>
            <td class="stat-box stat-aprob">
                <div class="num">{{ $resumen['aprobados'] ?? '-' }}</div>
                <div class="lbl">Aprobados</div>
            </td>
            <td class="stat-box stat-desaprob">
                <div class="num">{{ $resumen['desaprobados'] ?? '-' }}</div>
                <div class="lbl">Desaprobados</div>
            </td>
            <td class="stat-box stat-tasa">
                <div class="num">{{ $resumen['tasa_aprobacion'] !== null ? $resumen['tasa_aprobacion'] . '%' : '-' }}</div>
                <div class="lbl">Tasa Aprob.</div>
            </td>
            <td class="stat-box stat-promedio">
                <div class="num">{{ $resumen['promedio_nota'] ?? '-' }}</div>
                <div class="lbl">Promedio</div>
            </td>
        </tr>
    </table>

    @if($intentos->isEmpty())
        <div class="no-data">No se encontraron intentos registrados para este examen.</div>
    @else
    <div class="table-wrap">
    <table class="data">
        <colgroup>
            <col style="width: 6%;">
            <col style="width: 20%;">
            <col style="width: 12%;">
            <col style="width: 10%;">
            <col style="width: 10%;">
            <col style="width: 10%;">
            <col style="width: 14%;">
            <col style="width: 18%;">
        </colgroup>
        <thead>
            <tr>
                <th>N.º</th>
                <th>Nombres y Apellidos</th>
                <th>Documento</th>
                <th>N.º Intento</th>
                <th>Aciertos</th>
                <th>Puntaje</th>
                <th>Resultado</th>
                <th>Finalizado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($intentos as $i)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ trim(($i['nombres'] ?? '') . ' ' . ($i['apellidos'] ?? '')) }}</td>
                <td>{{ $i['numero_documento'] ?? '-' }}</td>
                <td>{{ $i['numero_intento'] }}</td>
                <td>{{ $i['aciertos'] }}/{{ $i['total_preguntas'] }}</td>
                <td><strong>{{ $i['puntaje'] ?? '-' }}</strong></td>
                <td>
                    @if($i['finalizado_en'] === null)
                        <span class="badge badge-en-curso">En curso</span>
                    @elseif($i['aprobado'] === true)
                        <span class="badge badge-aprobado">Aprobado</span>
                    @elseif($i['aprobado'] === false)
                        <span class="badge badge-desaprobado">Desaprobado</span>
                    @else
                        <span class="badge badge-en-curso">Finalizado</span>
                    @endif
                </td>
                <td>{{ $i['finalizado_en'] ?? '--' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @endif

    <table class="footer-table">
        <tr>
            <td>Utilitarios — DRE Sistema de Control</td>
            <td class="right">Total de intentos: {{ $resumen['total'] }}</td>
        </tr>
    </table>
</body>
</html>
