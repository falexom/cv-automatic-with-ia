<?php

namespace App\Support\CvAdaptation;

use App\Http\Requests\StoreCvProfileRequest;
use App\Models\CvProfile;
use App\Support\CvProfileContent;
use App\Support\NameNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class CvAdaptationApplier
{
    public function __construct(
        private readonly CvAdaptationCatalog $catalog,
        private readonly CvAdaptationReferences $references,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{redirect: string, created: bool}
     */
    public function apply(array $payload): array
    {
        $operation = $payload['operation'] ?? null;

        if (! is_string($operation) || ! in_array($operation, ['reuse', 'duplicate', 'create'], true)) {
            throw new CvAdaptationException(
                'invalid_operation',
                'La operación solicitada no es válida. Volvé a analizar la oferta.',
                422,
            );
        }

        $nonce = $payload['ref_nonce'] ?? null;

        if (! is_string($nonce) || ! $this->references->isValidNonce($nonce)) {
            throw new CvAdaptationException(
                'stale_reference',
                'La propuesta ya no es válida. Volvé a analizar la oferta.',
                422,
            );
        }

        $built = $this->catalog->build($nonce);
        $lookup = $built['lookup'];

        if ($operation === 'reuse') {
            $source = $this->sourceProfile($payload, $lookup, requireActive: true);

            return [
                'redirect' => route('cv.index', ['profile' => $source['id']]),
                'created' => false,
            ];
        }

        if ($operation === 'duplicate') {
            $this->sourceProfile($payload, $lookup, requireActive: false);
        } elseif (($payload['source_profile_ref'] ?? null) !== null) {
            throw new CvAdaptationException(
                'invalid_operation',
                'La operación de creación no puede tener un perfil de origen.',
                422,
            );
        }

        $selectedContent = $payload['selected_content'] ?? null;
        $profileDetails = $payload['profile_details'] ?? null;

        if (! is_array($selectedContent) || ! is_array($profileDetails)) {
            throw new CvAdaptationException(
                'invalid_profile',
                'La propuesta no contiene la selección necesaria para crear el perfil.',
                422,
            );
        }

        $selections = $this->resolveSelections($selectedContent, $lookup);
        $name = $profileDetails['name'] ?? null;

        if (! is_string($name) || trim($name) === '') {
            throw new CvAdaptationException(
                'invalid_profile',
                'El nombre del perfil es obligatorio.',
                422,
            );
        }

        $profileData = [
            'name' => trim($name),
            'professional_title' => $this->normalizeNullable($profileDetails['professional_title'] ?? null),
            'summary' => $this->normalizeNullable($profileDetails['summary'] ?? null),
            'work_experiences' => $selections['work_experiences'],
            'education_entries' => $selections['education_entries'],
            'certifications' => $selections['certifications'],
            'skills' => $selections['skills'],
            'projects' => $selections['projects'],
        ];

        $this->assertProfileData($profileData);

        $profile = DB::transaction(function () use ($profileData): CvProfile {
            $this->ensureUniqueName($profileData['name']);

            return CvProfileContent::create($profileData);
        });

        return [
            'redirect' => route('cv.index', ['profile' => $profile->id]),
            'created' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, array<string, array<string, mixed>>>  $lookup
     * @return array<string, mixed>
     */
    private function sourceProfile(array $payload, array $lookup, bool $requireActive): array
    {
        $ref = $payload['source_profile_ref'] ?? null;
        $source = is_string($ref)
            ? ($lookup[CvAdaptationReferences::PROFILE][$ref] ?? null)
            : null;

        if ($source === null) {
            throw new CvAdaptationException(
                'stale_reference',
                'El perfil de origen ya no está disponible. Volvé a analizar la oferta.',
                422,
            );
        }

        if ($requireActive && $source['is_active'] !== true) {
            throw new CvAdaptationException(
                'stale_reference',
                'El perfil recomendado ya no está activo. Volvé a analizar la oferta.',
                422,
            );
        }

        return $source;
    }

    /**
     * @param  array<string, mixed>  $selectedContent
     * @param  array<string, array<string, array<string, mixed>>>  $lookup
     * @return array{
     *     work_experiences: array<int, array{id: int, relevance: string}>,
     *     education_entries: array<int, array{id: int, relevance: string}>,
     *     certifications: array<int, array{id: int, relevance: string}>,
     *     skills: array<int, array{id: int, relevance: string}>,
     *     projects: array<int, array{id: int, relevance: string}>
     * }
     */
    private function resolveSelections(array $selectedContent, array $lookup): array
    {
        $resolved = [];

        foreach (CvAdaptationReferences::CONTENT_PREFIXES as $field => $prefix) {
            $resolved[$field] = [];
            $seen = [];
            $items = $selectedContent[$field] ?? [];

            if (! is_array($items)) {
                throw new CvAdaptationException(
                    'invalid_profile',
                    'La selección de contenido no es válida.',
                    422,
                );
            }

            foreach ($items as $item) {
                $ref = is_array($item) ? ($item['ref'] ?? null) : null;
                $relevance = is_array($item) ? ($item['relevance'] ?? null) : null;
                $record = is_string($ref) ? ($lookup[$prefix][$ref] ?? null) : null;

                if (
                    $record === null
                    || ! is_string($relevance)
                    || ! in_array($relevance, CvProfile::RELEVANCES, true)
                    || isset($seen[$ref])
                ) {
                    throw new CvAdaptationException(
                        'stale_reference',
                        'La propuesta contiene referencias que ya no están disponibles. Volvé a analizar la oferta.',
                        422,
                    );
                }

                $seen[$ref] = true;
                $resolved[$field][] = [
                    'id' => $record['id'],
                    'relevance' => $relevance,
                ];
            }
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $profileData
     */
    private function assertProfileData(array $profileData): void
    {
        $request = new StoreCvProfileRequest;
        $validator = Validator::make(
            $profileData,
            $request->rules(),
            $request->messages(),
        );

        if ($validator->fails()) {
            throw new CvAdaptationException(
                'invalid_profile',
                (string) $validator->errors()->first(),
                422,
            );
        }
    }

    private function ensureUniqueName(string $name): void
    {
        $exists = CvProfile::query()
            ->where('normalized_name', NameNormalizer::normalize($name))
            ->exists();

        if ($exists) {
            throw new CvAdaptationException(
                'duplicate_name',
                'Ya existe un perfil con ese nombre. Elegí otro nombre antes de guardar.',
                422,
            );
        }
    }

    private function normalizeNullable(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }
}
