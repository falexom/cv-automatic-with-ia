<?php

use App\Models\Certification;
use App\Models\CvProfile;
use App\Models\EducationEntry;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;
use App\Support\CvProfileContent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

it('creates a profile without content and with empty selections', function () {
    $this->post('/profiles', ['name' => 'Analista'])->assertRedirect();

    $profile = CvProfile::query()->firstOrFail();

    expect($profile->workExperiences()->count())->toBe(0)
        ->and($profile->educationEntries()->count())->toBe(0)
        ->and($profile->certifications()->count())->toBe(0)
        ->and($profile->skills()->count())->toBe(0)
        ->and($profile->projects()->count())->toBe(0);

    $this->get("/profiles/{$profile->id}/edit")
        ->assertInertia(fn (Assert $page) => $page
            ->where('selections.work_experiences', [])
            ->where('selections.education_entries', [])
            ->where('selections.certifications', [])
            ->where('selections.skills', [])
            ->where('selections.projects', []));

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('content.work_experiences', [])
            ->where('content.education_entries', [])
            ->where('content.certifications', [])
            ->where('content.skills', [])
            ->where('content.projects', []));
});

it('shows only explicitly included elements', function () {
    $included = WorkExperience::factory()->create();
    $excluded = WorkExperience::factory()->create();

    $this->post('/profiles', [
        'name' => 'Analista',
        'work_experiences' => [['id' => $included->id]],
    ])->assertRedirect();

    $profile = CvProfile::query()->firstOrFail();

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->has('content.work_experiences', 1)
            ->where('content.work_experiences.0.id', $included->id)
            ->where('content.work_experiences.0.relevance', 'media'));
});

it('includes one element of each of the five classes', function () {
    $experience = WorkExperience::factory()->create();
    $education = EducationEntry::factory()->create();
    $certification = Certification::factory()->create();
    $skill = Skill::factory()->create();
    $project = Project::factory()->create();

    $this->post('/profiles', [
        'name' => 'Analista',
        'work_experiences' => [['id' => $experience->id]],
        'education_entries' => [['id' => $education->id]],
        'certifications' => [['id' => $certification->id]],
        'skills' => [['id' => $skill->id]],
        'projects' => [['id' => $project->id]],
    ])->assertRedirect();

    $profile = CvProfile::query()->firstOrFail();

    $this->assertDatabaseHas('cv_profile_work_experience', ['cv_profile_id' => $profile->id, 'work_experience_id' => $experience->id]);
    $this->assertDatabaseHas('cv_profile_education_entry', ['cv_profile_id' => $profile->id, 'education_entry_id' => $education->id]);
    $this->assertDatabaseHas('cv_profile_certification', ['cv_profile_id' => $profile->id, 'certification_id' => $certification->id]);
    $this->assertDatabaseHas('cv_profile_skill', ['cv_profile_id' => $profile->id, 'skill_id' => $skill->id]);
    $this->assertDatabaseHas('cv_profile_project', ['cv_profile_id' => $profile->id, 'project_id' => $project->id]);

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->has('content.work_experiences', 1)
            ->has('content.education_entries', 1)
            ->has('content.certifications', 1)
            ->has('content.skills', 1)
            ->has('content.projects', 1));
});

it('stores the default media relevance and accepts alta and baja', function () {
    $a = WorkExperience::factory()->create(['start_year' => 2020]);
    $b = WorkExperience::factory()->create(['start_year' => 2021]);
    $c = WorkExperience::factory()->create(['start_year' => 2022]);

    $this->post('/profiles', [
        'name' => 'Analista',
        'work_experiences' => [
            ['id' => $a->id],
            ['id' => $b->id, 'relevance' => 'baja'],
            ['id' => $c->id, 'relevance' => 'alta'],
        ],
    ])->assertRedirect();

    $profile = CvProfile::query()->firstOrFail();

    $this->assertDatabaseHas('cv_profile_work_experience', ['cv_profile_id' => $profile->id, 'work_experience_id' => $a->id, 'relevance' => 'media']);
    $this->assertDatabaseHas('cv_profile_work_experience', ['cv_profile_id' => $profile->id, 'work_experience_id' => $b->id, 'relevance' => 'baja']);
    $this->assertDatabaseHas('cv_profile_work_experience', ['cv_profile_id' => $profile->id, 'work_experience_id' => $c->id, 'relevance' => 'alta']);
});

it('rejects an invalid relevance, foreign master ids and duplicate ids', function () {
    $experience = WorkExperience::factory()->create();
    $otherMaster = Profile::factory()->create();
    $foreign = WorkExperience::factory()->create(['profile_id' => $otherMaster->id]);
    $project = Project::factory()->create(['id' => 9999]);

    $this->post('/profiles', [
        'name' => 'Analista',
        'work_experiences' => [['id' => $experience->id, 'relevance' => 'urgente']],
    ])->assertSessionHasErrors('work_experiences.0.relevance');

    $this->post('/profiles', [
        'name' => 'Analista',
        'work_experiences' => [['id' => 999999]],
    ])->assertSessionHasErrors('work_experiences.0.id');

    $this->post('/profiles', [
        'name' => 'Analista',
        'work_experiences' => [['id' => $project->id]],
    ])->assertSessionHasErrors('work_experiences.0.id');

    $this->post('/profiles', [
        'name' => 'Analista',
        'work_experiences' => [['id' => $foreign->id]],
    ])->assertSessionHasErrors('work_experiences.0.id');

    $this->post('/profiles', [
        'name' => 'Analista',
        'work_experiences' => [['id' => $experience->id], ['id' => $experience->id]],
    ])->assertSessionHasErrors('work_experiences.1.id');

    expect(CvProfile::query()->count())->toBe(0);
});

it('orders content by relevance descending', function () {
    $low = WorkExperience::factory()->create(['start_year' => 2024]);
    $high = WorkExperience::factory()->create(['start_year' => 2020]);
    $medium = WorkExperience::factory()->create(['start_year' => 2022]);

    $this->post('/profiles', [
        'name' => 'Analista',
        'work_experiences' => [
            ['id' => $low->id, 'relevance' => 'baja'],
            ['id' => $medium->id, 'relevance' => 'media'],
            ['id' => $high->id, 'relevance' => 'alta'],
        ],
    ])->assertRedirect();

    $profile = CvProfile::query()->firstOrFail();

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('content.work_experiences.0.id', $high->id)
            ->where('content.work_experiences.1.id', $medium->id)
            ->where('content.work_experiences.2.id', $low->id));
});

it('breaks relevance ties with master chronological order and puts undated records last', function () {
    $old = WorkExperience::factory()->create(['start_year' => 2019, 'start_month' => 1]);
    $new = WorkExperience::factory()->create(['start_year' => 2022, 'start_month' => 5]);
    $undated = WorkExperience::factory()->create(['start_year' => null, 'start_month' => null]);

    $educationOld = EducationEntry::factory()->create(['start_year' => 2018, 'start_month' => 3]);
    $educationNew = EducationEntry::factory()->create(['start_year' => 2021, 'start_month' => 8]);

    $certificationOld = Certification::factory()->create(['date_year' => 2017, 'date_month' => 2]);
    $certificationNew = Certification::factory()->create(['date_year' => 2023, 'date_month' => 4]);

    $projectOld = Project::factory()->create(['start_year' => 2016, 'start_month' => 6]);
    $projectNew = Project::factory()->create(['start_year' => 2024, 'start_month' => 1]);

    $this->post('/profiles', [
        'name' => 'Analista',
        'work_experiences' => [
            ['id' => $old->id],
            ['id' => $undated->id],
            ['id' => $new->id],
        ],
        'education_entries' => [
            ['id' => $educationOld->id],
            ['id' => $educationNew->id],
        ],
        'certifications' => [
            ['id' => $certificationOld->id],
            ['id' => $certificationNew->id],
        ],
        'projects' => [
            ['id' => $projectOld->id],
            ['id' => $projectNew->id],
        ],
    ])->assertRedirect();

    $profile = CvProfile::query()->firstOrFail();

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('content.work_experiences.0.id', $new->id)
            ->where('content.work_experiences.1.id', $old->id)
            ->where('content.work_experiences.2.id', $undated->id)
            ->where('content.education_entries.0.id', $educationNew->id)
            ->where('content.education_entries.1.id', $educationOld->id)
            ->where('content.certifications.0.id', $certificationNew->id)
            ->where('content.certifications.1.id', $certificationOld->id)
            ->where('content.projects.0.id', $projectNew->id)
            ->where('content.projects.1.id', $projectOld->id));
});

it('orders skills by category and then name', function () {
    $technology = Skill::factory()->create(['name' => 'PHP', 'category' => 'tecnologia']);
    $technical = Skill::factory()->create(['name' => 'Análisis funcional', 'category' => 'tecnica']);
    $tool = Skill::factory()->create(['name' => 'Jira', 'category' => 'herramienta']);

    $this->post('/profiles', [
        'name' => 'Analista',
        'skills' => [
            ['id' => $tool->id],
            ['id' => $technology->id],
            ['id' => $technical->id],
        ],
    ])->assertRedirect();

    $profile = CvProfile::query()->firstOrFail();

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('content.skills.0.id', $technical->id)
            ->where('content.skills.1.id', $tool->id)
            ->where('content.skills.2.id', $technology->id));
});

it('reuses the same element in two profiles with independent relevance', function () {
    $experience = WorkExperience::factory()->create();

    $this->post('/profiles', [
        'name' => 'Analista',
        'work_experiences' => [['id' => $experience->id, 'relevance' => 'alta']],
    ])->assertRedirect();

    $this->post('/profiles', [
        'name' => 'Programador',
        'work_experiences' => [['id' => $experience->id, 'relevance' => 'baja']],
    ])->assertRedirect();

    $analista = CvProfile::query()->where('name', 'Analista')->firstOrFail();
    $programador = CvProfile::query()->where('name', 'Programador')->firstOrFail();

    expect($analista->workExperiences()->first()->pivot->relevance)->toBe('alta')
        ->and($programador->workExperiences()->first()->pivot->relevance)->toBe('baja')
        ->and($experience->fresh()->id)->toBe($experience->id);
});

it('changes and removes associations without affecting another profile or the master', function () {
    $kept = WorkExperience::factory()->create();
    $removed = WorkExperience::factory()->create();

    $this->post('/profiles', [
        'name' => 'Analista',
        'work_experiences' => [
            ['id' => $kept->id, 'relevance' => 'alta'],
            ['id' => $removed->id],
        ],
    ])->assertRedirect();

    $this->post('/profiles', [
        'name' => 'Programador',
        'work_experiences' => [
            ['id' => $kept->id, 'relevance' => 'media'],
            ['id' => $removed->id, 'relevance' => 'baja'],
        ],
    ])->assertRedirect();

    $analista = CvProfile::query()->where('name', 'Analista')->firstOrFail();
    $programador = CvProfile::query()->where('name', 'Programador')->firstOrFail();

    $this->put("/profiles/{$analista->id}", [
        'name' => 'Analista',
        'work_experiences' => [['id' => $kept->id, 'relevance' => 'baja']],
    ])->assertRedirect();

    expect($analista->workExperiences()->pluck('work_experiences.id')->all())->toBe([$kept->id])
        ->and($analista->workExperiences()->first()->pivot->relevance)->toBe('baja')
        ->and($programador->workExperiences()->count())->toBe(2)
        ->and($programador->workExperiences()->first()->pivot->relevance)->toBe('media')
        ->and(WorkExperience::query()->count())->toBe(2);
});

it('re-including an element updates the association without duplicating it', function () {
    $experience = WorkExperience::factory()->create();

    $profile = CvProfile::factory()->create(['name' => 'Analista']);

    $this->put("/profiles/{$profile->id}", [
        'name' => 'Analista',
        'work_experiences' => [['id' => $experience->id, 'relevance' => 'alta']],
    ])->assertRedirect();

    $this->put("/profiles/{$profile->id}", [
        'name' => 'Analista',
        'work_experiences' => [['id' => $experience->id, 'relevance' => 'baja']],
    ])->assertRedirect();

    expect($profile->workExperiences()->count())->toBe(1)
        ->and($profile->workExperiences()->first()->pivot->relevance)->toBe('baja');
});

it('replaces each class completely and treats omitted arrays as empty', function () {
    $experience = WorkExperience::factory()->create();
    $skill = Skill::factory()->create();

    $profile = CvProfile::factory()->create(['name' => 'Analista']);

    $this->put("/profiles/{$profile->id}", [
        'name' => 'Analista',
        'work_experiences' => [['id' => $experience->id]],
        'skills' => [['id' => $skill->id]],
    ])->assertRedirect();

    $this->put("/profiles/{$profile->id}", [
        'name' => 'Analista',
    ])->assertRedirect();

    expect($profile->workExperiences()->count())->toBe(0)
        ->and($profile->skills()->count())->toBe(0);
});

it('does not create or modify master records', function () {
    $experience = WorkExperience::factory()->create([
        'company' => 'Acme',
        'position' => 'Analista',
    ]);

    $before = $experience->only(['company', 'position', 'description']);

    $this->post('/profiles', [
        'name' => 'Analista',
        'work_experiences' => [['id' => $experience->id, 'relevance' => 'alta']],
    ])->assertRedirect();

    $after = $experience->fresh()->only(['company', 'position', 'description']);

    expect($after)->toBe($before)
        ->and(WorkExperience::query()->count())->toBe(1);
});

it('keeps pivot tables limited to ids and relevance with a composite primary key', function () {
    foreach ([
        'cv_profile_work_experience' => ['cv_profile_id', 'work_experience_id', 'relevance'],
        'cv_profile_education_entry' => ['cv_profile_id', 'education_entry_id', 'relevance'],
        'cv_profile_certification' => ['cv_profile_id', 'certification_id', 'relevance'],
        'cv_profile_skill' => ['cv_profile_id', 'skill_id', 'relevance'],
        'cv_profile_project' => ['cv_profile_id', 'project_id', 'relevance'],
    ] as $table => $columns) {
        expect(Schema::getColumnListing($table))->toBe($columns)
            ->and(Schema::getColumnListing($table))->not->toContain('id')
            ->and(Schema::getColumnListing($table))->not->toContain('created_at');

        $primary = collect(DB::select("PRAGMA table_info('{$table}')"))
            ->where('pk', '>', 0)
            ->pluck('name')
            ->all();

        expect($primary)->toBe(array_slice($columns, 0, 2));
    }
});

it('does not persist a profile or associations when validation fails', function () {
    $experience = WorkExperience::factory()->create();

    $this->post('/profiles', [
        'name' => 'Analista',
        'work_experiences' => [['id' => $experience->id]],
        'skills' => [['id' => 999999]],
    ])->assertSessionHasErrors('skills.0.id');

    expect(CvProfile::query()->count())->toBe(0)
        ->and(DB::table('cv_profile_work_experience')->count())->toBe(0);
});

it('adds provenance paths and ordered content to the detail', function () {
    $experience = WorkExperience::factory()->create();
    $education = EducationEntry::factory()->create();
    $certification = Certification::factory()->create();
    $skill = Skill::factory()->create();
    $project = Project::factory()->create();

    $profile = CvProfile::factory()->create();

    $this->put("/profiles/{$profile->id}", [
        'name' => $profile->name,
        'work_experiences' => [['id' => $experience->id]],
        'education_entries' => [['id' => $education->id]],
        'certifications' => [['id' => $certification->id]],
        'skills' => [['id' => $skill->id]],
        'projects' => [['id' => $project->id]],
    ])->assertRedirect();

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('content.work_experiences.0.master_path', "/experiences/{$experience->id}/edit")
            ->where('content.education_entries.0.master_path', "/education/{$education->id}/edit")
            ->where('content.certifications.0.master_path', "/certifications/{$certification->id}/edit")
            ->where('content.skills.0.master_path', "/skills/{$skill->id}/edit")
            ->where('content.projects.0.master_path', "/projects/{$project->id}/edit"));
});

it('reflects master updates and cascades master deletions only to associations', function () {
    $experience = WorkExperience::factory()->create(['company' => 'Acme']);
    $otherExperience = WorkExperience::factory()->create();
    $skill = Skill::factory()->create();

    $profile = CvProfile::factory()->create();

    $this->put("/profiles/{$profile->id}", [
        'name' => $profile->name,
        'work_experiences' => [
            ['id' => $experience->id],
            ['id' => $otherExperience->id],
        ],
        'skills' => [['id' => $skill->id]],
    ])->assertRedirect();

    $experience->update(['company' => 'Acme Actualizada']);

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('content.work_experiences.1.company', 'Acme Actualizada'));

    $experience->delete();

    expect(CvProfile::query()->whereKey($profile->id)->exists())->toBeTrue()
        ->and($profile->workExperiences()->count())->toBe(1)
        ->and($profile->skills()->count())->toBe(1);

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->has('content.work_experiences', 1)
            ->where('content.work_experiences.0.id', $otherExperience->id));
});

it('keeps content for inactive profiles', function () {
    $experience = WorkExperience::factory()->create();
    $profile = CvProfile::factory()->inactive()->create();

    $this->put("/profiles/{$profile->id}", [
        'name' => $profile->name,
        'work_experiences' => [['id' => $experience->id]],
    ])->assertRedirect();

    $this->post("/profiles/{$profile->id}/activate")->assertRedirect();

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->has('content.work_experiences', 1));
});

it('rejects duplicate pivot rows at the database level', function () {
    $experience = WorkExperience::factory()->create();
    $profile = CvProfile::factory()->create();

    DB::table('cv_profile_work_experience')->insert([
        'cv_profile_id' => $profile->id,
        'work_experience_id' => $experience->id,
        'relevance' => 'media',
    ]);

    expect(fn () => DB::table('cv_profile_work_experience')->insert([
        'cv_profile_id' => $profile->id,
        'work_experience_id' => $experience->id,
        'relevance' => 'alta',
    ]))->toThrow(QueryException::class);
});

it('offers master options in master order and only for the current master', function () {
    $old = WorkExperience::factory()->create(['start_year' => 2019]);
    $new = WorkExperience::factory()->create(['start_year' => 2023]);
    $otherMaster = Profile::factory()->create();
    WorkExperience::factory()->create(['profile_id' => $otherMaster->id, 'start_year' => 2024]);

    $profile = CvProfile::factory()->create();

    $this->get("/profiles/{$profile->id}/edit")
        ->assertInertia(fn (Assert $page) => $page
            ->has('options.work_experiences', 2)
            ->where('options.work_experiences.0.id', $new->id)
            ->where('options.work_experiences.1.id', $old->id)
            ->has('options.work_experiences.0.company')
            ->has('options.work_experiences.0.position')
            ->has('options.work_experiences.0.start_year'));
});

it('rolls back the profile when a pivot persistence fails inside the transaction', function () {
    expect(fn () => CvProfileContent::create([
        'name' => 'Perfil fallido',
        'work_experiences' => [['id' => 999999]],
    ]))->toThrow(QueryException::class);

    expect(CvProfile::query()->count())->toBe(0)
        ->and(DB::table('cv_profile_work_experience')->count())->toBe(0);
});
