<?php

namespace App\Support;

use App\Models\Certification;
use App\Models\CvProfile;
use App\Models\EducationEntry;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;
use Illuminate\Support\Facades\DB;

final class CvProfileContent
{
    /**
     * Master catalog for the five content classes, in master order.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function options(): array
    {
        $master = Profile::current();

        return [
            'work_experiences' => $master->workExperiences()
                ->chronological()
                ->get()
                ->map(fn (WorkExperience $experience): array => [
                    'id' => $experience->id,
                    'company' => $experience->company,
                    'position' => $experience->position,
                    'start_year' => $experience->start_year,
                    'start_month' => $experience->start_month,
                    'end_year' => $experience->end_year,
                    'end_month' => $experience->end_month,
                    'is_current' => $experience->is_current,
                ])
                ->values()
                ->all(),
            'education_entries' => $master->educationEntries()
                ->chronological()
                ->get()
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
                ])
                ->values()
                ->all(),
            'certifications' => $master->certifications()
                ->chronological()
                ->get()
                ->map(fn (Certification $certification): array => [
                    'id' => $certification->id,
                    'name' => $certification->name,
                    'institution' => $certification->institution,
                    'date_year' => $certification->date_year,
                    'date_month' => $certification->date_month,
                ])
                ->values()
                ->all(),
            'skills' => $master->skills()
                ->masterOrder()
                ->get()
                ->map(fn (Skill $skill): array => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'category' => $skill->category,
                ])
                ->values()
                ->all(),
            'projects' => $master->projects()
                ->chronological()
                ->get()
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'role' => $project->role,
                    'start_year' => $project->start_year,
                    'start_month' => $project->start_month,
                    'end_year' => $project->end_year,
                    'end_month' => $project->end_month,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Current association ids and relevances for a profile.
     *
     * @return array<string, array<int, array{id: int, relevance: string}>>
     */
    public static function selections(CvProfile $profile): array
    {
        return [
            'work_experiences' => $profile->workExperiences->map(fn (WorkExperience $experience): array => [
                'id' => $experience->id,
                'relevance' => $experience->pivot->relevance,
            ])->values()->all(),
            'education_entries' => $profile->educationEntries->map(fn (EducationEntry $entry): array => [
                'id' => $entry->id,
                'relevance' => $entry->pivot->relevance,
            ])->values()->all(),
            'certifications' => $profile->certifications->map(fn (Certification $certification): array => [
                'id' => $certification->id,
                'relevance' => $certification->pivot->relevance,
            ])->values()->all(),
            'skills' => $profile->skills->map(fn (Skill $skill): array => [
                'id' => $skill->id,
                'relevance' => $skill->pivot->relevance,
            ])->values()->all(),
            'projects' => $profile->projects->map(fn (Project $project): array => [
                'id' => $project->id,
                'relevance' => $project->pivot->relevance,
            ])->values()->all(),
        ];
    }

    /**
     * Ordered content for the profile detail, including provenance.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function content(CvProfile $profile): array
    {
        return [
            'work_experiences' => $profile->workExperiences->map(fn (WorkExperience $experience): array => [
                'id' => $experience->id,
                'relevance' => $experience->pivot->relevance,
                'master_path' => "/experiences/{$experience->id}/edit",
                'company' => $experience->company,
                'position' => $experience->position,
                'start_year' => $experience->start_year,
                'start_month' => $experience->start_month,
                'end_year' => $experience->end_year,
                'end_month' => $experience->end_month,
                'is_current' => $experience->is_current,
                'description' => $experience->description,
            ])->values()->all(),
            'education_entries' => $profile->educationEntries->map(fn (EducationEntry $entry): array => [
                'id' => $entry->id,
                'relevance' => $entry->pivot->relevance,
                'master_path' => "/education/{$entry->id}/edit",
                'institution' => $entry->institution,
                'program' => $entry->program,
                'degree_level' => $entry->degree_level,
                'start_year' => $entry->start_year,
                'start_month' => $entry->start_month,
                'end_year' => $entry->end_year,
                'end_month' => $entry->end_month,
                'is_in_progress' => $entry->is_in_progress,
                'description' => $entry->description,
            ])->values()->all(),
            'certifications' => $profile->certifications->map(fn (Certification $certification): array => [
                'id' => $certification->id,
                'relevance' => $certification->pivot->relevance,
                'master_path' => "/certifications/{$certification->id}/edit",
                'name' => $certification->name,
                'institution' => $certification->institution,
                'date_year' => $certification->date_year,
                'date_month' => $certification->date_month,
                'duration' => $certification->duration,
                'category' => $certification->category,
                'code' => $certification->code,
                'validation_url' => $certification->validation_url,
            ])->values()->all(),
            'skills' => $profile->skills->map(fn (Skill $skill): array => [
                'id' => $skill->id,
                'relevance' => $skill->pivot->relevance,
                'master_path' => "/skills/{$skill->id}/edit",
                'name' => $skill->name,
                'category' => $skill->category,
            ])->values()->all(),
            'projects' => $profile->projects->map(fn (Project $project): array => [
                'id' => $project->id,
                'relevance' => $project->pivot->relevance,
                'master_path' => "/projects/{$project->id}/edit",
                'name' => $project->name,
                'role' => $project->role,
                'start_year' => $project->start_year,
                'start_month' => $project->start_month,
                'end_year' => $project->end_year,
                'end_month' => $project->end_month,
                'url' => $project->url,
                'description' => $project->description,
            ])->values()->all(),
        ];
    }

    /**
     * Live inherited personal data. Master title and summary are excluded.
     *
     * @return array<string, mixed>
     */
    public static function personalData(): array
    {
        $profile = Profile::current();

        $links = $profile->links()
            ->orderBy('id')
            ->get(['id', 'label', 'url'])
            ->map(fn ($link): array => [
                'id' => $link->id,
                'label' => $link->label,
                'url' => $link->url,
            ])
            ->values()
            ->all();

        $personal = [
            'full_name' => $profile->full_name,
            'phone' => $profile->phone,
            'email' => $profile->email,
            'location' => $profile->location,
            'linkedin_url' => $profile->linkedin_url,
            'github_url' => $profile->github_url,
            'website_url' => $profile->website_url,
        ];

        $hasData = collect($personal)->contains(fn ($value): bool => filled($value))
            || $links !== [];

        return [
            ...$personal,
            'links' => $links,
            'has_data' => $hasData,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function create(array $data): CvProfile
    {
        return DB::transaction(function () use ($data): CvProfile {
            $profile = CvProfile::create([
                'name' => $data['name'],
                'professional_title' => $data['professional_title'] ?? null,
                'summary' => $data['summary'] ?? null,
            ]);

            self::syncSelections($profile, $data);

            return $profile;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function update(CvProfile $profile, array $data): void
    {
        DB::transaction(function () use ($profile, $data): void {
            $profile->update([
                'name' => $data['name'],
                'professional_title' => $data['professional_title'] ?? null,
                'summary' => $data['summary'] ?? null,
            ]);

            self::syncSelections($profile, $data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function syncSelections(CvProfile $profile, array $data): void
    {
        $relations = [
            'work_experiences' => 'workExperiences',
            'education_entries' => 'educationEntries',
            'certifications' => 'certifications',
            'skills' => 'skills',
            'projects' => 'projects',
        ];

        foreach ($relations as $field => $relation) {
            $payload = [];

            foreach ($data[$field] ?? [] as $item) {
                $payload[(int) $item['id']] = [
                    'relevance' => $item['relevance'] ?? 'media',
                ];
            }

            $profile->{$relation}()->sync($payload);
        }
    }
}
