<?php

namespace App\Services;

class ResolutionFilenameParser
{
    /** Reject clear auxiliary filenames, not words appearing inside a resolution's subject. */
    public function isAuxiliary(string $filename): bool
    {
        $stem = mb_strtoupper(trim(preg_replace('/(?:\.\s*pdf)+$/ui', '', $filename)));
        return (bool) preg_match('/^(?:(?:\d{1,6}[\s._-]+)?(?:ACTUAD|ACUTAD)[\p{L}]*\b|NOTIFICACI[OÓ]N\b|C[ÉE]DULA\s+DE\s+NOTIFICACI[OÓ]N\b|OFICIO\b|MEMOR[AÁ]ND(?:O|UM)\b|INFORME\b|CARTA\b|~\$|CX?$)/u', $stem);
    }
    /** Display the subject from the filename; never read or change the PDF or saved metadata. */
    public function title(string $filename): string
    {
        $stem = trim(preg_replace('/(?:\.\s*pdf)+$/ui', '', $filename));
        $name = $this->normalizeIdentifier($stem);
        $series = '(?:RDRR|RRDR|RER|RDR|ROI|RD|DR|R)';
        $year = '(?:19|20)\d{2,4}(?!\d)';
        $patterns = [
            '/^\s*\d{1,6}(?:\s*[-–]\s*\d{1,6})?[\s.:-]*'.$series.'(?=\b|'.$year.')[\s.:-]*(?:'.$year.')?[\s.:-]*/ui',
            '/^\s*\d{1,6}\s+'.$year.'[\s.:-]*(?:'.$series.'\b[\s.:-]*)?/ui',
            '/^\s*(?:RDR|ROI|RD)\s*(?:N[º°o.]?\s*)?[-\s]*\d{1,6}(?:\s*[-–]\s*'.$year.')?[\s.:-]*/ui',
            // A zero-padded leading identifier is clear even if the series is absent.
            '/^\s*0\d{2,5}\s+/u',
        ];
        foreach ($patterns as $pattern) {
            $title = preg_replace($pattern, '', $name, 1, $count);
            if ($count) return trim($title) !== '' ? trim($title) : $stem;
        }
        return $stem;
    }

    private function normalizeIdentifier(string $name): string
    {
        $prefix = '(\s*(?:N[º°o.]\s*)?(?:\d{1,6}(?:\s*[-–]\s*\d{1,6})?[\s.:-]*)?)';
        $name = preg_replace('/^'.$prefix.'R\.\s*D\.\s*R\.?/ui', '$1RDR', $name);
        $name = preg_replace('/^'.$prefix.'R\.\s*D\.(?!\s*R)/ui', '$1RD', $name);
        return preg_replace('/^\s*N[º°o.]\s*/ui', '', $name);
    }

    /** Interpret only the primary identifier; references in the subject are not identifiers. */
    public function parse(string $filename, string $folder = '', ?int $currentYear = null): array
    {
        $currentYear ??= (int) (new \DateTimeImmutable('now', new \DateTimeZone('America/Lima')))->format('Y');
        $name = mb_strtoupper($this->normalizeIdentifier(pathinfo($filename, PATHINFO_FILENAME)));
        $series = $number = $end = $year = null;
        if (preg_match('/^\s*(\d{1,6})(?:\s*[-–]\s*(\d{1,6}))?[\s.:-]*(RDR|ROI|RD)(?=\b|(?:19|20)\d{2,4}(?!\d))[\s.:-]*((?:19|20)\d{2,4}(?!\d))?/u', $name, $m)) {
            $number = (int) $m[1];
            $end = !empty($m[2]) ? (int) $m[2] : null;
            $series = $m[3];
            $year = !empty($m[4]) ? (int) $m[4] : null;
        } elseif (preg_match('/^\s*(\d{1,6})\s+((?:19|20)\d{2,4}(?!\d))\s+(RDR|ROI|RD)\b/u', $name, $m)) {
            $number = (int) $m[1];
            $year = (int) $m[2];
            $series = $m[3];
        } elseif (preg_match('/^\s*(\d{1,6})\s+(?:(?:RDRR|RRDR|RER|DR|R)\s+)?((?:19|20)\d{2,4}(?!\d))(?:\s|$)/u', $name, $m)) {
            // Preserve the identifier; an explicitly named folder can supply its series.
            $number = (int) $m[1];
            $year = (int) $m[2];
        } elseif (preg_match('/^\s*(RDR|ROI|RD)\s*(?:N[º°o.]?\s*)?[-\s]*(\d{1,6})(?:\s*[-–]\s*((?:19|20)\d{2,4}(?!\d)))?(?:\D|$)/ui', $name, $m)) {
            $series = $m[1];
            $number = (int) $m[2];
            $year = !empty($m[3]) ? (int) $m[3] : null;
        } elseif (preg_match('/^\s*(RDR|ROI|RD)\b/u', $name, $m)) {
            // RDR - (1).pdf may be an automatic rename, not a resolution number.
            $series = $m[1];
        }
        // Number-year before the series is one resolution, not a range ending in that year.
        if ($year === null && $end !== null && $end >= 1900 && $end <= $currentYear
            && preg_match('/^\s*\d{1,6}\s*[-–]\s*((?:19|20)\d{2})\s+(?:RDR|ROI|RD)\b/u', $name)) {
            $year = $end; $end = null;
        }
        // A leading zero-padded number belongs to this file, even when its subject cites another resolution.
        if ($number === null && preg_match('/^\s*(0\d{2,5})\s+(?=\p{L})/u', $name, $m)) {
            $number = (int) $m[1];
        }
        // Never truncate 20213 to 2021 or accept a future year. A valid year
        // immediately after the identifier wins over years mentioned in the subject.
        if ($year !== null && ($year < 1900 || $year > $currentYear)) $year = null;
        if ($year === null) {
            $year = $this->folderYear($folder, $currentYear);
        }
        if ($number === 0 || ($end !== null && $end < $number)) {
            $number = $end = null;
        }
        // The explicitly classified archive folder is authoritative, including
        // abbreviated or mistyped identifiers. Generic folders keep the identifier.
        if (!$this->isAuxiliary($filename) && ($folderSeries = $this->folderSeries($folder)) !== null) $series = $folderSeries;
        return ['series' => $series, 'year' => $year, 'number_start' => $number, 'number_end' => $end];
    }

    public function folderSeries(string $folder): ?string
    {
        foreach (array_reverse(explode('/', str_replace('\\', '/', $folder))) as $part) {
            $part = mb_strtoupper($this->normalizeIdentifier($part));
            preg_match_all('/(?<![\p{L}])(RDR|ROI|RD)(?=[\s._-]|\d|$)/u', $part, $matches);
            $series = array_values(array_unique($matches[1]));
            if (count($series) === 1) return $series[0];
            if (count($series) > 1) return null;
        }
        return null;
    }

    /** Closest named annual folder wins; parent years and number ranges do not. */
    public function folderYear(string $folder, ?int $currentYear = null): ?int
    {
        $currentYear ??= (int) (new \DateTimeImmutable('now', new \DateTimeZone('America/Lima')))->format('Y');
        foreach (array_reverse(explode('/', str_replace('\\', '/', $folder))) as $part) {
            if (!preg_match('/RDR|\bRD\b|ROI|RESOLUCI[OÓ]N|^\s*(?:19|20)\d{2}\s*$/ui', $part)) continue;
            preg_match_all('/(?<!\d)((?:19|20)\d{2})(?!\d)/', $part, $matches);
            $years = array_values(array_unique(array_filter(array_map('intval', $matches[1]), fn($y) => $y <= $currentYear)));
            if (count($years) === 1) return $years[0];
        }
        return null;
    }
}
