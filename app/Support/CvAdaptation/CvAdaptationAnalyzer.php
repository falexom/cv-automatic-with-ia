<?php

namespace App\Support\CvAdaptation;

interface CvAdaptationAnalyzer
{
    /**
     * @param  array<string, mixed>  $catalog
     * @return array<string, mixed>
     */
    public function analyze(array $catalog, string $offer, ?string $strategy = null): array;
}
