<?php

namespace App\Support;

use Illuminate\Support\Str;

final class CvPdfFilename
{
    public static function fromProfileName(?string $name): string
    {
        $base = self::base($name);

        return $base === '' ? 'cv.pdf' : "cv-{$base}.pdf";
    }

    public static function documentedFromProfileName(?string $name): string
    {
        $base = self::base($name);

        return $base === '' ? 'cv-documentado.pdf' : "cv-{$base}-documentado.pdf";
    }

    public static function markdownFromProfileName(?string $name): string
    {
        $base = self::base($name);

        return $base === '' ? 'cv.md' : "cv-{$base}.md";
    }

    private static function base(?string $name): string
    {
        $ascii = Str::lower(Str::ascii((string) $name));
        $base = preg_replace('/[^a-z0-9]+/', '-', $ascii) ?? '';
        $base = trim($base, '-');
        $base = substr($base, 0, 80);
        $base = rtrim($base, '-');

        return $base;
    }
}
