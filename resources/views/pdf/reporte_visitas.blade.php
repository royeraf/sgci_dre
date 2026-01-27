<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Visitas</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 2px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .status-pendiente { color: #d97706; font-weight: bold; }
        .status-completado { color: #059669; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Visitas Externas</h1>
        <p>Dirección Regional de Educación</p>
        <p>Periodo: {{ $start }} al {{ $end }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 70px;">Fecha</th>
                <th style="width: 50px;">Hora Ing.</th>
                <th style="width: 50px;">Hora Sal.</th>
                <th style="width: 60px;">DNI</th>
                <th>Visitante</th>
                <th>Motivo</th>
                <th>Destino</th>
                <th style="width: 80px;">Vigilante</th>
                <th style="width: 65px;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($visits as $visit)
            <tr>
                <td>{{ $visit->fecha->format('d/m/Y') }}</td>
                <td>{{ $visit->hora_ingreso ? $visit->hora_ingreso->format('H:i') : '-' }}</td>
                <td>{{ $visit->hora_salida ? $visit->hora_salida->format('H:i') : '-' }}</td>
                <td>{{ $visit->dni }}</td>
                <td>{{ $visit->nombres }}</td>
                <td>{{ $visit->motivo }}</td>
                <td>
                    {{ $visit->office_nombre ? $visit->office_nombre : $visit->area_nombre }}
                    @if($visit->a_quien_visita)
                    <br><small>A: {{ $visit->a_quien_visita }}</small>
                    @endif
                </td>
                <td style="font-size: 10px;">{{ $visit->registrador ? $visit->registrador->name : 'N/A' }}</td>
                <td>
                    @if($visit->hora_salida)
                        <span class="status-completado">Completado</span>
                    @else
                        <span class="status-pendiente">Pendiente</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
