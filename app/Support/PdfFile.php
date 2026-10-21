<?php

namespace App\Support;

/**
 * Structural PDF checks used at upload time (FR-002). A full parser is
 * intentionally avoided: no new dependencies (research.md §2).
 */
class PdfFile
{
    /**
     * 20 MB per file (spec assumptions); PHP itself must allow it
     * (see quickstart).
     */
    public const MAX_KILOBYTES = 20480;

    public static function looksLikePdf(string $contents): bool
    {
        if (! str_starts_with($contents, '%PDF-')) {
            return false;
        }

        $tail = strlen($contents) > 1024 ? substr($contents, -1024) : $contents;

        return str_contains($tail, '%%EOF');
    }

    /**
     * Safe value for the Content-Disposition header.
     */
    public static function sanitizeFilename(string $name): string
    {
        $clean = preg_replace('/[\x00-\x1F\x7F"\\\\\/]+/u', '', $name) ?? '';
        $clean = trim($clean);

        return $clean === '' ? 'documento.pdf' : $clean;
    }
}
