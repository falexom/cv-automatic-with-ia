<?php

namespace App\Support;

use Illuminate\Support\Str;

final class CvPdfFilename
{
    public static function fromProfileName(?string $name): string
    {
        $ascii = Str::lower(Str::ascii((string) $name));
        $base = preg_replace('/[^a-z0-9]+/', '-', $ascii) ?? '';
        $base = trim($base, '-');
        $base = substr($base, 0, 80);
        $base = rtrim($base, '-');

        return $base === '' ? 'cv.pdf' : "cv-{$base}.pdf";
    }
}
