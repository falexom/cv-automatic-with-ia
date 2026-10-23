<?php

namespace App\Support;

use App\Models\Certification;
use App\Models\CvProfile;
use App\Models\EducationEntry;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;

class CvPreviewContent
{
    /**
     * Build the derived, read-only CV preview for a profile.
     *
     * @return array<string, mixed>
     */
    public function build(CvProfile $profile): array
    {
        $profile->load([
            'workExperiences',
            'educationEntries',
            'certifications',
            'skills',
            'projects',
        ]);

        $personalData = CvProfileContent::personalData();

        $content = [
            'work_experiences' => $profile->workExperiences
                ->map(fn (WorkExperience $experience): array => [
                    'id' => $experience->id,
                    'position' => $experience->position,
                    'company' => $experience->company,
                    'start_year' => $experience->start_year,
                    'start_month' => $experience->start_month,
                    'end_year' => $experience->end_year,
                    'end_month' => $experience->end_month,
                    'is_current' => $experience->is_current,
                    'description' => $experience->description,
                    'responsibilities' => $experience->responsibilities,
                    'achievements' => $experience->achievements,
                ])
                ->values()
                ->all(),
            'education_entries' => $profile->educationEntries
                ->map(fn (EducationEntry $entry): array => [
                    'id' => $entry->id,
                    'institution' => $entry->institution,
                    'program' => $entry->program,
                    'degree_level' => $entry->degree_level,
                    'start_year' => $entry->start_year,
                    'start_month' => $entry->start_month,
                    'end_year' => $entry->end_year,
                    'end_month' => $entry->end_month,
                    'is_in_progress' => $entry->is_in_progress,
                    'description' => $entry->description,
                ])
                ->values()
                ->all(),
            'certifications' => $profile->certifications
                ->map(fn (Certification $certification): array => [
                    'id' => $certification->id,
                    'name' => $certification->name,
                    'institution' => $certification->institution,
                    'date_year' => $certification->date_year,
                    'date_month' => $certification->date_month,
                    'duration' => $certification->duration,
                    'category' => $certification->category,
                    'code' => $certification->code,
                    'validation_url' => $certification->validation_url,
                ])
                ->values()
                ->all(),
            'skills' => $profile->skills
                ->map(fn (Skill $skill): array => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'category' => $skill->category,
                ])
                ->values()
                ->all(),
            'projects' => $profile->projects
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'role' => $project->role,
                    'start_year' => $project->start_year,
                    'start_month' => $project->start_month,
                    'end_year' => $project->end_year,
                    'end_month' => $project->end_month,
                    'description' => $project->description,
                    'results' => $project->results,
                    'url' => $project->url,
                ])
                ->values()
                ->all(),
        ];

        $professionalTitle = $this->normalize($profile->professional_title);
        $summary = $this->normalize($profile->summary);

        $hasData = $personalData['has_data']
            || $professionalTitle !== null
            || $summary !== null
            || $this->hasContent($content);

        return [
            'personal_data' => $personalData,
            'professional_title' => $professionalTitle,
            'summary' => $summary,
            'content' => $content,
            'source_links' => [
                'profile' => "/profiles/{$profile->id}/edit",
                'personal_data' => '/profile',
                'work_experiences' => '/experiences',
                'education_entries' => '/education',
                'certifications' => '/certifications',
                'skills' => '/skills',
                'projects' => '/projects',
            ],
            'has_data' => $hasData,
        ];
    }

    private function normalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $content
     */
    private function hasContent(array $content): bool
    {
        foreach ($content as $items) {
            if ($items !== []) {
                return true;
            }
        }

        return false;
    }
}
