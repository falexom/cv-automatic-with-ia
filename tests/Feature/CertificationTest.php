<?php

use App\Models\Certification;
use App\Models\Skill;
use Inertia\Testing\AssertableInertia as Assert;

it('requires only the name', function () {
    $this->post('/certifications', [
        'name' => '',
    ])->assertSessionHasErrors('name');

    $this->post('/certifications', [
        'name' => 'Scrum Fundamentals',
    ])->assertRedirect(route('certifications.index'));

    $this->assertDatabaseHas('certifications', [
        'name' => 'Scrum Fundamentals',
        'institution' => null,
        'date_year' => null,
    ]);
});

it('allows saving without a date', function () {
    $this->post('/certifications', [
        'name' => 'Certificación sin fecha',
        'institution' => 'Institución X',
    ])->assertRedirect(route('certifications.index'));

    $certification = Certification::query()->sole();
    expect($certification->date_year)->toBeNull();
    expect($certification->date_month)->toBeNull();
});

it('validates the validation URL format', function () {
    $this->post('/certifications', [
        'name' => 'Curso con URL',
        'validation_url' => 'no-es-una-url',
    ])->assertSessionHasErrors('validation_url');

    expect(Certification::query()->count())->toBe(0);
});

it('rejects a month without a year', function () {
    $this->post('/certifications', [
        'name' => 'Curso con mes',
        'date_month' => 5,
    ])->assertSessionHasErrors('date_year');
});

it('warns about a duplicate name and institution until explicitly confirmed', function () {
    Certification::factory()->create([
        'name' => 'Curso de Laravel',
        'institution' => 'Laravel Academy',
    ]);

    $this->post('/certifications', [
        'name' => '  curso de laravel ',
        'institution' => 'laravel academy',
    ])
        ->assertRedirect()
        ->assertSessionHas('duplicate_warning');

    expect(Certification::query()->count())->toBe(1);

    $this->post('/certifications', [
        'name' => '  curso de laravel ',
        'institution' => 'laravel academy',
        'confirm_duplicate' => true,
    ])->assertRedirect(route('certifications.index'));

    expect(Certification::query()->count())->toBe(2);
});

it('does not warn when the name or institution differ', function () {
    Certification::factory()->create([
        'name' => 'Curso de Laravel',
        'institution' => 'Laravel Academy',
    ]);

    $this->post('/certifications', [
        'name' => 'Curso de Laravel Avanzado',
        'institution' => 'Laravel Academy',
    ])->assertRedirect(route('certifications.index'));

    expect(Certification::query()->count())->toBe(2);
});

it('syncs skills and lists certifications ordered by date', function () {
    $php = Skill::factory()->create(['name' => 'PHP', 'category' => 'tecnologia']);

    Certification::factory()->create([
        'name' => 'Antigua',
        'date_year' => 2019,
        'date_month' => 4,
    ]);
    Certification::factory()->create([
        'name' => 'Reciente',
        'date_year' => 2024,
        'date_month' => null,
    ]);
    Certification::factory()->create([
        'name' => 'SinFecha',
        'date_year' => null,
        'date_month' => null,
    ]);

    $this->post('/certifications', [
        'name' => 'Con habilidad',
        'skill_ids' => [$php->id],
    ])->assertRedirect(route('certifications.index'));

    expect(Certification::query()->where('name', 'Con habilidad')->sole()->skills()->pluck('skills.id')->all())
        ->toBe([$php->id]);

    $this->get('/certifications')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Certifications/Index')
            ->where('records.0.name', 'Reciente')
            ->where('records.1.name', 'Antigua')
            ->where('records.2.name', 'Con habilidad')
            ->where('records.3.name', 'SinFecha'));
});

it('updates a certification keeping its own duplicate out of the check', function () {
    $certification = Certification::factory()->create([
        'name' => 'Curso de Laravel',
        'institution' => 'Laravel Academy',
    ]);

    $this->put("/certifications/{$certification->id}", [
        'name' => 'Curso de Laravel',
        'institution' => 'Laravel Academy',
        'duration' => '60 horas',
    ])->assertRedirect(route('certifications.index'));

    $this->assertDatabaseHas('certifications', [
        'id' => $certification->id,
        'duration' => '60 horas',
    ]);
});

it('deletes a certification and its links but keeps the skills', function () {
    $skill = Skill::factory()->create(['name' => 'PHP', 'category' => 'tecnologia']);
    $certification = Certification::factory()->create();
    $certification->skills()->attach($skill);

    $this->delete("/certifications/{$certification->id}")
        ->assertRedirect(route('certifications.index'));

    $this->assertDatabaseMissing('certifications', ['id' => $certification->id]);
    $this->assertDatabaseMissing('skill_certification', ['certification_id' => $certification->id]);
    expect(Skill::query()->whereKey($skill->id)->exists())->toBeTrue();
});
