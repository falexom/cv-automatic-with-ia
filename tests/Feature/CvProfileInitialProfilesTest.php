<?php

use App\Models\CvProfile;
use Inertia\Testing\AssertableInertia as Assert;

it('does not create profiles automatically from the migration or the index', function () {
    $this->get('/profiles')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('profiles', [])
            ->where('can_create_initial_profiles', true));

    expect(CvProfile::query()->count())->toBe(0);
});

it('creates the two initial profiles explicitly and without content', function () {
    $this->post('/profiles/initial')
        ->assertRedirect(route('profiles.index'))
        ->assertSessionHas('toast.type', 'success');

    $analista = CvProfile::query()->where('name', 'Analista')->firstOrFail();
    $programador = CvProfile::query()->where('name', 'Programador')->firstOrFail();

    expect($analista->professional_title)->toBe('Analista de Sistemas')
        ->and($analista->summary)->toBeNull()
        ->and($analista->is_active)->toBeTrue()
        ->and($programador->professional_title)->toBe('Programador / Desarrollador')
        ->and($programador->summary)->toBeNull()
        ->and($programador->is_active)->toBeTrue()
        ->and($analista->workExperiences()->count())->toBe(0)
        ->and($analista->skills()->count())->toBe(0)
        ->and(CvProfile::query()->count())->toBe(2);
});

it('is idempotent and never duplicates or overwrites existing profiles', function () {
    $this->post('/profiles/initial')->assertRedirect();

    $analista = CvProfile::query()->where('name', 'Analista')->firstOrFail();
    $analista->update(['name' => 'Analista Senior']);

    $this->post('/profiles/initial')
        ->assertRedirect(route('profiles.index'))
        ->assertSessionHas('toast.type', 'info')
        ->assertSessionHas('toast.message', 'Los perfiles iniciales solo se crean cuando no existe ningún perfil.');

    expect(CvProfile::query()->count())->toBe(2)
        ->and(CvProfile::query()->where('name', 'Analista')->exists())->toBeFalse()
        ->and(CvProfile::query()->where('name', 'Analista Senior')->exists())->toBeTrue()
        ->and(CvProfile::query()->where('name', 'Programador')->exists())->toBeTrue();
});

it('does not recreate a deactivated preset', function () {
    $this->post('/profiles/initial')->assertRedirect();

    $programador = CvProfile::query()->where('name', 'Programador')->firstOrFail();
    $this->post("/profiles/{$programador->id}/deactivate")->assertRedirect();

    $this->post('/profiles/initial')->assertRedirect();

    expect(CvProfile::query()->where('name', 'Programador')->count())->toBe(1)
        ->and($programador->fresh()->is_active)->toBeFalse()
        ->and(CvProfile::query()->count())->toBe(2);
});

it('does not create presets when a custom profile exists', function () {
    CvProfile::factory()->create(['name' => 'Perfil personalizado']);

    $this->post('/profiles/initial')
        ->assertRedirect(route('profiles.index'))
        ->assertSessionHas('toast.type', 'info');

    expect(CvProfile::query()->count())->toBe(1)
        ->and(CvProfile::query()->where('name', 'Analista')->exists())->toBeFalse()
        ->and(CvProfile::query()->where('name', 'Programador')->exists())->toBeFalse();
});

it('offers the initial profiles action only when no profile exists', function () {
    $this->get('/profiles')
        ->assertInertia(fn (Assert $page) => $page
            ->where('can_create_initial_profiles', true));

    CvProfile::factory()->create();

    $this->get('/profiles')
        ->assertInertia(fn (Assert $page) => $page
            ->where('can_create_initial_profiles', false));
});
