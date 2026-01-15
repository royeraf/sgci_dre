<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Papeleta de Salida #{{ $entry->papeleta }}</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 10mm;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.2;
        }
        .wrapper {
            width: 100%;
            position: relative;
        }
        
        /* Main Layout Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        
        .main-table {
            border: 1px solid #000;
        }

        .main-table td {
            padding: 4px;
            vertical-align: middle;
            border: 1px solid #000;
        }
        
        /* Typography */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }
        .section-header {
            background-color: #e5e7eb;
            font-weight: bold;
            font-size: 9px;
            padding: 2px 4px;
            text-transform: uppercase;
        }
        
        /* Header specific */
        .header-title {
            font-size: 14px;
            font-weight: 900;
            text-align: center;
        }
        .sub-title {
            font-size: 8px;
            text-align: center;
        }
        
        /* Checkboxes */
        .checkbox-wrapper {
            display: inline-block;
            margin-right: 20px;
        }
        .checkbox-box {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            margin-right: 4px;
            position: relative;
            top: 1px;
        }
        .checkbox-box.checked:after {
            content: 'X';
            position: absolute;
            top: -2px;
            left: 1px;
            font-size: 9px;
            font-weight: bold;
        }

        /* Signatures */
        .signature-area {
            margin-top: 30px;
            border: none;
        }
        .signature-area td {
            border: none;
            padding-top: 20px;
            text-align: center;
        }
        .signature-line {
            display: block;
            border-top: 1px solid #000;
            margin: 0 auto 2px auto;
            width: 80%;
        }

        /* Helpers */
        .no-border { border: none !important; }
        .dashed-box {
            border: 1px dashed #000;
            height: 60px;
            position: relative;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        
        <!-- Header Section -->
        <table class="main-table" style="margin-bottom: 5px; border: none;">
            <tr style="border: none;">
                <td style="width: 20%; border: none; text-align: center;">
                    @php
                        $logoPath = public_path('images/logo.png');
                        $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
                    @endphp
                    @if($logoData)
                        <img src="data:image/png;base64,{{ $logoData }}" style="width: 50px; height: auto;">
                    @endif
                </td>
                <td style="width: 50%; border: none; text-align: center;">
                    <div class="sub-title">DIRECCIÓN REGIONAL DE EDUCACIÓN HUÁNUCO</div>
                    <div class="header-title">PAPELETA DE SALIDA</div>
                </td>
                <td style="width: 30%; border: none;">
                    <table style="border: 1px solid #000;">
                        <tr>
                            <td class="text-center text-bold" style="background-color: #f3f4f6; font-size: 8px;">N° PAPELETA</td>
                        </tr>
                        <tr>
                            <td class="text-center text-bold" style="color: #dc2626; font-size: 14px; padding: 5px;">
                                {{ str_pad($entry->papeleta, 6, '0', STR_PAD_LEFT) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: #f3f4f6; font-size: 8px; border-top: 1px solid #000;">
                                <div style="display: flex; justify-content: space-between; padding: 0 4px;">
                                    <span>FECHA:</span>
                                    <span class="text-bold">{{ $entry->fecha ? $entry->fecha->format('d/m/Y') : '' }}</span>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Main Form Content -->
        <table class="main-table">
            <!-- Reason Checkboxes -->
            <tr>
                <td colspan="4" class="section-header">MOTIVO DE LA SALIDA</td>
            </tr>
            <tr>
                <td colspan="4">
                    @php
                        $isComision = $entry->tipo_motivo === 'comision';
                        $isPermiso = $entry->tipo_motivo === 'permiso';
                    @endphp
                    <div class="checkbox-wrapper">
                        <span class="checkbox-box {{ $isComision ? 'checked' : '' }}"></span> Comisión de Servicios
                    </div>
                    <div class="checkbox-wrapper">
                        <span class="checkbox-box {{ $isPermiso ? 'checked' : '' }}"></span> Permiso Personal
                    </div>
                </td>
            </tr>

            <!-- Personal Data -->
            <tr>
                <td colspan="4" class="section-header">DATOS DEL SERVIDOR</td>
            </tr>
            <tr>
                <td class="text-bold" style="width: 25%; background-color: #f9fafb;">APELLIDOS Y NOMBRES</td>
                <td colspan="3">{{ $entry->nombre_personal }}</td>
            </tr>
            <tr>
                <td class="text-bold" style="background-color: #f9fafb;">D.N.I.</td>
                <td style="width: 25%;">{{ $entry->dni }}</td>
                <td class="text-bold" style="width: 20%; background-color: #f9fafb;">CARGO/RÉGIMEN</td>
                <td>{{ $entry->regimen ?? '-' }}</td>
            </tr>
            <tr>
                <td class="text-bold" style="background-color: #f9fafb;">DEPENDENCIA</td>
                <td colspan="3">SEDE CENTRAL - DRE HUÁNUCO</td>
            </tr>

            <!-- Duration -->
            <tr>
                <td colspan="4" class="section-header">DURACIÓN</td>
            </tr>
            <tr>
                <td class="text-bold text-center" style="background-color: #f9fafb;">HORA DE INICIO (SALIDA)</td>
                <td class="text-center text-bold" style="font-size: 12px;">
                    {{ $entry->hora_salida ? $entry->hora_salida->format('H:i A') : '--:--' }}
                </td>
                <td class="text-bold text-center" style="background-color: #f9fafb;">HORA DE TÉRMINO (RETORNO)</td>
                <td class="text-center text-bold" style="font-size: 12px;">
                    {{ $entry->hora_retorno ? $entry->hora_retorno->format('H:i A') : '' }}
                </td>
            </tr>

            <!-- Action / Justification -->
            <tr>
                <td colspan="4" class="section-header">ACCIÓN A CUMPLIR O JUSTIFICACIÓN DEL PERMISO</td>
            </tr>
            <tr>
                <td colspan="4" style="height: 50px; vertical-align: top;">
                    {{ $entry->motivo }}
                </td>
            </tr>

            <!-- Destination Stamp -->
            <tr>
                <td colspan="4" class="section-header">CONSTANCIA DE DESTINO (COMISIÓN)</td>
            </tr>
            <tr>
                <td colspan="4" style="padding: 10px;">
                    <div class="dashed-box">
                        <div style="position: absolute; bottom: 5px; width: 100%; text-align: center; color: #9ca3af; font-size: 8px;">
                            SELLO Y FIRMA DE LA ENTIDAD DE DESTINO
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Signatures -->
        <table class="signature-area">
            <tr>
                <td style="width: 50%;">
                    <span class="signature-line"></span>
                    <span class="text-bold" style="font-size: 9px;">FIRMA DEL SERVIDOR</span>
                    <br><span style="font-size: 8px;">DNI: {{ $entry->dni }}</span>
                </td>
                <td style="width: 50%;">
                    <span class="signature-line"></span>
                    <span class="text-bold" style="font-size: 9px;">V°B° JEFE INMEDIATO</span>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="height: 20px;"></td>
            </tr>
            <tr>
                <td>
                    <span class="signature-line"></span>
                    <span class="text-bold" style="font-size: 9px;">VIGILANCIA (SALIDA)</span>
                </td>
                <td>
                    <span class="signature-line"></span>
                    <span class="text-bold" style="font-size: 9px;">VIGILANCIA (RETORNO)</span>
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <div style="text-align: center; font-size: 7px; color: #9ca3af; margin-top: 15px; border-top: 1px solid #e5e7eb; padding-top: 5px;">
            Sistema de Control de Asistencia - DRE Huánuco | Fecha de impresión: {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>
</body>
</html>
