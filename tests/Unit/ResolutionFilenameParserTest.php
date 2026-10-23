<?php

namespace Tests\Unit;

use App\Services\ResolutionFilenameParser;
use PHPUnit\Framework\TestCase;

class ResolutionFilenameParserTest extends TestCase
{
    public function test_recognizes_the_archive_formats_without_reading_pdf(): void
    {
        $parser = new ResolutionFilenameParser;
        $cases = [
            ['RDR-001.pdf', 'RDR DRE HCO 2000', 'RDR', 2000, 1, null],
            ['00123 - R.D.R. 2024 APROBAR.pdf', '', 'RDR', 2024, 123, null],
            ['Nº 00123 RDR 2024 APROBAR.pdf', '', 'RDR', 2024, 123, null],
            ['RDR Nº 1000 - 2006.pdf', '', 'RDR', 2006, 1000, null],
            ['00003 - 00053 RDR 2006.pdf', '', 'RDR', 2006, 3, 53],
            ['0001 RDR 2021 DECLARAR NO HA LUGAR, RDUGEL 0408 2020.pdf', '', 'RDR', 2021, 1, null],
            ['0002 RDR.ACEPTAR RENUNCIA pdf.pdf', 'RDR DRE HCO. 2019', 'RDR', 2019, 2, null],
            ['0001 ROI 2021INICIAR PROCESO ADM.pdf', '', 'ROI', 2021, 1, null],
            ['RDR -  (1).pdf', 'RDR DRE HCO 2005', 'RDR', 2005, null, null],
            ['escaneo.pdf', '', null, null, null, null],
            ['00574 RDR2026 RECONOCER DEUDAS.pdf', '', 'RDR', 2026, 574, null],
            ['00172 RDRR 2026 DECLARAR.pdf', '', null, 2026, 172, null],
            ['00466 rRDR 2026 AUTORIZAR.pdf', '', null, 2026, 466, null],
            ['00531 R 2026 APROBAR.pdf', '', null, 2026, 531, null],
            ['00534 RER 2026 APROBAR.pdf', '', null, 2026, 534, null],
            ['00609 2026 INSCRIBIR.pdf', '', null, 2026, 609, null],
            ['00779 2026 RDR NO INSTAURAR.pdf', '', 'RDR', 2026, 779, null],
            ['01040 DR 2026 RECONOCER.pdf', '', null, 2026, 1040, null],
            ['01540 RECTIFICAR LOS ALCANCES DE LA RDR 01404-2026, DE CAJALEON EULOGIO JOSE CARLOS RDR 2026.pdf', 'RDR DRE HCO. 2026', 'RDR', 2026, 1540, null],
            ['01540 RECTIFICAR LA RD 01404-2025.pdf', 'RDR DRE HCO. 2026', 'RD', 2026, 1540, null],
            ['01540 RECTIFICAR ALCANCES.pdf', 'RDR DRE HCO. 2026', null, 2026, 1540, null],
            ['00053 - 00003 RDR 2006.pdf', '', 'RDR', 2006, null, null],
            ['02653 RDR 2034 APROBAR PLAN HUANUCO 2024.pdf', 'RDR DRE HCO. 2024', 'RDR', 2024, 2653, null],
            ['03097 RDR 2027 DAR CUMPLIMIENTO.pdf', 'RDR DRE HCO. 2024', 'RDR', 2024, 3097, null],
            ['0148 RDR 20213.pdf', 'RDR DRE HCO. 2013', 'RDR', 2013, 148, null],
            ['01000 RDR 20225 AUTORIZAR.pdf', 'RDR DRE HCO. 2025', 'RDR', 2025, 1000, null],
            ['RDR-2034.pdf', 'RDR DRE HCO 2001', 'RDR', 2001, 2034, null],
            ['RDR-001.pdf', 'RDR DRE HCO. 2019/RDR DRE HCO. 2015', 'RDR', 2015, 1, null],
            ['RDR-001.pdf', 'RDR DRE HCO. 2023/RDR DRE HCO. 2022', 'RDR', 2022, 1, null],
            ['0001 RDR 2034.pdf', '', 'RDR', null, 1, null],
            ['2251 RDR 2021.pdf', 'RDR DRE HCO. 2016', 'RDR', 2021, 2251, null],
            ['RDR-001.pdf', 'RDR DRE HCO. 2020/2001-2100', 'RDR', 2020, 1, null],
        ];
        foreach ($cases as [$name, $folder, $series, $year, $number, $end]) {
            $this->assertSame(['series' => $series, 'year' => $year, 'number_start' => $number, 'number_end' => $end], $parser->parse($name, $folder, 2026), $name);
        }
    }

    public function test_subjects_are_extracted_from_variants_without_changing_embedded_references(): void
    {
        $parser = new ResolutionFilenameParser;
        $cases = [
            '00172 RDRR 2026 DECLARAR IMPROCEDENTE.pdf'=>'DECLARAR IMPROCEDENTE',
            '00466 rRDR 2026 AUTORIZAR LA REUBICACIÓN.pdf'=>'AUTORIZAR LA REUBICACIÓN',
            '00531 R 2026 APROBAR CONTRATO.pdf'=>'APROBAR CONTRATO',
            '00534 RER 2026 APROBAR CONTRATO.pdf'=>'APROBAR CONTRATO',
            '00574 RDR2026 RECONOCER DEUDAS.pdf'=>'RECONOCER DEUDAS',
            '00609 2026 INSCRIBIR LOS TÍTULOS.pdf'=>'INSCRIBIR LOS TÍTULOS',
            '00779 2026 RDR NO INSTAURAR.pdf'=>'NO INSTAURAR',
            '01040 DR 2026 RECONOCER.pdf'=>'RECONOCER',
            '01540 RECTIFICAR LA RDR 01404-2026.pdf'=>'RECTIFICAR LA RDR 01404-2026',
            '01540 RECTIFICAR LA R.D.R. 01404-2026.pdf'=>'RECTIFICAR LA R.D.R. 01404-2026',
            '00123 - R.D.R. 2024 APROBAR.pdf'=>'APROBAR',
            'RDR Nº 123-2024 APROBAR.pdf'=>'APROBAR',
            'Nº 00123 RDR 2024 APROBAR.pdf'=>'APROBAR',
            '00003 - 00053 RDR 2006.pdf'=>'00003 - 00053 RDR 2006',
            'RDR-001.pdf'=>'RDR-001',
            'sin identificar.pdf'=>'sin identificar',
        ];
        foreach ($cases as $filename=>$title) $this->assertSame($title,$parser->title($filename),$filename);
    }
}
