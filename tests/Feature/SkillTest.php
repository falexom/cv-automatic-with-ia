<?php

use App\Models\Certification;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;
use Inertia\Testing\AssertableInertia as Assert;

it('requires name and category', function () {
    $this->post('/skills', [
        'name' => '',
        'category' => '',
    ])->assertSessionHasErrors(['name', 'category']);

    expect(Skill::query()->count())->toBe(0);
});

it('rejects an invalid category', function () {
    $this->post('/skills', [
        'name' => 'PHP',
        'category' => 'otra',
    ])->assertSessionHasErrors('category');

    expect(Skill::query()->count())->toBe(0);
});

it('stores a skill with a valid category', function () {
    $this->post('/skills', [
        'name' => 'PHP',
        'category' => 'tecnologia',
    ])->assertRedirect(route('skills.index'));

    $this->assertDatabaseHas('skills', [
        'name' => 'PHP',
        'normalized_name' => 'php',
        'category' => 'tecnologia',
    ]);
});

it('blocks duplicates ignoring case and extra spaces', function () {
    Skill::factory()->create(['name' => 'PHP', 'category' => 'tecnologia']);

    $this->post('/skills', [
        'name' => '  php  ',
        'category' => 'tecnologia',
    ])->assertSessionHasErrors('name');

    expect(Skill::query()->count())->toBe(1);
});

it('allows updating a skill without tripping its own duplicate check', function () {
    $skill = Skill::factory()->create(['name' => 'PHP', 'category' => 'tecnologia']);

    $this->put("/skills/{$skill->id}", [
        'name' => 'PHP',
        'category' => 'herramienta',
    ])->assertRedirect(route('skills.index'));

    expect($skill->refresh()->category)->toBe('herramienta');
});

it('shows the catalog grouped data and where a skill is used', function () {
    $skill = Skill::factory()->create(['name' => 'PHP', 'category' => 'tecnologia']);
    Skill::factory()->create(['name' => 'Análisis de procesos', 'category' => 'funcional']);

    $experience = WorkExperience::factory()->create(['company' => 'Acme', 'position' => 'Analista']);
    $certification = Certification::factory()->create(['name' => 'Curso X']);
    $project = Project::factory()->create(['name' => 'Portal']);

    $skill->workExperiences()->attach($experience);
    $skill->certifications()->attach($certification);
    $skill->projects()->attach($project);

    $this->get('/skills')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Skills/Index')
            ->where('records.0.name', 'Análisis de procesos')
            ->where('records.1.name', 'PHP'));

    $this->get("/skills/{$skill->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Skills/Edit')
            ->where('record.name', 'PHP')
            ->where('usage.experiences.0.label', 'Analista — Acme')
            ->where('usage.certifications.0.label', 'Curso X')
            ->where('usage.projects.0.label', 'Portal'));
});

it('deletes a skill removing only the links and keeping entities', function () {
    $skill = Skill::factory()->create(['name' => 'PHP', 'category' => 'tecnologia']);
    $experience = WorkExperience::factory()->create();
    $skill->workExperiences()->attach($experience);

    $this->delete("/skills/{$skill->id}")
        ->assertRedirect(route('skills.index'));

    $this->assertDatabaseMissing('skills', ['id' => $skill->id]);
    $this->assertDatabaseMissing('skill_work_experience', ['skill_id' => $skill->id]);
    $this->assertDatabaseHas('work_experiences', ['id' => $experience->id]);
});
