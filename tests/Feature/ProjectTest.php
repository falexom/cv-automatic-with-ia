<?php

use App\Models\Project;
use App\Models\Skill;
use Inertia\Testing\AssertableInertia as Assert;

it('requires the name', function () {
    $this->post('/projects', ['name' => ''])
        ->assertSessionHasErrors('name');

    expect(Project::query()->count())->toBe(0);
});

it('stores a project with minimal data', function () {
    $this->post('/projects', ['name' => 'Portal interno'])
        ->assertRedirect(route('projects.index'));

    $this->assertDatabaseHas('projects', [
        'name' => 'Portal interno',
        'start_year' => null,
    ]);
});

it('rejects a month without a year', function () {
    $this->post('/projects', [
        'name' => 'Portal interno',
        'end_month' => 3,
    ])->assertSessionHasErrors('end_year');
});

it('blocks an end date before the start date', function () {
    $this->post('/projects', [
        'name' => 'Portal interno',
        'start_year' => 2023,
        'start_month' => 5,
        'end_year' => 2023,
        'end_month' => 3,
    ])->assertSessionHasErrors('end_year');

    expect(Project::query()->count())->toBe(0);
});

it('validates the project URL format', function () {
    $this->post('/projects', [
        'name' => 'Portal interno',
        'url' => 'no-es-url',
    ])->assertSessionHasErrors('url');
});

it('syncs skills and lists projects ordered by start date', function () {
    $php = Skill::factory()->create(['name' => 'PHP', 'category' => 'tecnologia']);

    Project::factory()->create(['name' => 'Viejo', 'start_year' => 2018, 'start_month' => 2, 'end_year' => 2019, 'end_month' => 1]);
    Project::factory()->create(['name' => 'Nuevo', 'start_year' => 2024, 'start_month' => null, 'end_year' => null, 'end_month' => null]);
    Project::factory()->create(['name' => 'SinFecha', 'start_year' => null, 'start_month' => null, 'end_year' => null, 'end_month' => null]);

    $this->post('/projects', [
        'name' => 'Con habilidad',
        'start_year' => 2022,
        'skill_ids' => [$php->id],
    ])->assertRedirect(route('projects.index'));

    expect(Project::query()->where('name', 'Con habilidad')->sole()->skills()->pluck('skills.id')->all())
        ->toBe([$php->id]);

    $this->get('/projects')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Index')
            ->where('records.0.name', 'Nuevo')
            ->where('records.1.name', 'Con habilidad')
            ->where('records.2.name', 'Viejo')
            ->where('records.3.name', 'SinFecha'));
});

it('updates a project clearing cleared fields', function () {
    $project = Project::factory()->create([
        'name' => 'Portal interno',
        'end_year' => 2022,
        'end_month' => 6,
    ]);

    $this->put("/projects/{$project->id}", [
        'name' => 'Portal interno renovado',
        'start_year' => $project->start_year,
    ])->assertRedirect(route('projects.index'));

    $project->refresh();
    expect($project->name)->toBe('Portal interno renovado');
    expect($project->end_year)->toBeNull();
    expect($project->end_month)->toBeNull();
});

it('deletes a project and its links but keeps the skills', function () {
    $skill = Skill::factory()->create(['name' => 'PHP', 'category' => 'tecnologia']);
    $project = Project::factory()->create();
    $project->skills()->attach($skill);

    $this->delete("/projects/{$project->id}")
        ->assertRedirect(route('projects.index'));

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    $this->assertDatabaseMissing('skill_project', ['project_id' => $project->id]);
    expect(Skill::query()->whereKey($skill->id)->exists())->toBeTrue();
});
