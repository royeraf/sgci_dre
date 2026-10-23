<?php

namespace App\Services;

class ResolutionSearch
{
    /** Recognize only complete identifiers. Free text is never partially discarded. */
    public function interpret(string $query): array
    {
        $query = trim($query);
        $number = $year = $series = null;
        if (preg_match('/^(?:(RDR|ROI|RD)\s*(?:N[º°o.]?\s*)?)?(\d{1,6})\s*[-\/\s]\s*((?:19|20)\d{2})$/ui', $query, $m)) {
            $series = !empty($m[1]) ? mb_strtoupper($m[1]) : null;
            $number = (int) $m[2]; $year = (int) $m[3];
        } elseif (preg_match('/^(\d{1,6})\s*(RDR|ROI|RD)\s*((?:19|20)\d{2})$/ui', $query, $m)) {
            $number = (int) $m[1]; $series = mb_strtoupper($m[2]); $year = (int) $m[3];
        } elseif (preg_match('/^(?:(RDR|ROI|RD)\s*(?:N[º°o.]?\s*)?)?(\d{1,6})$/ui', $query, $m)) {
            $series = !empty($m[1]) ? mb_strtoupper($m[1]) : null;
            $number = (int) $m[2];
        }
        if (!$number) return ['q' => $query, 'number' => null, 'year' => null, 'series' => null];
        return ['q' => '', 'number' => $number, 'year' => $year, 'series' => $series];
    }
}
