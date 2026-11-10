<?php

namespace App\Support\CvAdaptation;

final class CvAdaptationReferences
{
    public const WORK_EXPERIENCE = 'exp';

    public const EDUCATION_ENTRY = 'edu';

    public const CERTIFICATION = 'cert';

    public const SKILL = 'skill';

    public const PROJECT = 'proj';

    public const PROFILE = 'cv';

    /**
     * @var list<string>
     */
    private const PREFIXES = [
        self::WORK_EXPERIENCE,
        self::EDUCATION_ENTRY,
        self::CERTIFICATION,
        self::SKILL,
        self::PROJECT,
        self::PROFILE,
    ];

    /**
     * @var array<string, string>
     */
    public const CONTENT_PREFIXES = [
        'work_experiences' => self::WORK_EXPERIENCE,
        'education_entries' => self::EDUCATION_ENTRY,
        'certifications' => self::CERTIFICATION,
        'skills' => self::SKILL,
        'projects' => self::PROJECT,
    ];

    public function nonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function isValidNonce(string $nonce): bool
    {
        return preg_match('/^[a-f0-9]{32}$/', $nonce) === 1;
    }

    public function make(string $prefix, int $id, string $nonce): string
    {
        if (! in_array($prefix, self::PREFIXES, true)) {
            throw new \InvalidArgumentException("Prefijo de referencia no válido: {$prefix}");
        }

        $signature = substr(
            hash_hmac('sha256', "{$prefix}:{$id}:{$nonce}", $this->key()),
            0,
            12,
        );

        return "{$prefix}_{$signature}";
    }

    private function key(): string
    {
        return (string) config('app.key');
    }
}
