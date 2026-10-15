<?php

use App\Models\Profile;
use App\Models\Skill;
use App\Models\WorkExperience;
use Inertia\Testing\AssertableInertia as Assert;

it('requires company and position', function () {
    $this->post('/experiences', [
        'company' => '',
        'position' => '',
    ])->assertSessionHasErrors(['company', 'position']);

    expect(WorkExperience::query()->count())->toBe(0);
});

it('rejects a month without a year', function () {
    $this->post('/experiences', [
        'company' => 'Acme',
        'position' => 'Analista',
        'start_month' => 6,
    ])->assertSessionHasErrors('start_year');

    expect(WorkExperience::query()->count())->toBe(0);
});

it('accepts year-only dates', function () {
    $this->post('/experiences', [
        'company' => 'Acme',
        'position' => 'Analista',
        'start_year' => 2022,
    ])->assertRedirect(route('experiences.index'));

    $this->assertDatabaseHas('work_experiences', [
        'company' => 'Acme',
        'start_year' => 2022,
        'start_month' => null,
    ]);
});

it('blocks an end date before the start date', function () {
    $this->post('/experiences', [
        'company' => 'Acme',
        'position' => 'Analista',
        'start_year' => 2022,
        'start_month' => 3,
        'end_year' => 2021,
        'end_month' => 12,
    ])->assertSessionHasErrors('end_year');

    expect(WorkExperience::query()->count())->toBe(0);
});

it('treats a year-only end date as december for validation', function () {
    $this->post('/experiences', [
        'company' => 'Acme',
        'position' => 'Analista',
        'start_year' => 2022,
        'start_month' => 6,
        'end_year' => 2022,
    ])->assertRedirect(route('experiences.index'));

    $this->assertDatabaseHas('work_experiences', [
        'company' => 'Acme',
        'start_month' => 6,
        'end_year' => 2022,
        'end_month' => null,
    ]);
});

it('shows experiences ordered from most recent to oldest', function () {
    WorkExperience::factory()->create(['company' => 'Antigua', 'start_year' => 2015, 'start_month' => 5, 'end_year' => 2017, 'end_month' => 2]);
    WorkExperience::factory()->create(['company' => 'Reciente', 'start_year' => 2023, 'start_month' => 8, 'end_year' => null, 'end_month' => null, 'is_current' => true]);
    WorkExperience::factory()->create(['company' => 'SoloAño', 'start_year' => 2021, 'start_month' => null, 'end_year' => null, 'end_month' => null, 'is_current' => false]);
    WorkExperience::factory()->create(['company' => 'SinFecha', 'start_year' => null, 'start_month' => null, 'end_year' => null, 'end_month' => null]);

    $this->get('/experiences')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Experiences/Index')
            ->where('records.0.company', 'Reciente')
            ->where('records.1.company', 'SoloAño')
            ->where('records.2.company', 'Antigua')
            ->where('records.3.company', 'SinFecha'));
});

it('marks an experience as current and clears the flag when an end date is set', function () {
    $this->post('/experiences', [
        'company' => 'Acme',
        'position' => 'Analista',
        'start_year' => 2024,
        'is_current' => true,
    ])->assertRedirect(route('experiences.index'));

    $experience = WorkExperience::query()->sole();
    expect($experience->is_current)->toBeTrue();
    expect($experience->end_year)->toBeNull();

    $this->put("/experiences/{$experience->id}", [
        'company' => 'Acme',
        'position' => 'Analista',
        'start_year' => 2024,
        'end_year' => 2025,
        'is_current' => true,
    ])->assertRedirect(route('experiences.index'));

    $experience->refresh();
    expect($experience->is_current)->toBeFalse();
    expect($experience->end_year)->toBe(2025);
});

it('considers the experience current again when the end date is removed', function () {
    $experience = WorkExperience::factory()->create([
        'start_year' => 2020,
        'end_year' => 2022,
        'is_current' => false,
    ]);

    $this->put("/experiences/{$experience->id}", [
        'company' => $experience->company,
        'position' => $experience->position,
        'start_year' => 2020,
    ])->assertRedirect(route('experiences.index'));

    $experience->refresh();
    expect($experience->is_current)->toBeTrue();
    expect($experience->end_year)->toBeNull();
});

it('syncs associated skills', function () {
    $php = Skill::factory()->create(['name' => 'PHP', 'category' => 'tecnologia']);
    $sql = Skill::factory()->create(['name' => 'SQL', 'category' => 'tecnologia']);

    $this->post('/experiences', [
        'company' => 'Acme',
        'position' => 'Analista',
        'skill_ids' => [$php->id, $sql->id],
    ])->assertRedirect(route('experiences.index'));

    $experience = WorkExperience::query()->sole();
    expect($experience->skills()->pluck('skills.id')->sort()->values()->all())
        ->toBe(collect([$php->id, $sql->id])->sort()->values()->all());

    $this->put("/experiences/{$experience->id}", [
        'company' => 'Acme',
        'position' => 'Analista',
        'skill_ids' => [$php->id],
    ])->assertRedirect(route('experiences.index'));

    expect($experience->refresh()->skills()->pluck('skills.id')->all())->toBe([$php->id]);
});

it('deletes an experience and its links but keeps the skills catalog', function () {
    $skill = Skill::factory()->create(['name' => 'PHP', 'category' => 'tecnologia']);
    $experience = WorkExperience::factory()->create();
    $experience->skills()->attach($skill);

    $this->delete("/experiences/{$experience->id}")
        ->assertRedirect(route('experiences.index'));

    $this->assertDatabaseMissing('work_experiences', ['id' => $experience->id]);
    $this->assertDatabaseMissing('skill_work_experience', ['work_experience_id' => $experience->id]);

    expect(Skill::query()->whereKey($skill->id)->exists())->toBeTrue();
    expect(Profile::query()->count())->toBe(1);
});
