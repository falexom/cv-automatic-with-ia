<?php

namespace App\Support;

final readonly class CvPdfChromeResolution
{
    public function __construct(
        public ?string $path,
        public ?string $source = null,
        public ?string $error = null,
    ) {}

    public function isAvailable(): bool
    {
        return $this->path !== null;
    }
}
