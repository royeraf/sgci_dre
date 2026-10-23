<?php

namespace Tests\Unit;

use App\Services\ResolutionSearch;
use PHPUnit\Framework\TestCase;

class ResolutionSearchTest extends TestCase
{
    public function test_identifiers_are_recognized_without_discarding_free_text(): void
    {
        $search = new ResolutionSearch;
        foreach (['125-2024', '000125 / 2024', 'RDR 00125-2024', '00125 RDR 2024'] as $query) {
            $result = $search->interpret($query);
            $this->assertSame(125, $result['number']);
            $this->assertSame(2024, $result['year']);
            $this->assertSame('', $result['q']);
        }
        foreach (['nombramiento José', 'RDR 125-2024 nombramiento', '0408 y 0410', '%_'] as $query) {
            $this->assertSame($query, $search->interpret($query)['q']);
            $this->assertNull($search->interpret($query)['number']);
        }
        $this->assertSame(2034, $search->interpret('2034')['number']);
        $this->assertNull($search->interpret('2034')['year']);
        $this->assertSame('RD', $search->interpret('RD 125-2024')['series']);
    }
}
