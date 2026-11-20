<?php

namespace App\Support\CertificationImport;

use App\Models\Certification;
use App\Models\Document;
use App\Models\Profile;
use App\Support\NameNormalizer;
use App\Support\PdfFile;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Single atomic confirmation path for assisted and manual PDF imports.
 * No analysis state is stored here; only the reviewed record, its accepted
 * skills and the original PDF association survive.
 */
final class CertificationImportWriter
{
    public function __construct(
        private readonly CertificationImportProposalToken $proposalToken,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     duplicate_warning: array<string, mixed>|null,
     *     redirect: string|null,
     *     idempotent: bool,
     *     created: bool,
     *     reused_document: bool
     * }
     */
    public function confirm(array $data, UploadedFile $file, ?string $proposalToken): array
    {
        $contents = (string) $file->get();

        if ($contents === '' || ! PdfFile::looksLikePdf($contents)) {
            throw new CertificationImportException(
                'pdf_invalid',
                'El archivo no es un PDF válido o está truncado. No se guardó nada.',
                422,
            );
        }

        $checksum = hash('sha256', $contents);
        $proposal = null;
        $existingCategory = null;
        $existingSkillIds = [];
        $confirmationId = null;

        if (is_string($proposalToken) && $proposalToken !== '') {
            $token = $this->proposalToken->parse($proposalToken);

            if (! hash_equals($token['file_checksum'], $checksum)) {
                throw new CertificationImportException(
                    'file_mismatch',
                    'El PDF seleccionado no coincide con el que se analizó. Volvé a prepararlo o ingresá manualmente.',
                    422,
                    ['file' => 'El PDF seleccionado no coincide con el que se analizó.'],
                );
            }

            $confirmationId = $token['confirmation_id'];
            $proposal = $token['proposal'];
            $existingCategory = $token['existing_category'];
            $existingSkillIds = $token['existing_skill_ids'];
            $this->assertAcceptedSuggestions($proposal, $data, $existingCategory, $existingSkillIds);
        } else {
            $confirmationId = $this->manualConfirmationId($data);
        }

        $idempotent = $this->existingConfirmation($confirmationId, $checksum);

        if ($idempotent !== null) {
            return [
                'duplicate_warning' => null,
                'redirect' => route('certifications.index'),
                'idempotent' => true,
                'created' => false,
                'reused_document' => true,
            ];
        }

        $duplicate = $this->findDuplicate($data);

        if ($duplicate !== null && ! $this->booleanValue($data['confirm_duplicate'] ?? false)) {
            return [
                'duplicate_warning' => [
                    'existing_id' => $duplicate->id,
                    'name' => $duplicate->name,
                    'institution' => $duplicate->institution,
                ],
                'redirect' => null,
                'idempotent' => false,
                'created' => false,
                'reused_document' => false,
            ];
        }

        $document = Profile::current()
            ->documents()
            ->where('checksum', $checksum)
            ->first();

        if ($document !== null && ! $this->documentIsIntact($document)) {
            throw new CertificationImportException(
                'document_conflict',
                'Ya existe un documento con este contenido, pero su archivo original está faltante o alterado. No se reutilizó ni se modificó. Revisalo en Documentos antes de reintentar.',
                409,
            );
        }

        $reusedDocument = $document !== null;
        $disk = Storage::disk(Document::DISK);
        $path = Document::pathFor($checksum);
        $newFileWritten = false;

        if ($document === null) {
            if (! $disk->put($path, $contents)) {
                throw new CertificationImportException(
                    'save_failed',
                    'No se pudo conservar el PDF original. No se guardó ninguna certificación.',
                    500,
                );
            }

            $newFileWritten = true;
        }

        try {
            $result = DB::transaction(function () use (
                $data,
                $confirmationId,
                $checksum,
                $contents,
                $file,
                $document,
            ): array {
                $existing = Profile::current()
                    ->certifications()
                    ->where('import_confirmation_id', $confirmationId)
                    ->first();

                if ($existing !== null) {
                    return ['idempotent' => true, 'document' => $document];
                }

                $certification = Profile::current()->certifications()->create([
                    'name' => (string) $data['name'],
                    'institution' => $this->optionalString($data['institution'] ?? null),
                    'date_year' => $this->optionalInt($data['date_year'] ?? null),
                    'date_month' => $this->optionalInt($data['date_month'] ?? null),
                    'duration' => $this->optionalString($data['duration'] ?? null),
                    'code' => $this->optionalString($data['code'] ?? null),
                    'validation_url' => $this->optionalString($data['validation_url'] ?? null),
                    'category' => $this->optionalString($data['category'] ?? null),
                    'import_confirmation_id' => $confirmationId,
                ]);

                /** @var array<int, mixed> $skillIds */
                $skillIds = is_array($data['skill_ids'] ?? null) ? $data['skill_ids'] : [];
                $certification->skills()->sync(array_map('intval', $skillIds));

                $document ??= Profile::current()->documents()->create([
                    'original_filename' => $file->getClientOriginalName(),
                    'title' => null,
                    'description' => null,
                    'size_bytes' => mb_strlen($contents, '8bit'),
                    'checksum' => $checksum,
                ]);

                $certification->documents()->syncWithoutDetaching([$document->id]);

                return ['idempotent' => false, 'document' => $document];
            });
        } catch (UniqueConstraintViolationException) {
            if ($newFileWritten && ! Document::query()->where('checksum', $checksum)->exists()) {
                $disk->delete($path);
            }

            $existing = $this->existingConfirmation($confirmationId, $checksum);

            if ($existing !== null) {
                return [
                    'duplicate_warning' => null,
                    'redirect' => route('certifications.index'),
                    'idempotent' => true,
                    'created' => false,
                    'reused_document' => true,
                ];
            }

            throw new CertificationImportException(
                'save_failed',
                'No se pudo guardar la certificación por un conflicto de datos. No se creó ningún registro parcial.',
                500,
            );
        } catch (Throwable $exception) {
            if ($newFileWritten && ! Document::query()->where('checksum', $checksum)->exists()) {
                $disk->delete($path);
            }

            throw $exception;
        }

        return [
            'duplicate_warning' => null,
            'redirect' => route('certifications.index'),
            'idempotent' => (bool) $result['idempotent'],
            'created' => ! $result['idempotent'],
            'reused_document' => $reusedDocument,
        ];
    }

    /**
     * @param  array<string, mixed>  $proposal
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $existingSkillIds
     */
    private function assertAcceptedSuggestions(
        array $proposal,
        array $data,
        ?string $existingCategory,
        array $existingSkillIds,
    ): void {
        /** @var array<string, mixed> $fields */
        $fields = $proposal['fields'];
        /** @var array<string, mixed> $category */
        $category = is_array($fields['category'] ?? null) ? $fields['category'] : [];

        if (($category['state'] ?? null) === 'suggested' && is_string($category['value'] ?? null)) {
            $submitted = $this->optionalString($data['category'] ?? null);

            if (
                $submitted !== null
                && NameNormalizer::normalize($submitted) === NameNormalizer::normalize($category['value'])
                && ! $this->booleanValue($data['accepted_category'] ?? false)
                && NameNormalizer::normalize($existingCategory) !== NameNormalizer::normalize($submitted)
            ) {
                throw new CertificationImportException(
                    'suggestion_not_accepted',
                    'Aceptá individualmente la categoría sugerida o modificá el valor antes de guardar.',
                    422,
                    ['category' => 'Aceptá la categoría sugerida o modificá el valor.'],
                );
            }
        }

        /** @var array<int, mixed> $rawSkills */
        $rawSkills = is_array($proposal['skills'] ?? null) ? $proposal['skills'] : [];
        $suggestedIds = [];

        foreach ($rawSkills as $skill) {
            if (
                is_array($skill)
                && ($skill['state'] ?? null) === 'suggested'
                && is_int($skill['id'] ?? null)
            ) {
                $suggestedIds[] = $skill['id'];
            }
        }

        /** @var array<int, mixed> $rawSubmitted */
        $rawSubmitted = is_array($data['skill_ids'] ?? null) ? $data['skill_ids'] : [];
        $submittedIds = array_map('intval', $rawSubmitted);
        /** @var array<int, mixed> $rawAccepted */
        $rawAccepted = is_array($data['accepted_skill_ids'] ?? null) ? $data['accepted_skill_ids'] : [];
        $acceptedIds = array_map('intval', $rawAccepted);

        foreach (array_intersect($submittedIds, $suggestedIds) as $skillId) {
            if (
                ! in_array($skillId, $acceptedIds, true)
                && ! in_array($skillId, $existingSkillIds, true)
            ) {
                throw new CertificationImportException(
                    'suggestion_not_accepted',
                    'Aceptá individualmente cada habilidad sugerida antes de guardar.',
                    422,
                    ['skill_ids' => 'Aceptá individualmente cada habilidad sugerida o quitá la selección.'],
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function manualConfirmationId(array $data): string
    {
        $id = $data['confirmation_id'] ?? null;

        if (! is_string($id) || preg_match('/^[0-9a-f-]{36}$/i', $id) !== 1) {
            throw new CertificationImportException(
                'invalid_confirmation',
                'La confirmación no es válida. Volvé a intentar la operación.',
                422,
            );
        }

        return $id;
    }

    private function existingConfirmation(string $confirmationId, string $checksum): ?Certification
    {
        $existing = Profile::current()
            ->certifications()
            ->where('import_confirmation_id', $confirmationId)
            ->first();

        if ($existing === null) {
            return null;
        }

        if (! $existing->documents()->where('checksum', $checksum)->exists()) {
            throw new CertificationImportException(
                'invalid_confirmation',
                'Esta confirmación ya se usó con un PDF diferente. No se guardó nada.',
                422,
            );
        }

        return $existing;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function findDuplicate(array $data): ?Certification
    {
        $name = NameNormalizer::normalize($data['name'] ?? null);
        $institution = NameNormalizer::normalize($data['institution'] ?? null);

        return Profile::current()
            ->certifications()
            ->get()
            ->first(fn (Certification $certification): bool => NameNormalizer::normalize($certification->name) === $name
                && NameNormalizer::normalize($certification->institution) === $institution);
    }

    private function documentIsIntact(Document $document): bool
    {
        $disk = Storage::disk(Document::DISK);

        if (! $disk->exists($document->path())) {
            return false;
        }

        return hash_equals(
            $document->checksum,
            hash_file('sha256', $disk->path($document->path())) ?: '',
        );
    }

    /**
     * Multipart form values arrive as strings ("1"), while JSON tests and
     * internal calls use booleans. Normalize both forms.
     */
    private function booleanValue(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
    }

    private function optionalString(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }

    private function optionalInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_int($value) || (is_string($value) && ctype_digit($value))
            ? (int) $value
            : null;
    }
}
