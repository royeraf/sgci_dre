<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Ticket de Visita #{{ $visit->id }}</title>
    <style>
        @page {
            size: 80mm 200mm; /* Tamaño ticket térmico ajustado */
            margin: 0;
        }
        body {
            font-family: 'Courier New', monospace; /* Fuente tipo ticket */
            font-size: 11px;
            color: #000;
            margin: 2mm 4mm;
            line-height: 1.2;
            text-align: center;
        }
        .header {
            margin-bottom: 5px;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }
        .title {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 10px;
        }
        .ticket-info {
            margin: 10px 0;
        }
        .ticket-number {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            border: 2px solid #000;
            padding: 5px;
            display: inline-block;
        }
        .label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .value {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
            word-wrap: break-word; /* Para nombres largos */
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .footer {
            font-size: 8px;
            margin-top: 10px;
        }
        .qr-placeholder {
            margin: 10px auto;
            border: 1px solid #000;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="subtitle">DIRECCIÓN REGIONAL DE EDUCACIÓN</div>
        <div class="subtitle">HUÁNUCO</div>
        <div class="title">VISITA EXTERNA</div>
        <div class="subtitle">{{ now()->format('d/m/Y H:i A') }}</div>
    </div>

    <div class="ticket-info">
        <div class="label">NÚMERO DE PASE</div>
        <div class="ticket-number">V-{{ str_pad($visit->id, 5, '0', STR_PAD_LEFT) }}</div>
    </div>

    <div class="divider"></div>

    <div class="ticket-info" style="text-align: left;">
        <div class="label">VISITANTE:</div>
        <div class="value">{{ Str::upper($visit->nombres) }}</div>
        
        <div class="label">DNI:</div>
        <div class="value">{{ $visit->dni }}</div>

        <div class="divider"></div>

        <div class="label">DESTINO (ÁREA):</div>
        <div class="value">{{ Str::upper($visit->area) }}</div>

        @if($visit->a_quien_visita)
        <div class="label">A QUIEN VISITA:</div>
        <div class="value">{{ Str::upper($visit->a_quien_visita) }}</div>
        @endif

        <div class="label">MOTIVO:</div>
        <div class="value" style="font-size: 10px;">{{ Str::limit($visit->motivo, 100) }}</div>
    </div>

    <div class="divider"></div>

    <div class="footer">
        <p>CONSERVE ESTE TICKET HASTA<br>EL FINAL DE SU VISITA</p>
        <p>*** DRE HUÁNUCO ***</p>
    </div>
</body>
</html>
