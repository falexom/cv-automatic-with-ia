<?php

namespace App\Support\CvAdaptation;

use App\Models\CvAdaptation;
use App\Models\CvAdaptationObservation;
use Illuminate\Support\Collection;

final class CvAdaptationDetail
{
    /**
     * @return array<string, mixed>
     */
    public function build(CvAdaptation $adaptation): array
    {
        $context = $adaptation->context;
        $requirements = is_array($context['requirements'] ?? null)
            ? $context['requirements']
            : [];

        return [
            'id' => $adaptation->id,
            'operation' => $adaptation->operation,
            'job_title' => $adaptation->job_title,
            'company' => $adaptation->company,
            'analyzed_at' => $adaptation->analyzed_at->toIso8601String(),
            'confirmed_at' => $adaptation->confirmed_at->toIso8601String(),
            'context' => $context,
            'counts' => $this->counts($requirements, $adaptation->observations),
            'observations' => $adaptation->observations
                ->map(fn ($observation): array => [
                    'id' => $observation->id,
                    'type' => $observation->type,
                    'reference' => $observation->reference,
                    'status' => $observation->status,
                    'note' => $observation->note,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $requirements
     * @param  Collection<int, CvAdaptationObservation>  $observations
     * @return array<string, int>
     */
    private function counts(array $requirements, Collection $observations): array
    {
        $byCoverage = collect($requirements)->countBy('coverage');

        $supported = (int) ($byCoverage['supported'] ?? 0);
        $partial = (int) ($byCoverage['partial'] ?? 0);
        $gap = (int) ($byCoverage['gap'] ?? 0);
        $needsConfirmation = (int) ($byCoverage['needs_confirmation'] ?? 0);

        return [
            'supported' => $supported,
            'partial' => $partial,
            'gap' => $gap,
            'needs_confirmation' => $needsConfirmation,
            'pending_coverage' => $gap + $needsConfirmation,
            'total' => count($requirements),
            'pending_observations' => $observations->where('status', 'pending')->count(),
        ];
    }
}
