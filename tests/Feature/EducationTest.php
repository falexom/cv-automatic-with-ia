<?php

use App\Models\EducationEntry;
use Inertia\Testing\AssertableInertia as Assert;

it('requires institution and program', function () {
    $this->post('/education', [
        'institution' => '',
        'program' => '',
    ])->assertSessionHasErrors(['institution', 'program']);

    expect(EducationEntry::query()->count())->toBe(0);
});

it('rejects a month without a year', function () {
    $this->post('/education', [
        'institution' => 'Universidad Nacional',
        'program' => 'Ingeniería en Sistemas',
        'end_month' => 6,
    ])->assertSessionHasErrors('end_year');
});

it('blocks an end date before the start date', function () {
    $this->post('/education', [
        'institution' => 'Universidad Nacional',
        'program' => 'Ingeniería en Sistemas',
        'start_year' => 2018,
        'end_year' => 2016,
    ])->assertSessionHasErrors('end_year');

    expect(EducationEntry::query()->count())->toBe(0);
});

it('stores education in progress without an end date', function () {
    $this->post('/education', [
        'institution' => 'Universidad Nacional',
        'program' => 'Maestría en Sistemas',
        'start_year' => 2024,
        'is_in_progress' => true,
    ])->assertRedirect(route('education.index'));

    $entry = EducationEntry::query()->sole();
    expect($entry->is_in_progress)->toBeTrue();
    expect($entry->end_year)->toBeNull();
});

it('clears and restores the in-progress flag with the end date', function () {
    $entry = EducationEntry::factory()->inProgress()->create([
        'start_year' => 2024,
    ]);

    $this->put("/education/{$entry->id}", [
        'institution' => $entry->institution,
        'program' => $entry->program,
        'start_year' => 2024,
        'end_year' => 2026,
        'is_in_progress' => true,
    ])->assertRedirect(route('education.index'));

    expect($entry->refresh()->is_in_progress)->toBeFalse();
    expect($entry->end_year)->toBe(2026);

    $this->put("/education/{$entry->id}", [
        'institution' => $entry->institution,
        'program' => $entry->program,
        'start_year' => 2024,
    ])->assertRedirect(route('education.index'));

    expect($entry->refresh()->is_in_progress)->toBeTrue();
    expect($entry->end_year)->toBeNull();
});

it('shows education ordered from most recent to oldest', function () {
    EducationEntry::factory()->create(['institution' => 'Antigua', 'start_year' => 2010, 'end_year' => 2015]);
    EducationEntry::factory()->create(['institution' => 'Reciente', 'start_year' => 2020, 'start_month' => 8, 'end_year' => 2024]);
    EducationEntry::factory()->create(['institution' => 'SinFecha', 'start_year' => null, 'start_month' => null, 'end_year' => null]);

    $this->get('/education')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Education/Index')
            ->where('records.0.institution', 'Reciente')
            ->where('records.1.institution', 'Antigua')
            ->where('records.2.institution', 'SinFecha'));
});

it('deletes an education entry', function () {
    $entry = EducationEntry::factory()->create();

    $this->delete("/education/{$entry->id}")
        ->assertRedirect(route('education.index'));

    $this->assertDatabaseMissing('education_entries', ['id' => $entry->id]);
});
