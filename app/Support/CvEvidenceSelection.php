<?php

namespace App\Support;

use App\Models\Certification;
use App\Models\CvProfile;
use App\Models\Document;
use App\Models\EducationEntry;
use App\Models\Profile;
use App\Models\WorkExperience;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CvEvidenceSelection
{
    /**
     * Eligible evidence for a CV profile: one entry per document linked
     * to at least one included record, with only those included links.
     *
     * @return array<int, array{
     *     id: int,
     *     display_name: string,
     *     is_available: bool,
     *     last_verified_at: string|null,
     *     associations: array<int, array{type: string, id: int, label: string}>
     * }>
     */
    public function eligibleFor(CvProfile $profile): array
    {
        $profile->loadMissing([
            'workExperiences',
            'educationEntries',
            'certifications',
        ]);

        $experienceIds = $profile->workExperiences->modelKeys();
        $educationIds = $profile->educationEntries->modelKeys();
        $certificationIds = $profile->certifications->modelKeys();

        return Profile::current()
            ->documents()
            ->where(function (Builder $query) use ($experienceIds, $educationIds, $certificationIds): void {
                $query
                    ->whereHas(
                        'workExperiences',
                        fn (Builder $relation) => $relation->whereIn('work_experiences.id', $experienceIds),
                    )
                    ->orWhereHas(
                        'educationEntries',
                        fn (Builder $relation) => $relation->whereIn('education_entries.id', $educationIds),
                    )
                    ->orWhereHas(
                        'certifications',
                        fn (Builder $relation) => $relation->whereIn('certifications.id', $certificationIds),
                    );
            })
            ->with([
                'certifications' => fn (BelongsToMany $relation) => $relation
                    ->whereIn('certifications.id', $certificationIds)
                    ->orderBy('certifications.id'),
                'educationEntries' => fn (BelongsToMany $relation) => $relation
                    ->whereIn('education_entries.id', $educationIds)
                    ->orderBy('education_entries.id'),
                'workExperiences' => fn (BelongsToMany $relation) => $relation
                    ->whereIn('work_experiences.id', $experienceIds)
                    ->orderBy('work_experiences.id'),
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Document $document): array => [
                'id' => $document->id,
                'display_name' => $document->displayName(),
                'is_available' => $document->is_available,
                'last_verified_at' => $document->last_verified_at?->toIso8601String(),
                'associations' => $this->associations($document),
            ])
            ->values()
            ->all();
    }

    /**
     * Only the links to records included in the profile, grouped by
     * document so each document appears once.
     *
     * @return array<int, array{type: string, id: int, label: string}>
     */
    private function associations(Document $document): array
    {
        return [
            ...$document->certifications
                ->map(fn (Certification $certification): array => [
                    'type' => 'certification',
                    'id' => $certification->id,
                    'label' => DocumentEntities::label($certification),
                ])
                ->all(),
            ...$document->educationEntries
                ->map(fn (EducationEntry $entry): array => [
                    'type' => 'education',
                    'id' => $entry->id,
                    'label' => DocumentEntities::label($entry),
                ])
                ->all(),
            ...$document->workExperiences
                ->map(fn (WorkExperience $experience): array => [
                    'type' => 'experience',
                    'id' => $experience->id,
                    'label' => DocumentEntities::label($experience),
                ])
                ->all(),
        ];
    }
}
