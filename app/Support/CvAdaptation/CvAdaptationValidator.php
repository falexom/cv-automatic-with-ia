<?php

namespace App\Support\CvAdaptation;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator as JsonSchemaValidator;

final class CvAdaptationValidator
{
    /**
     * @param  array<string, mixed>  $proposal
     * @param  array{catalog: array<string, mixed>, lookup: array<string, array<string, array<string, mixed>>>}  $built
     * @return array<string, mixed>
     */
    public function validateAnalysis(array $proposal, array $built, ?string $strategy = null): array
    {
        $this->assertSchema($proposal);
        $this->assertStrategy($proposal, $strategy);
        $this->assertOperation($proposal, $built['lookup']);
        $proposal = $this->normalizeProfileDetails($proposal);
        $this->assertRequirements($proposal);
        $this->assertSelections($proposal['selected_content'], $built['lookup']);

        return $proposal;
    }

    /**
     * @param  array<string, mixed>  $proposal
     */
    private function assertSchema(array $proposal): void
    {
        $validator = new JsonSchemaValidator(max_errors: 10, stop_at_first_error: false);
        $data = json_decode(
            json_encode($proposal, JSON_THROW_ON_ERROR),
            false,
            flags: JSON_THROW_ON_ERROR,
        );
        $result = $validator->validate($data, CvAdaptationSchema::json());

        if ($result->isValid()) {
            return;
        }

        $error = $result->error();
        $formatted = $error === null ? [] : (new ErrorFormatter)->format($error);
        $path = array_key_first($formatted);

        throw new CvAdaptationException(
            'schema_invalid',
            $path === null
                ? 'La respuesta de Codex no cumple el contrato JSON.'
                : "La respuesta de Codex no cumple el contrato JSON en {$path}.",
            502,
        );
    }

    /**
     * @param  array<string, mixed>  $proposal
     */
    private function assertStrategy(array $proposal, ?string $strategy): void
    {
        if ($strategy === null || $strategy === 'auto') {
            return;
        }

        if (($proposal['operation'] ?? null) !== $strategy) {
            throw new CvAdaptationException(
                'strategy_mismatch',
                'Codex no respetó la estrategia solicitada. Volvé a analizar la oferta.',
                502,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $proposal
     * @param  array<string, array<string, array<string, mixed>>>  $lookup
     */
    private function assertOperation(array $proposal, array $lookup): void
    {
        $operation = $proposal['operation'];
        $sourceRef = $proposal['source_profile_ref'];
        $profileDetails = $proposal['profile_details'];
        $selectedContent = $proposal['selected_content'];

        if ($operation === 'reuse') {
            $source = is_string($sourceRef) ? ($lookup[CvAdaptationReferences::PROFILE][$sourceRef] ?? null) : null;

            if ($source === null || $source['is_active'] !== true) {
                throw new CvAdaptationException(
                    'invalid_operation',
                    'La operación de reutilización requiere un perfil activo. Volvé a analizar la oferta.',
                    502,
                );
            }

            if ($profileDetails !== null || $selectedContent !== null) {
                throw new CvAdaptationException(
                    'invalid_operation',
                    'La operación de reutilización no puede proponer cambios en el perfil.',
                    502,
                );
            }

            return;
        }

        if ($operation === 'duplicate') {
            if (! is_string($sourceRef) || ! isset($lookup[CvAdaptationReferences::PROFILE][$sourceRef])) {
                throw new CvAdaptationException(
                    'invalid_operation',
                    'La operación de duplicación requiere un perfil de origen disponible.',
                    502,
                );
            }
        } elseif ($sourceRef !== null) {
            throw new CvAdaptationException(
                'invalid_operation',
                'La operación de creación no puede tener un perfil de origen.',
                502,
            );
        }

        if (! is_array($profileDetails) || ! is_array($selectedContent)) {
            throw new CvAdaptationException(
                'invalid_operation',
                'La propuesta no contiene los datos necesarios para crear el perfil.',
                502,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $proposal
     * @return array<string, mixed>
     */
    private function normalizeProfileDetails(array $proposal): array
    {
        if ($proposal['profile_details'] === null) {
            return $proposal;
        }

        $details = $proposal['profile_details'];
        $name = trim((string) ($details['name'] ?? ''));

        if ($name === '') {
            throw new CvAdaptationException(
                'invalid_profile',
                'La propuesta no incluye un nombre válido para el perfil nuevo.',
                502,
            );
        }

        $proposal['profile_details'] = [
            'name' => $name,
            'professional_title' => $this->normalizeNullable($details['professional_title'] ?? null),
            'summary' => $this->normalizeNullable($details['summary'] ?? null),
        ];

        return $proposal;
    }

    /**
     * @param  array<string, mixed>  $proposal
     */
    private function assertRequirements(array $proposal): void
    {
        $requirements = [];

        foreach ($proposal['requirements'] as $requirement) {
            $ref = $requirement['ref'];

            if (isset($requirements[$ref])) {
                throw new CvAdaptationException(
                    'invalid_requirements',
                    'La propuesta repite referencias de requisitos.',
                    502,
                );
            }

            $requirements[$ref] = $requirement;
        }

        $gapRefs = [];

        foreach ($proposal['gaps'] as $gap) {
            $ref = $gap['requirement_ref'];
            $requirement = $requirements[$ref] ?? null;

            if ($requirement === null || ! in_array($requirement['coverage'], ['gap', 'needs_confirmation'], true)) {
                throw new CvAdaptationException(
                    'invalid_requirements',
                    'La propuesta incluye brechas que no corresponden a requisitos sin cobertura.',
                    502,
                );
            }

            $gapRefs[$ref] = true;
        }

        foreach ($requirements as $ref => $requirement) {
            if (in_array($requirement['coverage'], ['gap', 'needs_confirmation'], true) && ! isset($gapRefs[$ref])) {
                throw new CvAdaptationException(
                    'invalid_requirements',
                    'La propuesta no explica todas las brechas detectadas.',
                    502,
                );
            }
        }

        foreach ($proposal['clarifying_questions'] as $question) {
            if (! isset($requirements[$question['requirement_ref']])) {
                throw new CvAdaptationException(
                    'invalid_requirements',
                    'La propuesta incluye preguntas sobre requisitos inexistentes.',
                    502,
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>|null  $selectedContent
     * @param  array<string, array<string, array<string, mixed>>>  $lookup
     */
    private function assertSelections(?array $selectedContent, array $lookup): void
    {
        if ($selectedContent === null) {
            return;
        }

        $fields = CvAdaptationReferences::CONTENT_PREFIXES;

        foreach ($fields as $field => $prefix) {
            $seen = [];

            foreach ($selectedContent[$field] as $selection) {
                $ref = $selection['ref'];

                if (isset($seen[$ref]) || ! isset($lookup[$prefix][$ref])) {
                    throw new CvAdaptationException(
                        'invalid_reference',
                        'La propuesta contiene referencias inválidas o repetidas. Volvé a analizar la oferta.',
                        502,
                    );
                }

                $seen[$ref] = true;
            }
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
