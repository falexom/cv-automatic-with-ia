<?php

namespace App\Support\CertificationImport;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

/**
 * Client-held, encrypted proposal. It is never persisted on the server;
 * its opaque confirmation id only protects against repeated submissions.
 */
final class CertificationImportProposalToken
{
    /**
     * @param  array<string, mixed>  $proposal
     * @param  array<int, int>  $existingSkillIds
     */
    public function issue(
        array $proposal,
        string $checksum,
        string $originalFilename,
        ?string $existingCategory,
        array $existingSkillIds,
    ): string {
        return Crypt::encryptString(json_encode([
            'schema_version' => 1,
            'confirmation_id' => (string) Str::uuid(),
            'analyzed_at' => now()->toIso8601String(),
            'file_checksum' => $checksum,
            'original_filename' => $originalFilename,
            'proposal' => $proposal,
            'existing_category' => $existingCategory,
            'existing_skill_ids' => array_values(array_unique(array_map('intval', $existingSkillIds))),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{
     *     confirmation_id: string,
     *     file_checksum: string,
     *     original_filename: string,
     *     proposal: array<string, mixed>,
     *     existing_category: string|null,
     *     existing_skill_ids: array<int, int>
     * }
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
            || ! is_string($payload['confirmation_id'] ?? null)
            || preg_match('/^[0-9a-f-]{36}$/i', $payload['confirmation_id']) !== 1
            || ! is_string($payload['file_checksum'] ?? null)
            || preg_match('/^[a-f0-9]{64}$/', $payload['file_checksum']) !== 1
            || ! is_string($payload['original_filename'] ?? null)
            || trim($payload['original_filename']) === ''
            || mb_strlen($payload['original_filename']) > 255
            || ! is_array($payload['proposal'] ?? null)
            || ! is_array($payload['proposal']['fields'] ?? null)
            || ! is_array($payload['proposal']['skills'] ?? null)
            || ! is_array($payload['proposal']['ambiguities'] ?? null)
            || ! array_key_exists('existing_category', $payload)
            || ($payload['existing_category'] !== null && (
                ! is_string($payload['existing_category'])
                || mb_strlen($payload['existing_category']) > 100
            ))
            || ! is_array($payload['existing_skill_ids'] ?? null)
            || ! is_string($payload['analyzed_at'] ?? null)
        ) {
            throw $this->invalidToken();
        }

        $existingSkillIds = [];

        foreach ($payload['existing_skill_ids'] as $skillId) {
            if (! is_int($skillId)) {
                throw $this->invalidToken();
            }

            $existingSkillIds[] = $skillId;
        }

        return [
            'confirmation_id' => $payload['confirmation_id'],
            'file_checksum' => $payload['file_checksum'],
            'original_filename' => $payload['original_filename'],
            'proposal' => $payload['proposal'],
            'existing_category' => $payload['existing_category'],
            'existing_skill_ids' => $existingSkillIds,
        ];
    }

    private function invalidToken(): CertificationImportException
    {
        return new CertificationImportException(
            'invalid_confirmation',
            'La propuesta ya no es válida. Volvé a analizar el PDF o continuá manualmente.',
            422,
        );
    }
}
