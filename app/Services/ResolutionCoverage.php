<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ResolutionCoverage
{
    public function report(?int $year = null): array
    {
        $report = Cache::remember('resolutions-coverage', 60, fn() => $this->analyze(
            DB::table('resolutions as r')->leftJoin('resolution_series as s','s.id','=','r.series_id')
                ->select('r.year','r.number_start','r.number_end','s.code as series')->cursor()
        ));
        $groups = array_values(array_filter($report['groups'], fn($group) => $year === null || $group['year'] === $year));
        return ['groups'=>$groups, 'missing'=>array_sum(array_column($groups,'missing')),
            'unidentified'=>$year === null ? array_sum($report['pending']) : ($report['pending'][(string)$year] ?? 0)];
    }

    /** Compare merged identifier intervals; never assume that the final uploaded number is the year's final issue. */
    public function analyze(iterable $records): array
    {
        $groups = []; $pending = [];
        foreach ($records as $record) {
            $r = (object) $record;
            $year = (int) ($r->year ?? 0); $start = (int) ($r->number_start ?? 0);
            $end = (int) ($r->number_end ?? $start); $series = $r->series ?? null;
            if (!$year || !$series || $start < 1 || $end < $start) { $key = (string) $year; $pending[$key] = ($pending[$key] ?? 0) + 1; continue; }
            $key = $year.':'.$series;
            $groups[$key] ??= ['year'=>$year,'series'=>$series,'files'=>0,'intervals'=>[]];
            $groups[$key]['files']++; $groups[$key]['intervals'][] = [$start,$end];
        }
        $result = [];
        foreach ($groups as $group) {
            usort($group['intervals'],fn($a,$b)=>$a[0]<=>$b[0] ?: $a[1]<=>$b[1]);
            $merged = [];
            foreach ($group['intervals'] as [$start,$end]) {
                $last = count($merged)-1;
                if ($last >= 0 && $start <= $merged[$last][1]+1) $merged[$last][1] = max($merged[$last][1],$end);
                else $merged[] = [$start,$end];
            }
            $next = 1; $gaps = []; $missing = 0;
            foreach ($merged as [$start,$end]) {
                if ($start > $next) { $gaps[] = [$next,$start-1]; $missing += $start-$next; }
                $next = $end+1;
            }
            $result[] = ['year'=>$group['year'],'series'=>$group['series'],'files'=>$group['files'],'last'=>$next-1,'missing'=>$missing,'gaps'=>$gaps];
        }
        usort($result,fn($a,$b)=>$b['year']<=>$a['year'] ?: strcmp($a['series'],$b['series']));
        return ['groups'=>$result,'pending'=>$pending];
    }
}
