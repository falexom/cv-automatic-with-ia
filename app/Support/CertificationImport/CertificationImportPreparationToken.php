<?php

namespace App\Support\CertificationImport;

use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Client-held, encrypted reference to the PDF that was prepared. It keeps
 * no server-side temporary state and binds the later confirmation to the
 * exact checksum that was analyzed.
 */
final class CertificationImportPreparationToken
{
    public function __construct(
        private readonly CertificationImportConfig $config,
    ) {}

    public function issue(string $checksum, string $originalFilename, int $sizeBytes, int $pageCount): string
    {
        return Crypt::encryptString(json_encode([
            'schema_version' => 1,
            'checksum' => $checksum,
            'original_filename' => $originalFilename,
            'size_bytes' => $sizeBytes,
            'page_count' => $pageCount,
            'prepared_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{checksum: string, original_filename: string, size_bytes: int, page_count: int}
     */
    public function parse(string $token): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw $this->invalidToken();
        }

        if (
            ! is_array($payload)
            || ($payload['schema_version'] ?? null) !== 1
            || ! is_string($payload['checksum'] ?? null)
            || preg_match('/^[a-f0-9]{64}$/', $payload['checksum']) !== 1
            || ! is_string($payload['original_filename'] ?? null)
            || trim($payload['original_filename']) === ''
            || mb_strlen($payload['original_filename']) > 255
            || ! is_int($payload['size_bytes'] ?? null)
            || $payload['size_bytes'] <= 0
            || ! is_int($payload['page_count'] ?? null)
            || $payload['page_count'] < 1
            || $payload['page_count'] > $this->config->maxPages()
            || ! is_string($payload['prepared_at'] ?? null)
        ) {
            throw $this->invalidToken();
        }

        return [
            'checksum' => $payload['checksum'],
            'original_filename' => $payload['original_filename'],
            'size_bytes' => $payload['size_bytes'],
            'page_count' => $payload['page_count'],
        ];
    }

    private function invalidToken(): CertificationImportException
    {
        return new CertificationImportException(
            'invalid_preparation',
            'La preparación del PDF ya no es válida. Volvé a preparar el texto antes de analizar.',
            422,
        );
    }
}
