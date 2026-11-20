<?php

namespace App\Support\CertificationImport;

interface PdfTextExtractor
{
    /**
     * Extract native, page-separated text from the PDF contents.
     *
     * @return array<int, array{page: int, text: string}>
     */
    public function extract(string $contents): array;
}
