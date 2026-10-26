<?php

namespace App\Support;

class CvPdfValidator
{
    public function isValid(string $pdf): bool
    {
        if ($pdf === '' || ! PdfFile::looksLikePdf($pdf)) {
            return false;
        }

        if (! preg_match('~/Type\s*/Pages(?![A-Za-z])~', $pdf)) {
            return false;
        }

        if (! preg_match('~/Type\s*/Page(?![A-Za-z])~', $pdf)) {
            return false;
        }

        $tail = strlen($pdf) > 1024 ? substr($pdf, -1024) : $pdf;

        return str_contains($tail, 'startxref');
    }
}
