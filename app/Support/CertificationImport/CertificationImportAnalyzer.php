<?php

namespace App\Support\CertificationImport;

interface CertificationImportAnalyzer
{
    /**
     * @param  array<int, array{ref: string, name: string, category: string}>  $skills
     * @param  array<int, array{page: int, text: string, origin?: string}>  $pages
     * @return array<string, mixed>
     */
    public function analyze(array $skills, array $pages): array;
}
