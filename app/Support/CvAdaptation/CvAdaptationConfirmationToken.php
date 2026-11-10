<?php

namespace App\Support\CvAdaptation;

use App\Models\CvAdaptation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

final class CvAdaptationConfirmationToken
{
    /**
     * @param  array<string, mixed>  $proposal
     */
    public function issue(array $proposal, string $nonce): string
    {
        $payload = [
            'schema_version' => 1,
            'confirmation_id' => (string) Str::uuid(),
            'ref_nonce' => $nonce,
            'analyzed_at' => now()->toIso8601String(),
            'operation' => $proposal['operation'],
            'source_profile_ref' => $proposal['source_profile_ref'],
            'rationale' => $proposal['rationale'],
            'requirements' => $proposal['requirements'],
            'gaps' => $proposal['gaps'],
            'development_recommendations' => $proposal['development_recommendations'],
            'clarifying_questions' => $proposal['clarifying_questions'],
        ];

        return Crypt::encryptString(json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function parse(string $token): array
    {
        try {
            $decrypted = Crypt::decryptString($token);
            $payload = json_decode($decrypted, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw $this->invalidToken();
        }

        if (! is_array($payload) || ($payload['schema_version'] ?? null) !== 1) {
            throw $this->invalidToken();
        }

        foreach ([
            'confirmation_id',
            'ref_nonce',
            'analyzed_at',
            'operation',
            'source_profile_ref',
            'rationale',
            'requirements',
            'gaps',
            'development_recommendations',
            'clarifying_questions',
        ] as $key) {
            if (! array_key_exists($key, $payload)) {
                throw $this->invalidToken();
            }
        }

        if (
            ! is_string($payload['confirmation_id'])
            || preg_match('/^[0-9a-f-]{36}$/i', $payload['confirmation_id']) !== 1
            || ! is_string($payload['ref_nonce'])
            || preg_match('/^[a-f0-9]{32}$/', $payload['ref_nonce']) !== 1
            || ! is_string($payload['analyzed_at'])
            || ! in_array($payload['operation'], CvAdaptation::OPERATIONS, true)
            || ($payload['source_profile_ref'] !== null && (
                ! is_string($payload['source_profile_ref'])
                || preg_match('/^cv_[a-f0-9]{12}$/', $payload['source_profile_ref']) !== 1
            ))
        ) {
            throw $this->invalidToken();
        }

        try {
            CarbonImmutable::parse($payload['analyzed_at']);
        } catch (Throwable) {
            throw $this->invalidToken();
        }

        foreach ([
            'rationale',
            'requirements',
            'gaps',
            'development_recommendations',
            'clarifying_questions',
        ] as $key) {
            if (! is_array($payload[$key])) {
                throw $this->invalidToken();
            }
        }

        return $payload;
    }

    private function invalidToken(): CvAdaptationException
    {
        return new CvAdaptationException(
            'invalid_confirmation',
            'La confirmación ya no es válida. Volvé a analizar la oferta.',
            422,
        );
    }
}
