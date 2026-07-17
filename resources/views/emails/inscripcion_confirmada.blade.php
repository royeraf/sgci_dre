<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Confirmación de inscripción</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            color: #1e293b;
            background-color: #f1f5f9;
            margin: 0;
            padding: 24px 0;
        }
        .container {
            max-width: 560px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: {{ $evento->color_primario ?? '#b45309' }};
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .header .logo-wrap {
            display: inline-block;
            background-color: #ffffff;
            border-radius: 8px;
            padding: 6px 10px;
            margin-bottom: 12px;
        }
        .header .institucion {
            font-size: 12px;
            letter-spacing: 1px;
            text-transform: uppercase;
            opacity: 0.9;
            margin: 0 0 4px;
        }
        .header .titulo-evento {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }
        .body {
            padding: 24px;
        }
        .saludo {
            font-size: 16px;
            margin: 0 0 16px;
        }
        .detalle {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        .detalle td {
            padding: 8px 0;
            vertical-align: top;
            border-bottom: 1px solid #f1f5f9;
        }
        .detalle .label {
            width: 140px;
            font-weight: bold;
            color: #475569;
        }
        .footer {
            padding: 16px 24px;
            font-size: 12px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if(file_exists(public_path('images/logo.png')))
                <div class="logo-wrap">
                    <img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="DRE Huánuco" style="height: 32px; display: block;">
                </div>
            @endif
            <p class="institucion">Dirección Regional de Educación Huánuco</p>
            <p class="titulo-evento">{{ $evento->titulo }}</p>
        </div>

        <div class="body">
            <p class="saludo">Hola {{ $inscripcion->nombres }},</p>
            <p>Tu inscripción ha sido registrada correctamente. Estos son los detalles del evento:</p>

            <table class="detalle">
                <tr>
                    <td class="label">Fecha</td>
                    <td>
                        {{ $evento->fecha_inicio->format('d/m/Y') }}
                        @if(!$evento->fecha_fin->equalTo($evento->fecha_inicio))
                            al {{ $evento->fecha_fin->format('d/m/Y') }}
                        @endif
                    </td>
                </tr>
                @if($evento->hora_inicio)
                <tr>
                    <td class="label">Hora</td>
                    <td>{{ $evento->hora_inicio }}@if($evento->hora_fin) - {{ $evento->hora_fin }}@endif</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Modalidad</td>
                    <td>{{ ucfirst($evento->modalidad) }}</td>
                </tr>
                @if($evento->modalidad !== 'virtual' && $evento->lugar)
                <tr>
                    <td class="label">Lugar</td>
                    <td>{{ $evento->lugar }}</td>
                </tr>
                @endif
                @if($evento->modalidad !== 'presencial' && $evento->enlace_virtual)
                <tr>
                    <td class="label">Enlace virtual</td>
                    <td><a href="{{ $evento->enlace_virtual }}">{{ $evento->enlace_virtual }}</a></td>
                </tr>
                @endif
                @if($evento->horas_educativas)
                <tr>
                    <td class="label">Horas educativas</td>
                    <td>{{ $evento->horas_educativas }}</td>
                </tr>
                @endif
            </table>

            <p style="font-weight: bold; margin: 20px 0 8px;">Tus datos de inscripción</p>
            <table class="detalle">
                <tr>
                    <td class="label">Nombre completo</td>
                    <td>{{ $inscripcion->nombres }} {{ $inscripcion->apellidos }}</td>
                </tr>
                <tr>
                    <td class="label">DNI</td>
                    <td>{{ $inscripcion->numero_documento }}</td>
                </tr>
                <tr>
                    <td class="label">Celular</td>
                    <td>{{ $inscripcion->celular }}</td>
                </tr>
                <tr>
                    <td class="label">Dirección</td>
                    <td>{{ $inscripcion->direction?->nombre }}</td>
                </tr>
                <tr>
                    <td class="label">Oficina</td>
                    <td>{{ $inscripcion->office?->nombre }}</td>
                </tr>
                <tr>
                    <td class="label">Cargo</td>
                    <td>{{ $inscripcion->cargo }}</td>
                </tr>
                <tr>
                    <td class="label">Profesión</td>
                    <td>{{ $inscripcion->profesion }}</td>
                </tr>
                <tr>
                    <td class="label">Régimen</td>
                    <td>{{ $inscripcion->contractType?->nombre }}</td>
                </tr>
            </table>

            <p>Guarda este correo como constancia de tu inscripción.</p>
        </div>

        <div class="footer">
            Este es un correo automático, por favor no respondas a este mensaje.
        </div>
    </div>
</body>
</html>
