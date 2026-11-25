<?php

namespace App\Support;

final readonly class CvPdfChromeCheck
{
    public function __construct(
        public bool $ok,
        public ?int $bytes = null,
        public ?string $error = null,
    ) {}
}
