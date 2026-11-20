<?php

namespace App\Support\CertificationImport;

use App\Models\Profile;
use App\Models\Skill;

/**
 * Minimal skill catalog for suggestion purposes. Database ids never leave
 * the server: the model only sees opaque references.
 */
final class CertificationImportCatalog
{
    public function nonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * @return array{
     *     skills: array<int, array{ref: string, name: string, category: string}>,
     *     lookup: array<string, array{id: int, name: string, category: string}>
     * }
     */
    public function build(string $nonce): array
    {
        $skills = [];
        $lookup = [];

        Profile::current()
            ->skills()
            ->orderBy('name')
            ->get(['id', 'name', 'category'])
            ->each(function (Skill $skill) use ($nonce, &$skills, &$lookup): void {
                $ref = 'skl_'.substr(hash_hmac('sha256', 'skill:'.$skill->id, $nonce), 0, 12);

                $skills[] = [
                    'ref' => $ref,
                    'name' => $skill->name,
                    'category' => $skill->category,
                ];
                $lookup[$ref] = [
                    'id' => (int) $skill->id,
                    'name' => $skill->name,
                    'category' => $skill->category,
                ];
            });

        return ['skills' => $skills, 'lookup' => $lookup];
    }
}
