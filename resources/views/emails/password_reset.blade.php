<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Nueva contraseña</title>
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
            background-color: #1e3a8a;
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
        .header .titulo {
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
        .credencial {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 16px 20px;
            text-align: center;
            margin: 20px 0;
        }
        .credencial .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1d4ed8;
            margin: 0 0 6px;
        }
        .credencial .password {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 3px;
            color: #1e293b;
            margin: 0;
            font-family: "Courier New", monospace;
        }
        .nota {
            font-size: 13px;
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
            <p class="titulo">Recuperación de contraseña</p>
        </div>

        <div class="body">
            <p class="saludo">Hola {{ $nombre }},</p>
            <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en el Sistema de Gestión y Control Institucional (SGCI).</p>

            <div class="credencial">
                <p class="label">Tu nueva contraseña temporal</p>
                <p class="password">{{ $password }}</p>
            </div>

            <p class="nota">
                Ingresa a <a href="{{ url('/login') }}">{{ url('/login') }}</a> con tu DNI o usuario y esta contraseña.
                Por seguridad, el sistema te pedirá cambiarla en tu primer ingreso.
            </p>
            <p class="nota">
                Si no solicitaste este cambio, comunícate de inmediato con el área de sistemas.
            </p>
        </div>

        <div class="footer">
            Este es un correo automático, por favor no respondas a este mensaje.
        </div>
    </div>
</body>
</html>
