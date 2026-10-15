<?php

namespace App\Support;

final class NameNormalizer
{
    /**
     * Normalize a name for duplicate detection: lowercase, trim and
     * collapse inner whitespace. Accents are preserved (FR-020, FR-018).
     */
    public static function normalize(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }
}
