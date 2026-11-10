<?php

namespace App\Support\CvAdaptation;

use App\Models\Certification;
use App\Models\CvProfile;
use App\Models\EducationEntry;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;
use App\Support\CvProfileContent;

final class CvAdaptationCatalog
{
    public function __construct(
        private readonly CvAdaptationReferences $references,
    ) {}

    /**
     * @return array{
     *     catalog: array<string, mixed>,
     *     lookup: array<string, array<string, array<string, mixed>>>
     * }
     */
    public function build(string $nonce): array
    {
        $master = Profile::current();
        $lookup = [
            CvAdaptationReferences::WORK_EXPERIENCE => [],
            CvAdaptationReferences::EDUCATION_ENTRY => [],
            CvAdaptationReferences::CERTIFICATION => [],
            CvAdaptationReferences::SKILL => [],
            CvAdaptationReferences::PROJECT => [],
            CvAdaptationReferences::PROFILE => [],
        ];
        $refsByContent = [];

        $workExperiences = $master->workExperiences()
            ->chronological()
            ->with('skills')
            ->get()
            ->map(function (WorkExperience $experience) use ($nonce, &$lookup, &$refsByContent): array {
                $ref = $this->references->make(
                    CvAdaptationReferences::WORK_EXPERIENCE,
                    $experience->id,
                    $nonce,
                );

                $lookup[CvAdaptationReferences::WORK_EXPERIENCE][$ref] = [
                    'id' => $experience->id,
                    'label' => trim("{$experience->position} — {$experience->company}"),
                ];
                $refsByContent['work_experiences'][$experience->id] = $ref;

                return [
                    'ref' => $ref,
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
                    'technologies' => $experience->skills
                        ->pluck('name')
                        ->unique()
                        ->sort()
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $educationEntries = $master->educationEntries()
            ->chronological()
            ->get()
            ->map(function (EducationEntry $entry) use ($nonce, &$lookup, &$refsByContent): array {
                $ref = $this->references->make(
                    CvAdaptationReferences::EDUCATION_ENTRY,
                    $entry->id,
                    $nonce,
                );

                $lookup[CvAdaptationReferences::EDUCATION_ENTRY][$ref] = [
                    'id' => $entry->id,
                    'label' => trim("{$entry->program} — {$entry->institution}"),
                ];
                $refsByContent['education_entries'][$entry->id] = $ref;

                return [
                    'ref' => $ref,
                    'institution' => $entry->institution,
                    'program' => $entry->program,
                    'degree_level' => $entry->degree_level,
                    'start_year' => $entry->start_year,
                    'start_month' => $entry->start_month,
                    'end_year' => $entry->end_year,
                    'end_month' => $entry->end_month,
                    'is_in_progress' => $entry->is_in_progress,
                    'description' => $entry->description,
                ];
            })
            ->values()
            ->all();

        $certifications = $master->certifications()
            ->chronological()
            ->get()
            ->map(function (Certification $certification) use ($nonce, &$lookup, &$refsByContent): array {
                $ref = $this->references->make(
                    CvAdaptationReferences::CERTIFICATION,
                    $certification->id,
                    $nonce,
                );

                $lookup[CvAdaptationReferences::CERTIFICATION][$ref] = [
                    'id' => $certification->id,
                    'label' => $certification->name,
                ];
                $refsByContent['certifications'][$certification->id] = $ref;

                return [
                    'ref' => $ref,
                    'name' => $certification->name,
                    'institution' => $certification->institution,
                    'date_year' => $certification->date_year,
                    'date_month' => $certification->date_month,
                    'duration' => $certification->duration,
                    'category' => $certification->category,
                ];
            })
            ->values()
            ->all();

        $skills = $master->skills()
            ->masterOrder()
            ->get()
            ->map(function (Skill $skill) use ($nonce, &$lookup, &$refsByContent): array {
                $ref = $this->references->make(
                    CvAdaptationReferences::SKILL,
                    $skill->id,
                    $nonce,
                );

                $lookup[CvAdaptationReferences::SKILL][$ref] = [
                    'id' => $skill->id,
                    'label' => $skill->name,
                ];
                $refsByContent['skills'][$skill->id] = $ref;

                return [
                    'ref' => $ref,
                    'name' => $skill->name,
                    'category' => $skill->category,
                ];
            })
            ->values()
            ->all();

        $projects = $master->projects()
            ->chronological()
            ->get()
            ->map(function (Project $project) use ($nonce, &$lookup, &$refsByContent): array {
                $ref = $this->references->make(
                    CvAdaptationReferences::PROJECT,
                    $project->id,
                    $nonce,
                );

                $lookup[CvAdaptationReferences::PROJECT][$ref] = [
                    'id' => $project->id,
                    'label' => $project->name,
                ];
                $refsByContent['projects'][$project->id] = $ref;

                return [
                    'ref' => $ref,
                    'name' => $project->name,
                    'role' => $project->role,
                    'start_year' => $project->start_year,
                    'start_month' => $project->start_month,
                    'end_year' => $project->end_year,
                    'end_month' => $project->end_month,
                    'description' => $project->description,
                    'results' => $project->results,
                ];
            })
            ->values()
            ->all();

        $profiles = CvProfile::query()
            ->orderBy('name')
            ->get()
            ->map(function (CvProfile $profile) use ($nonce, $refsByContent, &$lookup): array {
                $ref = $this->references->make(
                    CvAdaptationReferences::PROFILE,
                    $profile->id,
                    $nonce,
                );

                $lookup[CvAdaptationReferences::PROFILE][$ref] = [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'is_active' => $profile->is_active,
                ];

                return [
                    'ref' => $ref,
                    'name' => $profile->name,
                    'professional_title' => $profile->professional_title,
                    'summary' => $profile->summary,
                    'is_active' => $profile->is_active,
                    'selected_content' => $this->mapSelections(
                        CvProfileContent::selections($profile),
                        $refsByContent,
                    ),
                ];
            })
            ->values()
            ->all();

        return [
            'catalog' => [
                'professional_title' => $master->professional_title,
                'summary' => $master->summary,
                'work_experiences' => $workExperiences,
                'education_entries' => $educationEntries,
                'certifications' => $certifications,
                'skills' => $skills,
                'projects' => $projects,
                'profiles' => $profiles,
            ],
            'lookup' => $lookup,
        ];
    }

    /**
     * @param  array<string, array<int, array{id: int, relevance: string}>>  $selections
     * @param  array<string, array<int, string>>  $refsByContent
     * @return array<string, array<int, array{ref: string, relevance: string}>>
     */
    private function mapSelections(array $selections, array $refsByContent): array
    {
        $mapped = [];

        foreach (CvAdaptationReferences::CONTENT_PREFIXES as $field => $prefix) {
            $mapped[$field] = [];

            foreach ($selections[$field] ?? [] as $selection) {
                $ref = $refsByContent[$field][$selection['id']] ?? null;

                if ($ref === null) {
                    continue;
                }

                $mapped[$field][] = [
                    'ref' => $ref,
                    'relevance' => $selection['relevance'],
                ];
            }
        }

        return $mapped;
    }
}
