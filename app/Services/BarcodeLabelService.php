<?php

namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorPNG;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;

class BarcodeLabelService
{
    /**
     * Factor de conversión de milímetros a puntos Tipográficos (pt)
     * 1 pulgada = 25.4 mm = 72 pt -> 72 / 25.4 = 2.83464566929 pt/mm
     */
    const MM_TO_PT = 2.83464566929;

    /**
     * Generar imagen Base64 para Código de Barras (Code 128)
     */
    public function generateBarcodeBase64(string $code, int $widthFactor = 2, int $height = 50): string
    {
        $cleanCode = trim($code);
        if (empty($cleanCode)) {
            $cleanCode = '00000000';
        }

        $generator = new BarcodeGeneratorPNG();
        $png = $generator->getBarcode($cleanCode, $generator::TYPE_CODE_128, $widthFactor, $height);

        return 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * Generar imagen Base64 para Código QR
     */
    public function generateQrBase64(string $text, int $size = 200, int $margin = 2): string
    {
        $cleanText = trim($text);
        if (empty($cleanText)) {
            $cleanText = '00000000';
        }

        $qrCode = new QrCode(
            data: $cleanText,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: $margin,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return $result->getDataUri();
    }

    /**
     * Calcular dimensiones de página en puntos (pt) para DomPDF
     */
    public function calculatePaperDimensions(
        string $mode, // 'thermal' o 'a4'
        int $columns, // 1 o 2
        float $labelWidthMm, // Ancho de etiqueta en mm
        float $labelHeightMm, // Alto de etiqueta en mm
        float $gapMm = 2.0, // Espaciado entre columnas en mm
        float $sideMarginsMm = 2.0 // Márgenes laterales del soporte en mm
    ): array {
        if ($mode === 'a4') {
            return [
                'width_pt' => 595.28, // A4 estándar
                'height_pt' => 841.89,
                'paper' => 'a4',
                'orientation' => 'portrait'
            ];
        }

        // Modo Térmico TD-402S
        if ($columns === 2) {
            // Ancho total del rollo de 2 columnas
            $totalWidthMm = ($labelWidthMm * 2) + $gapMm + ($sideMarginsMm * 2);
            $totalHeightMm = $labelHeightMm;
        } else {
            // Ancho de 1 columna
            $totalWidthMm = $labelWidthMm;
            $totalHeightMm = $labelHeightMm;
        }

        $widthPt = $totalWidthMm * self::MM_TO_PT;
        $heightPt = $totalHeightMm * self::MM_TO_PT;

        return [
            'width_mm' => $totalWidthMm,
            'height_mm' => $totalHeightMm,
            'width_pt' => round($widthPt, 2),
            'height_pt' => round($heightPt, 2),
            'paper' => [0, 0, round($widthPt, 2), round($heightPt, 2)],
            'orientation' => 'portrait'
        ];
    }

    /**
     * Retornar lista de bienes de ejemplo realistas para pruebas inmediatas
     */
    public function getSampleAssets(): array
    {
        return [
            [
                'id' => 'sample-1',
                'codigo_patrimonio' => '74643745',
                'codigo_interno' => '0137',
                'codigo_completo' => '746437450137',
                'codigo_barras' => '746437450137',
                'denominacion' => 'ESCRITORIO DE MELAMINA 3 CAJONES COLOR CEDRO',
                'numero_serie' => 'SN-2024-ESC-0137',
                'marca' => ['nombre' => 'MODERNA'],
                'modelo' => 'EJECUTIVO PLUS',
                'oficina_actual' => ['nombre' => 'DIRECCION REGIONAL'],
                'responsable_actual' => ['nombre_completo' => 'MG. WILLIAM ELEAZAR INGA V.'],
                'estado_actual' => 'BUENO',
            ],
            [
                'id' => 'sample-2',
                'codigo_patrimonio' => '74089500',
                'codigo_interno' => '0012',
                'codigo_completo' => '740895000012',
                'codigo_barras' => '740895000012',
                'denominacion' => 'SILLON GIRATORIO ERGONOMICO CON BRAZOS REGULABLES',
                'numero_serie' => 'SN-SILL-9982',
                'marca' => ['nombre' => 'KORS'],
                'modelo' => 'ERGOMASTER',
                'oficina_actual' => ['nombre' => 'GESTION PEDAGOGICA'],
                'responsable_actual' => ['nombre_completo' => 'LIC. MARIA CARMEN HUAMAN'],
                'estado_actual' => 'BUENO',
            ],
            [
                'id' => 'sample-3',
                'codigo_patrimonio' => '74087700',
                'codigo_interno' => '0045',
                'codigo_completo' => '740877000045',
                'codigo_barras' => '740877000045',
                'denominacion' => 'COMPUTADORA PORTATIL CORE I7 16GB RAM SSD 512GB',
                'numero_serie' => '5CD3428XYZ',
                'marca' => ['nombre' => 'HP'],
                'modelo' => 'PROBOOK 450 G9',
                'oficina_actual' => ['nombre' => 'INFORMATICA Y SISTEMAS'],
                'responsable_actual' => ['nombre_completo' => 'ING. ROBERTO SALAS M.'],
                'estado_actual' => 'BUENO',
            ],
            [
                'id' => 'sample-4',
                'codigo_patrimonio' => '74088100',
                'codigo_interno' => '0089',
                'codigo_completo' => '740881000089',
                'codigo_barras' => '740881000089',
                'denominacion' => 'IMPRESORA MULTIFUNCIONAL LASER MONOCROMATICA',
                'numero_serie' => 'BR-MFC-89123',
                'marca' => ['nombre' => 'BROTHER'],
                'modelo' => 'DCP-L5650DN',
                'oficina_actual' => ['nombre' => 'ADMINISTRACION'],
                'responsable_actual' => ['nombre_completo' => 'CPC. JUAN CARLOS PEREZ'],
                'estado_actual' => 'BUENO',
            ],
            [
                'id' => 'sample-5',
                'codigo_patrimonio' => '74221100',
                'codigo_interno' => '0023',
                'codigo_completo' => '742211000023',
                'codigo_barras' => '742211000023',
                'denominacion' => 'PROYECTOR MULTIMEDIA 4000 LUMENS WUXGA',
                'numero_serie' => 'EPS-EB-7421',
                'marca' => ['nombre' => 'EPSON'],
                'modelo' => 'POWERLITE 2250U',
                'oficina_actual' => ['nombre' => 'AUDITORIO PRINCIPAL'],
                'responsable_actual' => ['nombre_completo' => 'PROF. ELENA ROJAS C.'],
                'estado_actual' => 'BUENO',
            ],
            [
                'id' => 'sample-6',
                'codigo_patrimonio' => '74645200',
                'codigo_interno' => '0067',
                'codigo_completo' => '746452000067',
                'codigo_barras' => '746452000067',
                'denominacion' => 'MODULO DE ATENCION AL CIUDADANO EN MELAMINA',
                'numero_serie' => 'SN-MOD-0067',
                'marca' => ['nombre' => 'MUEBLESTIL'],
                'modelo' => 'MOD-AT-2024',
                'oficina_actual' => ['nombre' => 'TRAMITE DOCUMENTARIO'],
                'responsable_actual' => ['nombre_completo' => 'SRTA. GLORIA MENDOZA'],
                'estado_actual' => 'BUENO',
            ],
        ];
    }
}
