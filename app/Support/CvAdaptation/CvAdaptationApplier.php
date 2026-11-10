<?php

namespace App\Support\CvAdaptation;

use App\Http\Requests\StoreCvProfileRequest;
use App\Models\CvAdaptation;
use App\Models\CvProfile;
use App\Support\CvProfileContent;
use App\Support\NameNormalizer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

final class CvAdaptationApplier
{
    public function __construct(
        private readonly CvAdaptationCatalog $catalog,
        private readonly CvAdaptationConfirmationToken $confirmationToken,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{redirect: string, created: bool, idempotent: bool}
     */
    public function apply(array $payload): array
    {
        $token = $this->confirmationToken->parse((string) ($payload['confirmation_token'] ?? ''));

        $this->assertTokenMatchesPayload($token, $payload);

        $existing = CvAdaptation::query()
            ->where('confirmation_id', $token['confirmation_id'])
            ->first();

        if ($existing !== null) {
            return [
                'redirect' => route('cv.index', ['profile' => $existing->cv_profile_id]),
                'created' => false,
                'idempotent' => true,
            ];
        }

        $operation = $token['operation'];
        $nonce = $token['ref_nonce'];
        $built = $this->catalog->build($nonce);
        $lookup = $built['lookup'];
        $evaluation = $this->evaluationData($token, $payload);

        if ($operation === 'reuse') {
            $source = $this->sourceProfile($token['source_profile_ref'], $lookup, requireActive: true);

            DB::transaction(function () use ($source, $evaluation): void {
                $this->persistEvaluation($source['id'], $evaluation);
            });

            return [
                'redirect' => route('cv.index', ['profile' => $source['id']]),
                'created' => false,
                'idempotent' => false,
            ];
        }

        if ($operation === 'duplicate') {
            $this->sourceProfile($token['source_profile_ref'], $lookup, requireActive: false);
        } elseif ($token['source_profile_ref'] !== null) {
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

        $profile = DB::transaction(function () use ($profileData, $evaluation): CvProfile {
            $this->ensureUniqueName($profileData['name']);

            $profile = CvProfileContent::create($profileData);
            $this->persistEvaluation($profile->id, $evaluation);

            return $profile;
        });

        return [
            'redirect' => route('cv.index', ['profile' => $profile->id]),
            'created' => true,
            'idempotent' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $token
     * @param  array<string, mixed>  $payload
     */
    private function assertTokenMatchesPayload(array $token, array $payload): void
    {
        if (
            ($payload['operation'] ?? null) !== $token['operation']
            || ($payload['ref_nonce'] ?? null) !== $token['ref_nonce']
            || ($payload['source_profile_ref'] ?? null) !== $token['source_profile_ref']
        ) {
            throw new CvAdaptationException(
                'invalid_confirmation',
                'La confirmación no coincide con la propuesta. Volvé a analizar la oferta.',
                422,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $token
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function evaluationData(array $token, array $payload): array
    {
        $jobTitle = trim((string) ($payload['job_title'] ?? ''));

        if ($jobTitle === '') {
            $jobTitle = 'Puesto no identificado';
        }

        $questions = $this->resolveQuestions(
            $token['clarifying_questions'],
            $payload['questions'] ?? [],
        );

        $context = [
            'schema_version' => 1,
            'job_title' => $jobTitle,
            'company' => $this->normalizeNullable($payload['company'] ?? null),
            'operation' => $token['operation'],
            'rationale' => $token['rationale'],
            'requirements' => $token['requirements'],
            'gaps' => $token['gaps'],
            'development_recommendations' => $token['development_recommendations'],
            'clarifying_questions' => $questions,
        ];

        try {
            $analyzedAt = CarbonImmutable::parse($token['analyzed_at']);
        } catch (Throwable) {
            throw new CvAdaptationException(
                'invalid_confirmation',
                'La confirmación ya no es válida. Volvé a analizar la oferta.',
                422,
            );
        }

        return [
            'confirmation_id' => $token['confirmation_id'],
            'operation' => $token['operation'],
            'job_title' => $jobTitle,
            'company' => $context['company'],
            'analyzed_at' => $analyzedAt,
            'context' => $context,
            'observations' => $this->observations($context),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @param  array<int, array<string, mixed>>  $reviews
     * @return array<int, array<string, mixed>>
     */
    private function resolveQuestions(array $questions, array $reviews): array
    {
        $reviewsByIndex = [];

        foreach ($reviews as $review) {
            $index = $review['index'] ?? null;

            if (
                ! is_int($index)
                || $index < 0
                || $index >= count($questions)
                || isset($reviewsByIndex[$index])
            ) {
                throw $this->invalidQuestions();
            }

            $status = $review['status'] ?? null;
            $answer = $review['answer'] ?? null;
            $confirmedAbsent = (bool) ($review['user_confirmed_absent'] ?? false);

            if (
                ! in_array($status, ['answered', 'unanswered', 'discarded'], true)
                || ($answer !== null && ! is_string($answer))
            ) {
                throw $this->invalidQuestions();
            }

            $answered = $status === 'answered'
                && is_string($answer)
                && trim($answer) !== '';

            if (
                ($status === 'answered' && ! $answered)
                || ($status !== 'answered' && is_string($answer) && trim($answer) !== '')
                || ($confirmedAbsent && ! $answered)
            ) {
                throw $this->invalidQuestions();
            }

            $reviewsByIndex[$index] = [
                'status' => $status,
                'answer' => $answered ? $answer : null,
                'answer_source' => $answered ? 'user_statement' : null,
                'user_confirmed_absent' => $answered && $confirmedAbsent,
            ];
        }

        $resolved = [];

        foreach ($questions as $index => $question) {
            $review = $reviewsByIndex[$index] ?? [
                'status' => 'unanswered',
                'answer' => null,
                'answer_source' => null,
                'user_confirmed_absent' => false,
            ];

            $resolved[] = [
                'requirement_ref' => $question['requirement_ref'],
                'question' => $question['question'],
                ...$review,
            ];
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
    private function observations(array $context): array
    {
        $observations = [];

        foreach ($context['gaps'] as $gap) {
            $observations[] = [
                'type' => 'gap',
                'reference' => 'gap:'.$gap['requirement_ref'],
                'status' => 'pending',
                'note' => null,
            ];
        }

        foreach (array_keys($context['development_recommendations']) as $index) {
            $observations[] = [
                'type' => 'recommendation',
                'reference' => 'recommendation:'.$index,
                'status' => 'pending',
                'note' => null,
            ];
        }

        foreach ($context['clarifying_questions'] as $index => $question) {
            if ($question['status'] === 'discarded') {
                continue;
            }

            $observations[] = [
                'type' => 'question',
                'reference' => 'question:'.$index,
                'status' => 'pending',
                'note' => null,
            ];
        }

        return $observations;
    }

    /**
     * @param  array<string, mixed>  $evaluation
     */
    private function persistEvaluation(int $profileId, array $evaluation): CvAdaptation
    {
        $adaptation = CvAdaptation::create([
            'cv_profile_id' => $profileId,
            'confirmation_id' => $evaluation['confirmation_id'],
            'operation' => $evaluation['operation'],
            'job_title' => $evaluation['job_title'],
            'company' => $evaluation['company'],
            'context' => $evaluation['context'],
            'analyzed_at' => $evaluation['analyzed_at'],
            'confirmed_at' => now(),
        ]);

        $adaptation->observations()->createMany($evaluation['observations']);

        return $adaptation;
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $lookup
     * @return array<string, mixed>
     */
    private function sourceProfile(?string $ref, array $lookup, bool $requireActive): array
    {
        $source = $ref !== null
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

    private function invalidQuestions(): CvAdaptationException
    {
        return new CvAdaptationException(
            'invalid_questions',
            'Las respuestas de las preguntas no son válidas. Revisalas antes de confirmar.',
            422,
        );
    }
}
