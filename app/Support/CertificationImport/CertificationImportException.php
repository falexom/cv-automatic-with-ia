<?php

namespace App\Support\CertificationImport;

use RuntimeException;

final class CertificationImportException extends RuntimeException
{
    /**
     * @param  array<string, string>  $errors
     */
    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly int $status = 422,
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
