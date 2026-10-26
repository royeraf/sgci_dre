<?php
namespace Tests\Unit;

use App\Services\ResolutionCoverage;
use App\Services\ResolutionFilenameParser;
use PHPUnit\Framework\TestCase;

class ResolutionCoverageTest extends TestCase
{
    public function test_gaps_merge_ranges_and_duplicates_and_separate_years_and_types(): void
    {
        $rows = [];
        foreach ([[2026,'RDR',1,null],[2026,'RDR',3,null],[2026,'RDR',5,7],[2026,'RDR',7,10],[2026,'RDR',6,null],[2026,'RD',4,null],[2024,'RDR',2,null],[2026,null,2,null]] as [$year,$series,$first,$last]) $rows[] = ['year'=>$year,'series'=>$series,'number_start'=>$first,'number_end'=>$last];
        $report = (new ResolutionCoverage)->analyze($rows);
        $groups = array_column($report['groups'],null,'series');
        $rdr = array_values(array_filter($report['groups'],fn($row)=>$row['year']===2026 && $row['series']==='RDR'))[0];
        $this->assertSame([[2,2],[4,4]],$rdr['gaps']);
        $this->assertSame(2,$rdr['missing']);
        $this->assertSame(10,$rdr['last']);
        $this->assertSame(5,$rdr['files']);
        $this->assertSame(8,$rdr['unique']);
        $this->assertSame(0,$rdr['repeated_numbers']);
        $this->assertSame([[1,3]],$groups['RD']['gaps']);
        $this->assertSame(['2026'=>1],$report['pending']);
        $this->assertSame(['groups'=>[],'pending'=>[]],(new ResolutionCoverage)->analyze([]));
    }

    public function test_auxiliary_rules_do_not_reject_a_resolution_subject(): void
    {
        $parser = new ResolutionFilenameParser;
        $this->assertTrue($parser->isAuxiliary('~$006 RDR 2024 DECLARAR IMPROCEDENTE.pdf'));
        foreach (['291 ACTUADOS RDR 2024.pdf','792 ACTUADORS RDR 2024.pdf','817 ACUTADOS RDR 2024.pdf','ACTUADOS RDR 2024.pdf','NOTIFICACIÓN ROI 0003-2025 HECTOR ARAGON.pdf','c.pdf','cx.pdf','OFICIO 23-2024.pdf'] as $name) $this->assertTrue($parser->isAuxiliary($name),$name);
        foreach (['00291 RDR 2024 APROBAR ACTUADOS.pdf','00003 RDR 2026 NOTIFICACION DE RESULTADOS.pdf','escaneo.pdf','001 RDR 2026.pdf'] as $name) $this->assertFalse($parser->isAuxiliary($name),$name);
    }
}
