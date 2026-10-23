<?php

use App\Models\Certification;
use App\Models\CvProfile;
use App\Models\EducationEntry;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;
use App\Support\CvPreviewContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

function cvGeneratorMaster(array $attributes = []): Profile
{
    $master = Profile::current();
    $master->update($attributes);

    return $master;
}

it('exposes the cv route and renders the Cv/Index component', function () {
    expect(Route::has('cv.index'))->toBeTrue();

    CvProfile::factory()->create();

    $this->get('/cv')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cv/Index')
            ->where('status', 'selection_required')
            ->where('selected_profile_id', null)
            ->where('preview', null));
});

it('lists only active profiles ordered by name with selector fields', function () {
    CvProfile::factory()->inactive()->create(['name' => 'ZZ Inactivo']);
    $programmer = CvProfile::factory()->create([
        'name' => 'Programador',
        'professional_title' => 'Programador / Desarrollador',
    ]);
    $analyst = CvProfile::factory()->create([
        'name' => 'Analista',
        'professional_title' => 'Analista de Sistemas',
    ]);

    $this->get('/cv')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cv/Index')
            ->has('profiles', 2)
            ->where('profiles.0', [
                'id' => $analyst->id,
                'name' => 'Analista',
                'professional_title' => 'Analista de Sistemas',
            ])
            ->where('profiles.1', [
                'id' => $programmer->id,
                'name' => 'Programador',
                'professional_title' => 'Programador / Desarrollador',
            ])
            ->missing('profiles.2'));
});

it('requires an explicit selection when active profiles exist', function () {
    CvProfile::factory()->create(['name' => 'Analista']);

    foreach (['/cv', '/cv?profile='] as $url) {
        $this->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('status', 'selection_required')
                ->where('selected_profile_id', null)
                ->where('preview', null));
    }
});

it('reports no active profiles when every profile is inactive', function () {
    CvProfile::factory()->inactive()->create(['name' => 'Analista']);

    $this->get('/cv')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cv/Index')
            ->where('profiles', [])
            ->where('status', 'no_active_profiles')
            ->where('selected_profile_id', null)
            ->where('preview', null));
});

it('returns unavailable for inactive, missing or invalid profile queries', function () {
    $active = CvProfile::factory()->create(['name' => 'Analista']);
    $inactive = CvProfile::factory()->inactive()->create(['name' => 'Programador']);

    $queries = [
        "?profile={$inactive->id}",
        '?profile=999999',
        '?profile=abc',
        '?profile=0',
        '?profile=-1',
        '?profile=%20',
        '?profile[]=1',
    ];

    foreach ($queries as $query) {
        $this->get('/cv'.$query)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('status', 'unavailable')
                ->where('selected_profile_id', null)
                ->where('preview', null)
                ->has('profiles', 1)
                ->where('profiles.0.id', $active->id));
    }
});

it('returns ready with inherited personal data and no title or summary fallback', function () {
    $master = cvGeneratorMaster([
        'full_name' => 'Ada Lovelace',
        'professional_title' => 'Título del Maestro',
        'phone' => '+54 11 5555 1234',
        'email' => 'ada@example.com',
        'location' => 'Buenos Aires, Argentina',
        'linkedin_url' => 'https://www.linkedin.com/in/ada',
        'github_url' => 'https://github.com/ada',
        'website_url' => 'https://ada.example.com',
        'summary' => 'Resumen del Maestro',
    ]);
    $link = $master->links()->create([
        'label' => 'Blog',
        'url' => 'https://blog.example.com',
    ]);

    $profile = CvProfile::factory()->create([
        'name' => 'Analista',
        'professional_title' => '   ',
        'summary' => '',
    ]);

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cv/Index')
            ->where('status', 'ready')
            ->where('selected_profile_id', $profile->id)
            ->where('profiles.0.id', $profile->id)
            ->where('preview.personal_data.full_name', 'Ada Lovelace')
            ->where('preview.personal_data.phone', '+54 11 5555 1234')
            ->where('preview.personal_data.email', 'ada@example.com')
            ->where('preview.personal_data.location', 'Buenos Aires, Argentina')
            ->where('preview.personal_data.linkedin_url', 'https://www.linkedin.com/in/ada')
            ->where('preview.personal_data.github_url', 'https://github.com/ada')
            ->where('preview.personal_data.website_url', 'https://ada.example.com')
            ->where('preview.personal_data.links.0', [
                'id' => $link->id,
                'label' => 'Blog',
                'url' => 'https://blog.example.com',
            ])
            ->where('preview.personal_data.has_data', true)
            ->where('preview.professional_title', null)
            ->where('preview.summary', null)
            ->where('preview.content', [
                'work_experiences' => [],
                'education_entries' => [],
                'certifications' => [],
                'skills' => [],
                'projects' => [],
            ])
            ->where('preview.has_data', true)
            ->missing('preview.name')
            ->missing('preview.is_active')
            ->missing('preview.relevance')
            ->missing('preview.master_path')
            ->missing('preview.template')
            ->missing('preview.personal_data.professional_title')
            ->missing('preview.personal_data.summary'));
});

it('uses the profile own title and summary instead of the master values', function () {
    cvGeneratorMaster([
        'professional_title' => 'Título del Maestro',
        'summary' => 'Resumen del Maestro',
    ]);

    $profile = CvProfile::factory()->create([
        'professional_title' => 'Título propio',
        'summary' => 'Resumen propio con ñandú y estadística.',
    ]);

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.professional_title', 'Título propio')
            ->where('preview.summary', 'Resumen propio con ñandú y estadística.'));
});

it('composes only included items with all FR-011 fields and no configuration metadata', function () {
    cvGeneratorMaster([
        'full_name' => 'Ada Lovelace',
        'phone' => '+54 11 5555 1234',
        'email' => 'ada@example.com',
    ]);

    $experience = WorkExperience::factory()->create([
        'company' => 'Acme',
        'position' => 'Analista funcional',
        'start_year' => 2022,
        'start_month' => 3,
        'end_year' => null,
        'end_month' => null,
        'is_current' => true,
        'description' => 'Relevamiento y documentación de procesos.',
        'responsibilities' => "Relevamiento de requerimientos.\nDocumentación funcional.",
        'achievements' => 'Reducción de tiempos de proceso en un 30%.',
    ]);
    $excludedExperience = WorkExperience::factory()->create(['company' => 'Excluida']);

    $education = EducationEntry::factory()->create([
        'institution' => 'Universidad',
        'program' => 'Sistemas',
        'degree_level' => 'Licenciatura',
        'start_year' => 2015,
        'start_month' => 2,
        'end_year' => 2020,
        'end_month' => 12,
        'is_in_progress' => false,
        'description' => 'Formación en análisis y diseño de sistemas.',
    ]);
    $excludedEducation = EducationEntry::factory()->create(['institution' => 'Excluida']);

    $certification = Certification::factory()->create([
        'name' => 'Curso de Laravel',
        'institution' => 'Academy',
        'date_year' => 2023,
        'date_month' => 6,
        'duration' => '40 horas',
        'category' => 'Desarrollo web',
        'code' => 'LAR-2023-001',
        'validation_url' => 'https://academy.example.com/validar/LAR-2023-001',
    ]);
    $excludedCertification = Certification::factory()->create(['name' => 'Excluida']);

    $skill = Skill::factory()->create(['name' => 'PHP', 'category' => 'tecnologia']);
    $excludedSkill = Skill::factory()->create(['name' => 'Excluida']);

    $project = Project::factory()->create([
        'name' => 'Portal interno',
        'role' => 'Desarrolladora',
        'start_year' => 2021,
        'start_month' => 1,
        'end_year' => 2021,
        'end_month' => 9,
        'description' => 'Migración del portal corporativo.',
        'results' => 'Reducción del tiempo de carga en un 40%.',
        'url' => 'https://example.com/proyecto',
    ]);
    $excludedProject = Project::factory()->create(['name' => 'Excluido']);

    $profile = CvProfile::factory()->create([
        'name' => 'Analista',
        'professional_title' => 'Analista de Sistemas',
        'summary' => 'Resumen propio.',
    ]);

    $profile->workExperiences()->attach($experience, ['relevance' => 'alta']);
    $profile->educationEntries()->attach($education, ['relevance' => 'media']);
    $profile->certifications()->attach($certification, ['relevance' => 'baja']);
    $profile->skills()->attach($skill, ['relevance' => 'alta']);
    $profile->projects()->attach($project, ['relevance' => 'media']);

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cv/Index')
            ->where('status', 'ready')
            ->where('selected_profile_id', $profile->id)
            ->has('preview.content.work_experiences', 1)
            ->where('preview.content.work_experiences.0', [
                'id' => $experience->id,
                'position' => 'Analista funcional',
                'company' => 'Acme',
                'start_year' => 2022,
                'start_month' => 3,
                'end_year' => null,
                'end_month' => null,
                'is_current' => true,
                'description' => 'Relevamiento y documentación de procesos.',
                'responsibilities' => "Relevamiento de requerimientos.\nDocumentación funcional.",
                'achievements' => 'Reducción de tiempos de proceso en un 30%.',
            ])
            ->has('preview.content.education_entries', 1)
            ->where('preview.content.education_entries.0', [
                'id' => $education->id,
                'institution' => 'Universidad',
                'program' => 'Sistemas',
                'degree_level' => 'Licenciatura',
                'start_year' => 2015,
                'start_month' => 2,
                'end_year' => 2020,
                'end_month' => 12,
                'is_in_progress' => false,
                'description' => 'Formación en análisis y diseño de sistemas.',
            ])
            ->has('preview.content.certifications', 1)
            ->where('preview.content.certifications.0', [
                'id' => $certification->id,
                'name' => 'Curso de Laravel',
                'institution' => 'Academy',
                'date_year' => 2023,
                'date_month' => 6,
                'duration' => '40 horas',
                'category' => 'Desarrollo web',
                'code' => 'LAR-2023-001',
                'validation_url' => 'https://academy.example.com/validar/LAR-2023-001',
            ])
            ->has('preview.content.skills', 1)
            ->where('preview.content.skills.0', [
                'id' => $skill->id,
                'name' => 'PHP',
                'category' => 'tecnologia',
            ])
            ->has('preview.content.projects', 1)
            ->where('preview.content.projects.0', [
                'id' => $project->id,
                'name' => 'Portal interno',
                'role' => 'Desarrolladora',
                'start_year' => 2021,
                'start_month' => 1,
                'end_year' => 2021,
                'end_month' => 9,
                'description' => 'Migración del portal corporativo.',
                'results' => 'Reducción del tiempo de carga en un 40%.',
                'url' => 'https://example.com/proyecto',
            ])
            ->missing('preview.content.work_experiences.0.relevance')
            ->missing('preview.content.work_experiences.0.master_path')
            ->missing('preview.content.education_entries.0.relevance')
            ->missing('preview.content.education_entries.0.master_path')
            ->missing('preview.content.certifications.0.relevance')
            ->missing('preview.content.certifications.0.master_path')
            ->missing('preview.content.skills.0.relevance')
            ->missing('preview.content.skills.0.master_path')
            ->missing('preview.content.projects.0.relevance')
            ->missing('preview.content.projects.0.master_path')
            ->missing('preview.name')
            ->missing('preview.is_active')
            ->missing('preview.template'));

    expect($excludedExperience->id)->not->toBe($experience->id)
        ->and($excludedEducation->id)->not->toBe($education->id)
        ->and($excludedCertification->id)->not->toBe($certification->id)
        ->and($excludedSkill->id)->not->toBe($skill->id)
        ->and($excludedProject->id)->not->toBe($project->id);
});

it('orders each section by relevance and master tiebreak', function () {
    cvGeneratorMaster(['full_name' => 'Ada Lovelace']);

    $experienceLow = WorkExperience::factory()->create([
        'position' => 'Baja 2024',
        'start_year' => 2024,
        'start_month' => 1,
    ]);
    $experienceHighOld = WorkExperience::factory()->create([
        'position' => 'Alta 2018',
        'start_year' => 2018,
        'start_month' => 5,
    ]);
    $experienceHighNew = WorkExperience::factory()->create([
        'position' => 'Alta 2022',
        'start_year' => 2022,
        'start_month' => 1,
    ]);
    $experienceMedium = WorkExperience::factory()->create([
        'position' => 'Media 2023',
        'start_year' => 2023,
        'start_month' => 1,
    ]);

    $educationLow = EducationEntry::factory()->create([
        'institution' => 'Baja',
        'start_year' => 2024,
    ]);
    $educationHigh = EducationEntry::factory()->create([
        'institution' => 'Alta',
        'start_year' => 2019,
    ]);

    $certificationHigh = Certification::factory()->create([
        'name' => 'Alta',
        'date_year' => 2023,
    ]);
    $certificationLow = Certification::factory()->create([
        'name' => 'Baja',
        'date_year' => 2024,
    ]);

    $skillFunctional = Skill::factory()->create([
        'name' => 'Análisis',
        'category' => 'funcional',
    ]);
    $skillTechnology = Skill::factory()->create([
        'name' => 'PHP',
        'category' => 'tecnologia',
    ]);
    $skillMedium = Skill::factory()->create([
        'name' => 'Git',
        'category' => 'herramienta',
    ]);

    $projectLow = Project::factory()->create([
        'name' => 'Baja',
        'start_year' => 2024,
    ]);
    $projectHigh = Project::factory()->create([
        'name' => 'Alta',
        'start_year' => 2018,
    ]);

    $profile = CvProfile::factory()->create();

    $profile->workExperiences()->attach($experienceLow, ['relevance' => 'baja']);
    $profile->workExperiences()->attach($experienceHighOld, ['relevance' => 'alta']);
    $profile->workExperiences()->attach($experienceMedium, ['relevance' => 'media']);
    $profile->workExperiences()->attach($experienceHighNew, ['relevance' => 'alta']);
    $profile->educationEntries()->attach($educationLow, ['relevance' => 'baja']);
    $profile->educationEntries()->attach($educationHigh, ['relevance' => 'alta']);
    $profile->certifications()->attach($certificationLow, ['relevance' => 'baja']);
    $profile->certifications()->attach($certificationHigh, ['relevance' => 'alta']);
    $profile->skills()->attach($skillTechnology, ['relevance' => 'alta']);
    $profile->skills()->attach($skillMedium, ['relevance' => 'media']);
    $profile->skills()->attach($skillFunctional, ['relevance' => 'alta']);
    $profile->projects()->attach($projectLow, ['relevance' => 'baja']);
    $profile->projects()->attach($projectHigh, ['relevance' => 'alta']);

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.content.work_experiences.0.id', $experienceHighNew->id)
            ->where('preview.content.work_experiences.1.id', $experienceHighOld->id)
            ->where('preview.content.work_experiences.2.id', $experienceMedium->id)
            ->where('preview.content.work_experiences.3.id', $experienceLow->id)
            ->where('preview.content.education_entries.0.id', $educationHigh->id)
            ->where('preview.content.education_entries.1.id', $educationLow->id)
            ->where('preview.content.certifications.0.id', $certificationHigh->id)
            ->where('preview.content.certifications.1.id', $certificationLow->id)
            ->where('preview.content.skills.0.id', $skillFunctional->id)
            ->where('preview.content.skills.1.id', $skillTechnology->id)
            ->where('preview.content.skills.2.id', $skillMedium->id)
            ->where('preview.content.projects.0.id', $projectHigh->id)
            ->where('preview.content.projects.1.id', $projectLow->id));
});

it('uses the master id descending as a stable tiebreak', function () {
    cvGeneratorMaster(['full_name' => 'Ada Lovelace']);

    $first = WorkExperience::factory()->create([
        'company' => 'Primera',
        'start_year' => 2020,
        'start_month' => 1,
    ]);
    $second = WorkExperience::factory()->create([
        'company' => 'Segunda',
        'start_year' => 2020,
        'start_month' => 1,
    ]);

    $profile = CvProfile::factory()->create();
    $profile->workExperiences()->attach($first, ['relevance' => 'alta']);
    $profile->workExperiences()->attach($second, ['relevance' => 'alta']);

    $this->get("/cv?profile={$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.content.work_experiences.0.id', $second->id)
            ->where('preview.content.work_experiences.1.id', $first->id));
});

it('does not persist or modify anything when the preview is built', function () {
    $master = cvGeneratorMaster(['full_name' => 'Ada Lovelace']);
    $experience = WorkExperience::factory()->create();
    $profile = CvProfile::factory()->create();
    $profile->workExperiences()->attach($experience, ['relevance' => 'alta']);

    $masterUpdatedAt = $master->updated_at;
    $profileUpdatedAt = $profile->updated_at;
    $counts = [
        'profiles' => Profile::count(),
        'cv_profiles' => CvProfile::count(),
        'work_experiences' => WorkExperience::count(),
        'pivot' => DB::table('cv_profile_work_experience')->count(),
    ];

    $this->get("/cv?profile={$profile->id}")->assertOk();

    expect(Profile::count())->toBe($counts['profiles'])
        ->and(CvProfile::count())->toBe($counts['cv_profiles'])
        ->and(WorkExperience::count())->toBe($counts['work_experiences'])
        ->and(DB::table('cv_profile_work_experience')->count())->toBe($counts['pivot'])
        ->and($master->fresh()->updated_at->equalTo($masterUpdatedAt))->toBeTrue()
        ->and($profile->fresh()->updated_at->equalTo($profileUpdatedAt))->toBeTrue();
});

it('recomposes different active profiles without residues and preserves the selection id', function () {
    cvGeneratorMaster([
        'full_name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    $shared = WorkExperience::factory()->create([
        'position' => 'Experiencia compartida',
        'company' => 'Compartida',
        'start_year' => 2020,
        'start_month' => 1,
    ]);
    $onlyA = WorkExperience::factory()->create([
        'position' => 'Solo A',
        'company' => 'Empresa A',
        'start_year' => 2021,
        'start_month' => 1,
    ]);
    $onlyB = WorkExperience::factory()->create([
        'position' => 'Solo B',
        'company' => 'Empresa B',
        'start_year' => 2022,
        'start_month' => 1,
    ]);

    $profileA = CvProfile::factory()->create([
        'name' => 'Analista',
        'professional_title' => 'Título A',
        'summary' => 'Resumen A',
    ]);
    $profileB = CvProfile::factory()->create([
        'name' => 'Programador',
        'professional_title' => 'Título B',
        'summary' => 'Resumen B',
    ]);

    $profileA->workExperiences()->attach($shared, ['relevance' => 'alta']);
    $profileA->workExperiences()->attach($onlyA, ['relevance' => 'media']);
    $profileB->workExperiences()->attach($onlyB, ['relevance' => 'alta']);
    $profileB->workExperiences()->attach($shared, ['relevance' => 'baja']);

    $profileUpdatedA = $profileA->updated_at;
    $profileUpdatedB = $profileB->updated_at;
    $counts = [
        'cv_profiles' => CvProfile::count(),
        'work_experiences' => WorkExperience::count(),
        'pivot_a' => DB::table('cv_profile_work_experience')
            ->where('cv_profile_id', $profileA->id)
            ->count(),
        'pivot_b' => DB::table('cv_profile_work_experience')
            ->where('cv_profile_id', $profileB->id)
            ->count(),
    ];

    $this->get("/cv?profile={$profileA->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'ready')
            ->where('selected_profile_id', $profileA->id)
            ->has('profiles', 2)
            ->where('profiles.0.name', 'Analista')
            ->where('profiles.1.name', 'Programador')
            ->where('preview.professional_title', 'Título A')
            ->where('preview.summary', 'Resumen A')
            ->has('preview.content.work_experiences', 2)
            ->where('preview.content.work_experiences.0.id', $shared->id)
            ->where('preview.content.work_experiences.1.id', $onlyA->id)
            ->missing('preview.content.work_experiences.2'));

    $this->get("/cv?profile={$profileB->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'ready')
            ->where('selected_profile_id', $profileB->id)
            ->where('preview.professional_title', 'Título B')
            ->where('preview.summary', 'Resumen B')
            ->has('preview.content.work_experiences', 2)
            ->where('preview.content.work_experiences.0.id', $onlyB->id)
            ->where('preview.content.work_experiences.1.id', $shared->id)
            ->missing('preview.content.work_experiences.2'));

    $this->get("/cv?profile={$profileA->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'ready')
            ->where('selected_profile_id', $profileA->id)
            ->where('preview.professional_title', 'Título A')
            ->where('preview.summary', 'Resumen A')
            ->has('preview.content.work_experiences', 2)
            ->where('preview.content.work_experiences.0.id', $shared->id)
            ->where('preview.content.work_experiences.1.id', $onlyA->id));

    expect(CvProfile::count())->toBe($counts['cv_profiles'])
        ->and(WorkExperience::count())->toBe($counts['work_experiences'])
        ->and(DB::table('cv_profile_work_experience')
            ->where('cv_profile_id', $profileA->id)
            ->count())->toBe($counts['pivot_a'])
        ->and(DB::table('cv_profile_work_experience')
            ->where('cv_profile_id', $profileB->id)
            ->count())->toBe($counts['pivot_b'])
        ->and($profileA->fresh()->updated_at->equalTo($profileUpdatedA))->toBeTrue()
        ->and($profileB->fresh()->updated_at->equalTo($profileUpdatedB))->toBeTrue();
});

it('clears the selection and offers active alternatives when the selected profile is unavailable', function () {
    $active = CvProfile::factory()->create([
        'name' => 'Analista',
        'professional_title' => 'Activo',
    ]);
    $inactive = CvProfile::factory()->inactive()->create([
        'name' => 'Programador',
        'professional_title' => 'Inactivo',
    ]);

    $this->get("/cv?profile={$active->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'ready')
            ->where('selected_profile_id', $active->id));

    $this->get("/cv?profile={$inactive->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'unavailable')
            ->where('selected_profile_id', null)
            ->where('preview', null)
            ->has('profiles', 1)
            ->where('profiles.0.id', $active->id));
});

it('reflects live master and record updates in the next request', function () {
    $master = cvGeneratorMaster([
        'full_name' => 'Ada Lovelace',
        'phone' => '+54 11 0000 0000',
        'email' => 'old@example.com',
    ]);

    $experience = WorkExperience::factory()->create([
        'position' => 'Analista',
        'company' => 'Acme',
        'achievements' => 'Logro inicial.',
    ]);
    $education = EducationEntry::factory()->create([
        'institution' => 'Universidad',
        'program' => 'Sistemas',
    ]);
    $certification = Certification::factory()->create([
        'name' => 'Curso inicial',
        'duration' => '20 horas',
    ]);
    $skill = Skill::factory()->create([
        'name' => 'PHP',
        'category' => 'tecnologia',
    ]);
    $project = Project::factory()->create([
        'name' => 'Portal',
        'results' => 'Resultado inicial.',
    ]);

    $profile = CvProfile::factory()->create([
        'professional_title' => 'Título inicial',
        'summary' => 'Resumen inicial',
    ]);

    $profile->workExperiences()->attach($experience, ['relevance' => 'alta']);
    $profile->educationEntries()->attach($education, ['relevance' => 'media']);
    $profile->certifications()->attach($certification, ['relevance' => 'baja']);
    $profile->skills()->attach($skill, ['relevance' => 'alta']);
    $profile->projects()->attach($project, ['relevance' => 'media']);

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.personal_data.email', 'old@example.com')
            ->where('preview.professional_title', 'Título inicial')
            ->where('preview.summary', 'Resumen inicial')
            ->where('preview.content.work_experiences.0.position', 'Analista')
            ->where('preview.content.work_experiences.0.achievements', 'Logro inicial.')
            ->where('preview.content.education_entries.0.program', 'Sistemas')
            ->where('preview.content.certifications.0.name', 'Curso inicial')
            ->where('preview.content.certifications.0.duration', '20 horas')
            ->where('preview.content.skills.0.name', 'PHP')
            ->where('preview.content.projects.0.name', 'Portal')
            ->where('preview.content.projects.0.results', 'Resultado inicial.'));

    $master->update([
        'email' => 'nuevo@example.com',
        'phone' => '+54 11 9999 9999',
    ]);
    $experience->update([
        'position' => 'Analista senior',
        'achievements' => 'Logro actualizado.',
    ]);
    $education->update(['program' => 'Maestría en Sistemas']);
    $certification->update([
        'name' => 'Curso avanzado',
        'duration' => '80 horas',
    ]);
    $skill->update(['name' => 'PHP 8', 'category' => 'herramienta']);
    $project->update([
        'name' => 'Portal nuevo',
        'results' => 'Resultado actualizado.',
    ]);
    $profile->update([
        'professional_title' => 'Título actualizado',
        'summary' => 'Resumen actualizado',
    ]);

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.personal_data.email', 'nuevo@example.com')
            ->where('preview.personal_data.phone', '+54 11 9999 9999')
            ->where('preview.professional_title', 'Título actualizado')
            ->where('preview.summary', 'Resumen actualizado')
            ->where('preview.content.work_experiences.0.position', 'Analista senior')
            ->where('preview.content.work_experiences.0.achievements', 'Logro actualizado.')
            ->where('preview.content.education_entries.0.program', 'Maestría en Sistemas')
            ->where('preview.content.certifications.0.name', 'Curso avanzado')
            ->where('preview.content.certifications.0.duration', '80 horas')
            ->where('preview.content.skills.0.name', 'PHP 8')
            ->where('preview.content.skills.0.category', 'herramienta')
            ->where('preview.content.projects.0.name', 'Portal nuevo')
            ->where('preview.content.projects.0.results', 'Resultado actualizado.'));
});

it('updates the selected profile without altering another active profile', function () {
    cvGeneratorMaster(['full_name' => 'Ada Lovelace']);

    $shared = WorkExperience::factory()->create([
        'position' => 'Compartida',
        'start_year' => 2020,
    ]);
    $onlyA = WorkExperience::factory()->create([
        'position' => 'Solo A',
        'start_year' => 2021,
    ]);
    $onlyB = WorkExperience::factory()->create([
        'position' => 'Solo B',
        'start_year' => 2022,
    ]);

    $profileA = CvProfile::factory()->create([
        'name' => 'Analista',
        'professional_title' => 'Título A',
        'summary' => 'Resumen A',
    ]);
    $profileB = CvProfile::factory()->create([
        'name' => 'Programador',
        'professional_title' => 'Título B',
        'summary' => 'Resumen B',
    ]);

    $profileA->workExperiences()->attach($shared, ['relevance' => 'baja']);
    $profileA->workExperiences()->attach($onlyA, ['relevance' => 'alta']);
    $profileB->workExperiences()->attach($shared, ['relevance' => 'alta']);
    $profileB->workExperiences()->attach($onlyB, ['relevance' => 'baja']);

    $this->get("/cv?profile={$profileA->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.professional_title', 'Título A')
            ->where('preview.content.work_experiences.0.id', $onlyA->id)
            ->where('preview.content.work_experiences.1.id', $shared->id));

    $profileA->update([
        'professional_title' => 'Título A actualizado',
        'summary' => 'Resumen A actualizado',
    ]);
    $profileA->workExperiences()->updateExistingPivot($shared->id, [
        'relevance' => 'alta',
    ]);
    $profileA->workExperiences()->detach($onlyA->id);

    $this->get("/cv?profile={$profileA->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.professional_title', 'Título A actualizado')
            ->where('preview.summary', 'Resumen A actualizado')
            ->has('preview.content.work_experiences', 1)
            ->where('preview.content.work_experiences.0.id', $shared->id));

    $this->get("/cv?profile={$profileB->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.professional_title', 'Título B')
            ->where('preview.summary', 'Resumen B')
            ->has('preview.content.work_experiences', 2)
            ->where('preview.content.work_experiences.0.id', $shared->id)
            ->where('preview.content.work_experiences.1.id', $onlyB->id));
});

it('removes deleted master records and personal data while keeping the rest', function () {
    $master = cvGeneratorMaster([
        'full_name' => 'Ada Lovelace',
        'phone' => '+54 11 5555 1234',
        'email' => 'ada@example.com',
    ]);
    $link = $master->links()->create([
        'label' => 'Blog',
        'url' => 'https://blog.example.com',
    ]);

    $experienceKeep = WorkExperience::factory()->create(['position' => 'Conservar']);
    $experienceDelete = WorkExperience::factory()->create(['position' => 'Eliminar']);
    $educationKeep = EducationEntry::factory()->create(['program' => 'Conservar']);
    $educationDelete = EducationEntry::factory()->create(['program' => 'Eliminar']);
    $certificationKeep = Certification::factory()->create(['name' => 'Conservar']);
    $certificationDelete = Certification::factory()->create(['name' => 'Eliminar']);
    $skillKeep = Skill::factory()->create(['name' => 'Conservar']);
    $skillDelete = Skill::factory()->create(['name' => 'Eliminar']);
    $projectKeep = Project::factory()->create(['name' => 'Conservar']);
    $projectDelete = Project::factory()->create(['name' => 'Eliminar']);

    $profile = CvProfile::factory()->create();

    $profile->workExperiences()->attach($experienceKeep, ['relevance' => 'media']);
    $profile->workExperiences()->attach($experienceDelete, ['relevance' => 'media']);
    $profile->educationEntries()->attach($educationKeep, ['relevance' => 'media']);
    $profile->educationEntries()->attach($educationDelete, ['relevance' => 'media']);
    $profile->certifications()->attach($certificationKeep, ['relevance' => 'media']);
    $profile->certifications()->attach($certificationDelete, ['relevance' => 'media']);
    $profile->skills()->attach($skillKeep, ['relevance' => 'media']);
    $profile->skills()->attach($skillDelete, ['relevance' => 'media']);
    $profile->projects()->attach($projectKeep, ['relevance' => 'media']);
    $profile->projects()->attach($projectDelete, ['relevance' => 'media']);

    $this->get("/cv?profile={$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.content.work_experiences', 2)
            ->has('preview.content.education_entries', 2)
            ->has('preview.content.certifications', 2)
            ->has('preview.content.skills', 2)
            ->has('preview.content.projects', 2)
            ->where('preview.personal_data.email', 'ada@example.com')
            ->has('preview.personal_data.links', 1));

    $experienceDelete->delete();
    $educationDelete->delete();
    $certificationDelete->delete();
    $skillDelete->delete();
    $projectDelete->delete();
    $link->delete();
    $master->update(['email' => null]);

    $this->get("/cv?profile={$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.content.work_experiences', 1)
            ->where('preview.content.work_experiences.0.id', $experienceKeep->id)
            ->has('preview.content.education_entries', 1)
            ->where('preview.content.education_entries.0.id', $educationKeep->id)
            ->has('preview.content.certifications', 1)
            ->where('preview.content.certifications.0.id', $certificationKeep->id)
            ->has('preview.content.skills', 1)
            ->where('preview.content.skills.0.id', $skillKeep->id)
            ->has('preview.content.projects', 1)
            ->where('preview.content.projects.0.id', $projectKeep->id)
            ->where('preview.personal_data.email', null)
            ->where('preview.personal_data.links', [])
            ->where('preview.personal_data.full_name', 'Ada Lovelace')
            ->where('preview.personal_data.phone', '+54 11 5555 1234'));
});

it('recomputes live data without storing a CV copy', function () {
    cvGeneratorMaster(['full_name' => 'Ada Lovelace']);

    $experience = WorkExperience::factory()->create(['position' => 'Inicial']);
    $profile = CvProfile::factory()->create();
    $profile->workExperiences()->attach($experience, ['relevance' => 'alta']);

    $this->get("/cv?profile={$profile->id}")->assertOk();

    $experience->update(['position' => 'Actualizado']);

    $this->get("/cv?profile={$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.content.work_experiences.0.position', 'Actualizado'));

    expect(CvProfile::count())->toBe(1)
        ->and(DB::table('cv_profile_work_experience')->count())->toBe(1)
        ->and(WorkExperience::count())->toBe(1);
});

it('returns exact source links for the selected profile', function () {
    $profileA = CvProfile::factory()->create(['name' => 'Analista']);
    $profileB = CvProfile::factory()->create(['name' => 'Programador']);

    $this->get("/cv?profile={$profileA->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.source_links', [
                'profile' => "/profiles/{$profileA->id}/edit",
                'personal_data' => '/profile',
                'work_experiences' => '/experiences',
                'education_entries' => '/education',
                'certifications' => '/certifications',
                'skills' => '/skills',
                'projects' => '/projects',
            ]));

    $this->get("/cv?profile={$profileB->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where(
                'preview.source_links.profile',
                "/profiles/{$profileB->id}/edit",
            ));
});

it('stops offering a profile deactivated after a previous request', function () {
    $profile = CvProfile::factory()->create(['name' => 'Analista']);

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'ready')
            ->where('selected_profile_id', $profile->id));

    $profile->update(['is_active' => false]);

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'no_active_profiles')
            ->where('selected_profile_id', null)
            ->where('preview', null)
            ->where('profiles', []));
});

it('returns a valid preview when the profile has no content but the master has personal data', function () {
    cvGeneratorMaster([
        'full_name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    $profile = CvProfile::factory()->create([
        'professional_title' => null,
        'summary' => null,
    ]);

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'ready')
            ->where('selected_profile_id', $profile->id)
            ->where('preview.has_data', true)
            ->where('preview.personal_data.full_name', 'Ada Lovelace')
            ->where('preview.personal_data.email', 'ada@example.com')
            ->where('preview.professional_title', null)
            ->where('preview.summary', null)
            ->where('preview.content', [
                'work_experiences' => [],
                'education_entries' => [],
                'certifications' => [],
                'skills' => [],
                'projects' => [],
            ]));
});

it('uses the profile own title and summary when the master is empty', function () {
    Profile::current();

    $profile = CvProfile::factory()->create([
        'professional_title' => 'Título propio',
        'summary' => 'Resumen propio',
    ]);

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'ready')
            ->where('preview.personal_data.has_data', false)
            ->where('preview.professional_title', 'Título propio')
            ->where('preview.summary', 'Resumen propio')
            ->where('preview.has_data', true)
            ->where('preview.content', [
                'work_experiences' => [],
                'education_entries' => [],
                'certifications' => [],
                'skills' => [],
                'projects' => [],
            ]));
});

it('reports has_data false when the profile has no data at all', function () {
    Profile::current();

    $profile = CvProfile::factory()->create([
        'professional_title' => null,
        'summary' => null,
    ]);

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'ready')
            ->where('preview.has_data', false)
            ->where('preview.personal_data.has_data', false)
            ->where('preview.professional_title', null)
            ->where('preview.summary', null)
            ->where('preview.content', [
                'work_experiences' => [],
                'education_entries' => [],
                'certifications' => [],
                'skills' => [],
                'projects' => [],
            ]));
});

it('returns a controlled error without a partial preview when composition fails', function () {
    $profile = CvProfile::factory()->create();

    $this->app->bind(CvPreviewContent::class, fn () => new class extends CvPreviewContent
    {
        public function build(CvProfile $profile): array
        {
            throw new RuntimeException('Fallo de composición');
        }
    });

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'error')
            ->where('selected_profile_id', $profile->id)
            ->where('preview', null)
            ->has('profiles', 1)
            ->where('profiles.0.id', $profile->id));
});

it('preserves long accented and special text completely', function () {
    $longText = str_repeat('Áéíóú ñandú — ✓ ', 20);

    cvGeneratorMaster(['full_name' => 'Ada Lovelace '.$longText]);

    $experience = WorkExperience::factory()->create([
        'description' => $longText,
        'responsibilities' => $longText."\n".$longText,
        'achievements' => $longText,
    ]);

    $profile = CvProfile::factory()->create(['summary' => $longText]);
    $profile->workExperiences()->attach($experience, ['relevance' => 'alta']);

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.personal_data.full_name', 'Ada Lovelace '.$longText)
            ->where('preview.summary', $longText)
            ->where('preview.content.work_experiences.0.description', $longText)
            ->where('preview.content.work_experiences.0.responsibilities', $longText."\n".$longText)
            ->where('preview.content.work_experiences.0.achievements', $longText));
});

it('exposes only the read-only cv route and no scope-expanding endpoints', function () {
    expect(Route::has('cv.index'))->toBeTrue();

    foreach ([
        'cv.export',
        'cv.print',
        'cv.download',
        'cv.pdf',
        'cv.template',
        'cv.store',
        'cv.update',
        'cv.destroy',
        'cv.regenerate',
        'cv.reorder',
    ] as $routeName) {
        expect(Route::has($routeName))->toBeFalse();
    }

    $cvRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => $route->uri() === 'cv');

    expect($cvRoutes)->toHaveCount(1);

    $cvRoute = $cvRoutes->first();

    expect($cvRoute->getName())->toBe('cv.index')
        ->and($cvRoute->methods())->toContain('GET')
        ->and($cvRoute->methods())->not->toContain('POST')
        ->and($cvRoute->methods())->not->toContain('PUT')
        ->and($cvRoute->methods())->not->toContain('PATCH')
        ->and($cvRoute->methods())->not->toContain('DELETE');

    $this->post('/cv')->assertStatus(405);
    $this->put('/cv')->assertStatus(405);
    $this->patch('/cv')->assertStatus(405);
    $this->delete('/cv')->assertStatus(405);
});
