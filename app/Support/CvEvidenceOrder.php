<?php

namespace App\Support;

use App\Models\Certification;
use App\Models\CvProfile;
use App\Models\Document;
use App\Models\EducationEntry;
use App\Models\Profile;
use App\Models\WorkExperience;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CvEvidenceOrder
{
    /**
     * Order eligible documents for the documented CV: education first,
     * then certifications and finally experience evidence.
     *
     * @param  array<int, int>  $documentIds
     * @return array<int, Document>
     */
    public function order(CvProfile $profile, array $documentIds): array
    {
        $profile->loadMissing([
            'workExperiences',
            'educationEntries',
            'certifications',
        ]);

        $educationIds = $profile->educationEntries->modelKeys();
        $certificationIds = $profile->certifications->modelKeys();
        $experienceIds = $profile->workExperiences->modelKeys();

        return Profile::current()
            ->documents()
            ->whereKey($documentIds)
            ->with([
                'educationEntries' => fn (BelongsToMany $relation) => $relation
                    ->whereIn('education_entries.id', $educationIds),
                'certifications' => fn (BelongsToMany $relation) => $relation
                    ->whereIn('certifications.id', $certificationIds),
                'workExperiences' => fn (BelongsToMany $relation) => $relation
                    ->whereIn('work_experiences.id', $experienceIds),
            ])
            ->get()
            ->filter(fn (Document $document): bool => $document->educationEntries->isNotEmpty()
                || $document->certifications->isNotEmpty()
                || $document->workExperiences->isNotEmpty())
            ->sort(function (Document $left, Document $right): int {
                return $this->sortKey($left) <=> $this->sortKey($right);
            })
            ->values()
            ->all();
    }

    /**
     * @return array{0: int, 1: int, 2: string, 3: int}
     */
    private function sortKey(Document $document): array
    {
        $educationDates = $document->educationEntries
            ->map(fn (EducationEntry $entry): ?int => $this->educationDate($entry))
            ->filter()
            ->values();

        if ($document->educationEntries->isNotEmpty()) {
            return [0, $this->descendingDate($educationDates->max()), $this->nameKey($document), $document->id];
        }

        $certificationDates = $document->certifications
            ->map(fn (Certification $certification): ?int => $this->dateValue(
                $certification->date_year,
                $certification->date_month,
            ))
            ->filter()
            ->values();

        if ($document->certifications->isNotEmpty()) {
            return [1, $this->descendingDate($certificationDates->max()), $this->nameKey($document), $document->id];
        }

        $experienceDates = $document->workExperiences
            ->map(fn (WorkExperience $experience): ?int => $this->experienceDate($experience))
            ->filter()
            ->values();

        if ($document->workExperiences->isNotEmpty()) {
            return [2, $this->descendingDate($experienceDates->max()), $this->nameKey($document), $document->id];
        }

        return [3, 0, $this->nameKey($document), $document->id];
    }

    private function descendingDate(?int $date): int
    {
        return $date === null ? 0 : -$date;
    }

    private function nameKey(Document $document): string
    {
        return mb_strtolower($document->displayName());
    }

    private function educationDate(EducationEntry $entry): ?int
    {
        return $this->dateValue(
            $entry->end_year ?? $entry->start_year,
            $entry->end_year !== null ? $entry->end_month : $entry->start_month,
        );
    }

    private function experienceDate(WorkExperience $experience): ?int
    {
        return $this->dateValue(
            $experience->end_year ?? $experience->start_year,
            $experience->end_year !== null ? $experience->end_month : $experience->start_month,
        );
    }

    private function dateValue(?int $year, ?int $month): ?int
    {
        if ($year === null) {
            return null;
        }

        return ($year * 100) + ($month ?? 0);
    }
}
