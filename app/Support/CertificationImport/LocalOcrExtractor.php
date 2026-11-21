<?php

namespace App\Support\CertificationImport;

interface LocalOcrExtractor
{
    /**
     * Extract text locally from the requested original page numbers.
     *
     * @param  array<int, int>  $pageNumbers
     * @return array<int, array{page: int, text: string, has_content?: bool}>
     */
    public function extract(string $contents, array $pageNumbers): array;

    /**
     * @return array<int, string>
     */
    public function usedLanguages(): array;
}
