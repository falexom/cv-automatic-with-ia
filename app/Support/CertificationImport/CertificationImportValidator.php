<?php

namespace App\Support\CertificationImport;

use App\Support\YearMonth;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator as JsonSchemaValidator;

final class CertificationImportValidator
{
    public function __construct(
        private readonly CertificationImportText $text,
    ) {}

    /**
     * @param  array<string, mixed>  $proposal
     * @param  array<int, array{page: int, text: string, origin?: string}>  $pages
     * @param  array<string, array{id: int, name: string, category: string}>  $skillLookup
     * @return array<string, mixed>
     */
    public function validate(array $proposal, array $pages, array $skillLookup): array
    {
        $this->assertSchema($proposal);
        $this->assertSufficiency($proposal);

        /** @var array<string, mixed> $rawFields */
        $rawFields = $proposal['fields'];

        return [
            'schema_version' => 1,
            'sufficiency' => 'sufficient',
            'sufficiency_note' => $this->nullableString($proposal['sufficiency_note'] ?? null, 500),
            'fields' => [
                'name' => $this->valueField($rawFields['name'] ?? null, 'name', 255, ['found', 'absent'], $pages),
                'institution' => $this->valueField($rawFields['institution'] ?? null, 'institution', 255, ['found', 'absent'], $pages),
                'date' => $this->dateField($rawFields['date'] ?? null, $pages),
                'duration' => $this->valueField($rawFields['duration'] ?? null, 'duration', 100, ['found', 'absent'], $pages),
                'code' => $this->valueField($rawFields['code'] ?? null, 'code', 255, ['found', 'absent'], $pages),
                'validation_url' => $this->valueField($rawFields['validation_url'] ?? null, 'validation_url', 2048, ['found', 'absent'], $pages),
                'category' => $this->valueField($rawFields['category'] ?? null, 'category', 100, ['found', 'suggested', 'absent'], $pages, true),
            ],
            'skills' => $this->skills($proposal['skills'] ?? null, $pages, $skillLookup),
            'ambiguities' => $this->ambiguities($proposal['ambiguities'] ?? null),
        ];
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
        $result = $validator->validate($data, CertificationImportSchema::json());

        if ($result->isValid()) {
            return;
        }

        $error = $result->error();
        $formatted = $error === null ? [] : (new ErrorFormatter)->format($error);
        $path = array_key_first($formatted);

        throw new CertificationImportException(
            'invalid_proposal',
            $path === null
                ? 'La respuesta de Codex no cumple el contrato de importación.'
                : "La respuesta de Codex no cumple el contrato de importación en {$path}.",
            502,
        );
    }

    /**
     * @param  array<string, mixed>  $proposal
     */
    private function assertSufficiency(array $proposal): void
    {
        if (($proposal['sufficiency'] ?? null) === 'sufficient') {
            return;
        }

        $note = $this->nullableString($proposal['sufficiency_note'] ?? null, 500);
        $prefix = ($proposal['sufficiency'] ?? null) === 'irrelevant'
            ? 'El texto extraído no parece corresponder al contenido relevante de un certificado.'
            : 'El PDF no tiene texto nativo suficiente para una propuesta fiable.';

        $message = $prefix.' Podés reintentar con OCR local o ingresar los datos manualmente.';

        if ($note !== null) {
            $message .= ' Detalle: '.mb_substr($note, 0, 300);
        }

        throw new CertificationImportException('text_insufficient', $message, 422);
    }

    /**
     * @param  array<int, string>  $states
     * @param  array<int, array{page: int, text: string, origin?: string}>  $pages
     * @return array{value: string|null, state: string, support: string|null, page: int|null, reason: string|null}
     */
    private function valueField(
        mixed $raw,
        string $field,
        int $maxLength,
        array $states,
        array $pages,
        bool $suggestionAllowed = false,
    ): array {
        $data = $this->arrayValue($raw, $field);

        $state = $data['state'] ?? null;

        if (! is_string($state) || ! in_array($state, $states, true)) {
            throw $this->invalidProposal();
        }

        $value = $this->nullableString($data['value'] ?? null, $maxLength);
        $support = $this->nullableString($data['support'] ?? null, 1000);
        $reason = $this->nullableString($data['reason'] ?? null, 500);
        $page = $this->nullablePage($data['page'] ?? null);

        if ($state === 'absent') {
            return [
                'value' => null,
                'state' => 'absent',
                'support' => null,
                'page' => null,
                'reason' => null,
            ];
        }

        if ($value === null) {
            return [
                'value' => null,
                'state' => 'absent',
                'support' => null,
                'page' => null,
                'reason' => null,
            ];
        }

        if ($state === 'suggested') {
            $resolved = ['valid' => false, 'page' => null];

            if ($suggestionAllowed && $support !== null) {
                $resolved = $this->attemptSupport($support, $page, $pages, $value);
            }

            return [
                'value' => $value,
                'state' => $suggestionAllowed ? 'suggested' : 'unverified',
                'support' => $resolved['valid'] ? $support : null,
                'page' => $resolved['valid'] ? $resolved['page'] : null,
                'reason' => $reason
                    ?? 'Sugerencia para revisar; no hay un fundamento literal verificado.',
            ];
        }

        $resolved = $support === null
            ? ['valid' => false, 'page' => null]
            : $this->attemptSupport($support, $page, $pages, $value);

        if (! $resolved['valid']) {
            return [
                'value' => $value,
                'state' => 'unverified',
                'support' => null,
                'page' => null,
                'reason' => 'La cita o página no coincide con el texto enviado; el valor queda como sugerencia sin respaldo verificado.',
            ];
        }

        return [
            'value' => $value,
            'state' => 'found',
            'support' => $support,
            'page' => $resolved['page'],
            'reason' => null,
        ];
    }

    /**
     * @param  array<int, array{page: int, text: string, origin?: string}>  $pages
     * @return array{year: int|null, month: int|null, state: string, support: string|null, page: int|null, reason: string|null}
     */
    private function dateField(mixed $raw, array $pages): array
    {
        $data = $this->arrayValue($raw, 'date');
        $state = $data['state'] ?? null;

        if (! is_string($state) || ! in_array($state, ['found', 'absent'], true)) {
            throw $this->invalidProposal();
        }

        $year = $this->nullableInt($data['year'] ?? null);
        $month = $this->nullableInt($data['month'] ?? null);

        if ($state === 'absent') {
            return [
                'year' => null,
                'month' => null,
                'state' => 'absent',
                'support' => null,
                'page' => null,
                'reason' => null,
            ];
        }

        if (
            $year === null
            || $year < 1900
            || $year > YearMonth::maxYear()
            || ($month !== null && ($month < 1 || $month > 12))
        ) {
            return [
                'year' => null,
                'month' => null,
                'state' => 'absent',
                'support' => null,
                'page' => null,
                'reason' => null,
            ];
        }

        $support = $this->nullableString($data['support'] ?? null, 1000);
        $resolved = $support === null
            ? ['valid' => false, 'page' => null]
            : $this->attemptSupport(
                $support,
                $this->nullablePage($data['page'] ?? null),
                $pages,
                (string) $year,
            );

        if (! $resolved['valid']) {
            return [
                'year' => $year,
                'month' => $month,
                'state' => 'unverified',
                'support' => null,
                'page' => null,
                'reason' => 'La cita o página no coincide con el texto enviado; la fecha queda como sugerencia sin respaldo verificado.',
            ];
        }

        return [
            'year' => $year,
            'month' => $month,
            'state' => 'found',
            'support' => $support,
            'page' => $resolved['page'],
            'reason' => null,
        ];
    }

    /**
     * @param  array<int, array{page: int, text: string, origin?: string}>  $pages
     * @param  array<string, array{id: int, name: string, category: string}>  $lookup
     * @return array<int, array{id: int, name: string, category: string, state: string, support: string|null, page: int|null, reason: string|null}>
     */
    private function skills(mixed $raw, array $pages, array $lookup): array
    {
        if (! is_array($raw)) {
            throw $this->invalidProposal();
        }

        $skills = [];
        $seen = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                throw $this->invalidProposal();
            }

            $ref = $item['ref'] ?? null;

            if (
                ! is_string($ref)
                || preg_match('/^skl_[a-f0-9]{12}$/', $ref) !== 1
                || isset($seen[$ref])
                || ! isset($lookup[$ref])
            ) {
                throw new CertificationImportException(
                    'invalid_reference',
                    'La propuesta incluye habilidades fuera del catálogo vigente. Volvé a analizar o ingresá manualmente.',
                    502,
                );
            }

            $seen[$ref] = true;
            $state = $item['state'] ?? null;

            if (! is_string($state) || ! in_array($state, ['found', 'suggested'], true)) {
                throw $this->invalidProposal();
            }

            $support = $this->nullableString($item['support'] ?? null, 1000);
            $reason = $this->nullableString($item['reason'] ?? null, 500);
            $page = $this->nullablePage($item['page'] ?? null);
            $skill = $lookup[$ref];

            if ($state === 'found') {
                $resolved = $support === null
                    ? ['valid' => false, 'page' => null]
                    : $this->attemptSupport($support, $page, $pages, $skill['name']);

                if (! $resolved['valid']) {
                    $skills[] = [
                        'id' => $skill['id'],
                        'name' => $skill['name'],
                        'category' => $skill['category'],
                        'state' => 'suggested',
                        'support' => null,
                        'page' => null,
                        'reason' => 'La cita o página no coincide con el texto enviado; aceptá o rechazá la sugerencia.',
                    ];

                    continue;
                }

                $skills[] = [
                    'id' => $skill['id'],
                    'name' => $skill['name'],
                    'category' => $skill['category'],
                    'state' => 'found',
                    'support' => $support,
                    'page' => $resolved['page'],
                    'reason' => null,
                ];

                continue;
            }

            $resolved = $support === null
                ? ['valid' => false, 'page' => null]
                : $this->attemptSupport($support, $page, $pages, $support);

            $skills[] = [
                'id' => $skill['id'],
                'name' => $skill['name'],
                'category' => $skill['category'],
                'state' => 'suggested',
                'support' => $resolved['valid'] ? $support : null,
                'page' => $resolved['valid'] ? $resolved['page'] : null,
                'reason' => $reason
                    ?? 'Sugerencia para revisar; no hay un fundamento literal verificado.',
            ];
        }

        return $skills;
    }

    /**
     * @return array<int, string>
     */
    private function ambiguities(mixed $raw): array
    {
        if (! is_array($raw) || count($raw) > 10) {
            throw $this->invalidProposal();
        }

        $ambiguities = [];

        foreach ($raw as $item) {
            if (! is_string($item)) {
                throw $this->invalidProposal();
            }

            $item = trim($item);

            if ($item === '') {
                continue;
            }

            if (mb_strlen($item) > 500) {
                throw $this->invalidProposal();
            }

            $ambiguities[] = $item;
        }

        return $ambiguities;
    }

    /**
     * A support snippet is valid only when it exists in the sent text and its
     * page (when provided) is the page that contains it. Invalid citations
     * are downgraded to unverified suggestions instead of rejecting the
     * whole proposal.
     *
     * @param  array<int, array{page: int, text: string, origin?: string}>  $pages
     * @return array{valid: bool, page: int|null}
     */
    private function attemptSupport(string $support, ?int $page, array $pages, string $expectedValue): array
    {
        if (mb_strlen($this->text->normalize($support)) < 3) {
            return ['valid' => false, 'page' => null];
        }

        if (mb_stripos($this->text->normalize($support), $this->text->normalize($expectedValue)) === false) {
            return ['valid' => false, 'page' => null];
        }

        if ($page !== null) {
            $pageText = null;

            foreach ($pages as $candidate) {
                if ($candidate['page'] === $page) {
                    $pageText = $candidate['text'];

                    break;
                }
            }

            if ($pageText === null || ! $this->text->supportMatches($support, $pageText)) {
                return ['valid' => false, 'page' => null];
            }

            return ['valid' => true, 'page' => $page];
        }

        $matches = $this->text->pagesContaining($support, $pages);

        if ($matches === []) {
            return ['valid' => false, 'page' => null];
        }

        return ['valid' => true, 'page' => count($matches) === 1 ? $matches[0] : null];
    }

    private function invalidProposal(): CertificationImportException
    {
        return new CertificationImportException(
            'invalid_proposal',
            'La propuesta no cumple el contrato de importación. Volvé a analizar o ingresá manualmente.',
            502,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayValue(mixed $value, string $field): array
    {
        if (! is_array($value)) {
            throw $this->invalidProposal();
        }

        return $value;
    }

    private function nullableString(mixed $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw $this->invalidProposal();
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) > $maxLength) {
            throw $this->invalidProposal();
        }

        return $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (! is_int($value)) {
            throw $this->invalidProposal();
        }

        return $value;
    }

    private function nullablePage(mixed $value): ?int
    {
        $page = $this->nullableInt($value);

        if ($page !== null && $page < 1) {
            throw $this->invalidProposal();
        }

        return $page;
    }
}
