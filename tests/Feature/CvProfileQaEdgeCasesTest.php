<?php

use App\Models\Certification;
use App\Models\CvProfile;
use App\Models\EducationEntry;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;
use App\Support\CvProfileContent;
use App\Support\NameNormalizer;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

it('rejects a non-string name with a validation error instead of a server error', function () {
    $this->post('/profiles', ['name' => ['Analista']])
        ->assertSessionHasErrors('name');

    expect(CvProfile::query()->count())->toBe(0);
});

it('cascades master deletions for all five classes while keeping the profile', function () {
    $experience = WorkExperience::factory()->create();
    $education = EducationEntry::factory()->create();
    $certification = Certification::factory()->create();
    $skill = Skill::factory()->create();
    $project = Project::factory()->create();
    $otherSkill = Skill::factory()->create();

    $profile = CvProfile::factory()->create();

    $this->put("/profiles/{$profile->id}", [
        'name' => $profile->name,
        'work_experiences' => [['id' => $experience->id]],
        'education_entries' => [['id' => $education->id]],
        'certifications' => [['id' => $certification->id]],
        'skills' => [
            ['id' => $skill->id],
            ['id' => $otherSkill->id],
        ],
        'projects' => [['id' => $project->id]],
    ])->assertRedirect();

    $experience->delete();
    $education->delete();
    $certification->delete();
    $skill->delete();
    $project->delete();

    expect(CvProfile::query()->whereKey($profile->id)->exists())->toBeTrue()
        ->and(DB::table('cv_profile_work_experience')->where('cv_profile_id', $profile->id)->count())->toBe(0)
        ->and(DB::table('cv_profile_education_entry')->where('cv_profile_id', $profile->id)->count())->toBe(0)
        ->and(DB::table('cv_profile_certification')->where('cv_profile_id', $profile->id)->count())->toBe(0)
        ->and(DB::table('cv_profile_project')->where('cv_profile_id', $profile->id)->count())->toBe(0)
        ->and(DB::table('cv_profile_skill')->where('cv_profile_id', $profile->id)->count())->toBe(1)
        ->and($profile->skills()->pluck('skills.id')->all())->toBe([$otherSkill->id]);

    $this->get("/profiles/{$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('content.work_experiences', 0)
            ->has('content.education_entries', 0)
            ->has('content.certifications', 0)
            ->has('content.skills', 1)
            ->has('content.projects', 0));
});

it('puts undated education, certifications and projects last within their class', function () {
    $educationDated = EducationEntry::factory()->create([
        'start_year' => 2020,
        'start_month' => 4,
    ]);
    $educationUndated = EducationEntry::factory()->create([
        'start_year' => null,
        'start_month' => null,
    ]);
    $certificationDated = Certification::factory()->create([
        'date_year' => 2021,
        'date_month' => 7,
    ]);
    $certificationUndated = Certification::factory()->withoutDate()->create();
    $projectDated = Project::factory()->create([
        'start_year' => 2022,
        'start_month' => 2,
    ]);
    $projectUndated = Project::factory()->create([
        'start_year' => null,
        'start_month' => null,
    ]);

    $this->post('/profiles', [
        'name' => 'Analista',
        'education_entries' => [
            ['id' => $educationUndated->id],
            ['id' => $educationDated->id],
        ],
        'certifications' => [
            ['id' => $certificationUndated->id],
            ['id' => $certificationDated->id],
        ],
        'projects' => [
            ['id' => $projectUndated->id],
            ['id' => $projectDated->id],
        ],
    ])->assertRedirect();

    $profile = CvProfile::query()->firstOrFail();

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('content.education_entries.0.id', $educationDated->id)
            ->where('content.education_entries.1.id', $educationUndated->id)
            ->where('content.certifications.0.id', $certificationDated->id)
            ->where('content.certifications.1.id', $certificationUndated->id)
            ->where('content.projects.0.id', $projectDated->id)
            ->where('content.projects.1.id', $projectUndated->id));
});

it('orders every class by relevance before the master order', function () {
    $experienceHigh = WorkExperience::factory()->create(['start_year' => 2018]);
    $experienceLow = WorkExperience::factory()->create(['start_year' => 2024]);

    $educationHigh = EducationEntry::factory()->create(['start_year' => 2018]);
    $educationLow = EducationEntry::factory()->create(['start_year' => 2024]);

    $certificationHigh = Certification::factory()->create(['date_year' => 2018]);
    $certificationLow = Certification::factory()->create(['date_year' => 2024]);

    $projectHigh = Project::factory()->create(['start_year' => 2018]);
    $projectLow = Project::factory()->create(['start_year' => 2024]);

    $skillHigh = Skill::factory()->create(['name' => 'PHP', 'category' => 'tecnologia']);
    $skillLow = Skill::factory()->create(['name' => 'Scrum', 'category' => 'metodologica']);
    $skillTool = Skill::factory()->create(['name' => 'Jira', 'category' => 'herramienta']);
    $skillZeta = Skill::factory()->create(['name' => 'Zeta', 'category' => 'tecnica']);
    $skillAlpha = Skill::factory()->create(['name' => 'Alfa', 'category' => 'tecnica']);

    $this->post('/profiles', [
        'name' => 'Analista',
        'work_experiences' => [
            ['id' => $experienceLow->id, 'relevance' => 'baja'],
            ['id' => $experienceHigh->id, 'relevance' => 'alta'],
        ],
        'education_entries' => [
            ['id' => $educationLow->id, 'relevance' => 'baja'],
            ['id' => $educationHigh->id, 'relevance' => 'alta'],
        ],
        'certifications' => [
            ['id' => $certificationLow->id, 'relevance' => 'baja'],
            ['id' => $certificationHigh->id, 'relevance' => 'alta'],
        ],
        'skills' => [
            ['id' => $skillHigh->id, 'relevance' => 'alta'],
            ['id' => $skillLow->id, 'relevance' => 'baja'],
            ['id' => $skillTool->id],
            ['id' => $skillZeta->id],
            ['id' => $skillAlpha->id],
        ],
        'projects' => [
            ['id' => $projectLow->id, 'relevance' => 'baja'],
            ['id' => $projectHigh->id, 'relevance' => 'alta'],
        ],
    ])->assertRedirect();

    $profile = CvProfile::query()->firstOrFail();

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('content.work_experiences.0.id', $experienceHigh->id)
            ->where('content.work_experiences.1.id', $experienceLow->id)
            ->where('content.education_entries.0.id', $educationHigh->id)
            ->where('content.education_entries.1.id', $educationLow->id)
            ->where('content.certifications.0.id', $certificationHigh->id)
            ->where('content.certifications.1.id', $certificationLow->id)
            ->where('content.skills.0.id', $skillHigh->id)
            ->where('content.skills.1.id', $skillAlpha->id)
            ->where('content.skills.2.id', $skillZeta->id)
            ->where('content.skills.3.id', $skillTool->id)
            ->where('content.skills.4.id', $skillLow->id)
            ->where('content.projects.0.id', $projectHigh->id)
            ->where('content.projects.1.id', $projectLow->id));
});

it('rolls back the profile update when a pivot persistence fails', function () {
    $profile = CvProfile::factory()->create(['name' => 'Nombre original']);

    expect(fn () => CvProfileContent::update($profile, [
        'name' => 'Nombre nuevo',
        'work_experiences' => [['id' => 999999]],
    ]))->toThrow(QueryException::class);

    expect($profile->fresh()->name)->toBe('Nombre original');
});

it('does not change the state through the update payload', function () {
    $profile = CvProfile::factory()->create(['is_active' => true]);

    $this->put("/profiles/{$profile->id}", [
        'name' => $profile->name,
        'is_active' => false,
        'normalized_name' => 'alterado',
    ])->assertRedirect();

    expect($profile->fresh()->is_active)->toBeTrue()
        ->and($profile->fresh()->normalized_name)->toBe(NameNormalizer::normalize($profile->name));
});

it('offers empty master options and inherited-data guidance when the master is empty', function () {
    $profile = CvProfile::factory()->create();

    $this->get('/profiles/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('options.work_experiences', [])
            ->where('options.education_entries', [])
            ->where('options.certifications', [])
            ->where('options.skills', [])
            ->where('options.projects', []));

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('personal_data.has_data', false)
            ->where('content.work_experiences', [])
            ->where('content.skills', []));
});
