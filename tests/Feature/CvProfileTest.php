<?php

use App\Http\Controllers\CvProfileController;
use App\Models\CvProfile;
use App\Models\Profile;
use App\Models\WorkExperience;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

it('shows an empty index when there are no profiles', function () {
    $this->get('/profiles')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profiles/Index')
            ->where('profiles', [])
            ->where('can_create_initial_profiles', true));
});

it('lists active and inactive profiles ordered by name', function () {
    CvProfile::factory()->create(['name' => 'Programador', 'is_active' => true]);
    CvProfile::factory()->inactive()->create(['name' => 'Analista']);

    $this->get('/profiles')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profiles/Index')
            ->has('profiles', 2)
            ->where('profiles.0.name', 'Analista')
            ->where('profiles.0.is_active', false)
            ->where('profiles.1.name', 'Programador')
            ->where('profiles.1.is_active', true)
            ->where('can_create_initial_profiles', false));
});

it('lists every profile when all are inactive', function () {
    CvProfile::factory()->inactive()->create(['name' => 'Analista']);
    CvProfile::factory()->inactive()->create(['name' => 'Programador']);

    $this->get('/profiles')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('profiles', 2)
            ->where('can_create_initial_profiles', false));
});

it('creates a profile with its own data and active by default', function () {
    $this->post('/profiles', [
        'name' => 'Analista',
        'professional_title' => 'Analista de Sistemas',
        'summary' => 'Resumen propio con acentos: estadística.',
        'is_active' => false,
    ])->assertRedirect(route('profiles.show', CvProfile::query()->first()));

    $profile = CvProfile::query()->firstOrFail();

    expect($profile->name)->toBe('Analista')
        ->and($profile->normalized_name)->toBe('analista')
        ->and($profile->professional_title)->toBe('Analista de Sistemas')
        ->and($profile->summary)->toBe('Resumen propio con acentos: estadística.')
        ->and($profile->is_active)->toBeTrue();
});

it('rejects an empty or whitespace-only name', function () {
    $this->post('/profiles', ['name' => ''])->assertSessionHasErrors('name');
    $this->post('/profiles', ['name' => '    '])->assertSessionHasErrors('name');

    expect(CvProfile::query()->count())->toBe(0);
});

it('rejects a duplicate normalized name including inactive profiles', function () {
    CvProfile::factory()->inactive()->create(['name' => 'Analista']);

    $this->post('/profiles', ['name' => '  ANALISTA  '])
        ->assertSessionHasErrors('name');

    expect(CvProfile::query()->count())->toBe(1);
});

it('allows renaming a profile to the same normalized name', function () {
    $profile = CvProfile::factory()->create(['name' => 'Analista']);

    $this->put("/profiles/{$profile->id}", ['name' => ' analista '])
        ->assertRedirect(route('profiles.show', $profile));

    expect($profile->fresh()->name)->toBe('analista');
});

it('rejects renaming a profile to another profile name', function () {
    CvProfile::factory()->create(['name' => 'Analista']);
    $profile = CvProfile::factory()->create(['name' => 'Programador']);

    $this->put("/profiles/{$profile->id}", ['name' => 'ANALISTA'])
        ->assertSessionHasErrors('name');

    expect($profile->fresh()->name)->toBe('Programador');
});

it('keeps the master profile untouched when own profile data changes', function () {
    $master = Profile::current();
    $master->update([
        'professional_title' => 'Título del Maestro',
        'summary' => 'Resumen del Maestro',
    ]);

    $profile = CvProfile::factory()->create(['name' => 'Analista']);

    $this->put("/profiles/{$profile->id}", [
        'name' => 'Analista',
        'professional_title' => 'Título propio',
        'summary' => 'Resumen propio',
    ])->assertRedirect();

    expect($master->fresh()->professional_title)->toBe('Título del Maestro')
        ->and($master->fresh()->summary)->toBe('Resumen del Maestro')
        ->and($profile->fresh()->professional_title)->toBe('Título propio');
});

it('accepts a profile without title or summary', function () {
    $this->post('/profiles', [
        'name' => 'Analista',
        'professional_title' => '',
        'summary' => '   ',
    ])->assertRedirect();

    $profile = CvProfile::query()->firstOrFail();

    expect($profile->professional_title)->toBeNull()
        ->and($profile->summary)->toBeNull();
});

it('shows a profile and returns 404 for a missing one', function () {
    $profile = CvProfile::factory()->create([
        'name' => 'Analista',
        'professional_title' => 'Analista de Sistemas',
        'summary' => 'Resumen propio.',
    ]);

    $this->get("/profiles/{$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profiles/Show')
            ->where('profile.id', $profile->id)
            ->where('profile.name', 'Analista')
            ->where('profile.professional_title', 'Analista de Sistemas')
            ->where('profile.summary', 'Resumen propio.'));

    $this->get('/profiles/999999')->assertNotFound();
});

it('deactivates and reactivates a profile preserving data and associations', function () {
    $experience = WorkExperience::factory()->create();
    $profile = CvProfile::factory()->create(['name' => 'Analista']);

    $this->put("/profiles/{$profile->id}", [
        'name' => 'Analista',
        'professional_title' => 'Analista de Sistemas',
        'work_experiences' => [['id' => $experience->id, 'relevance' => 'alta']],
    ])->assertRedirect();

    $this->post("/profiles/{$profile->id}/deactivate")
        ->assertRedirect(route('profiles.index'));

    expect($profile->fresh()->is_active)->toBeFalse()
        ->and($profile->workExperiences()->count())->toBe(1);

    $this->get('/profiles')
        ->assertInertia(fn (Assert $page) => $page
            ->where('profiles.0.name', 'Analista')
            ->where('profiles.0.is_active', false));

    $this->get("/profiles/{$profile->id}/edit")->assertOk();
    $this->get("/profiles/{$profile->id}")->assertOk();

    $this->post("/profiles/{$profile->id}/activate")
        ->assertRedirect(route('profiles.index'));

    expect($profile->fresh()->is_active)->toBeTrue()
        ->and($profile->workExperiences()->count())->toBe(1);
});

it('persists accented names and data between requests', function () {
    $this->post('/profiles', [
        'name' => 'Análisis de Datos',
        'professional_title' => 'Especialista en estadística',
        'summary' => 'Ñandú, ágil y diseñado.',
    ])->assertRedirect();

    $profile = CvProfile::query()->firstOrFail();

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.name', 'Análisis de Datos')
            ->where('profile.professional_title', 'Especialista en estadística')
            ->where('profile.summary', 'Ñandú, ágil y diseñado.'));
});

it('returns inherited personal data without master title or summary', function () {
    $master = Profile::current();
    $master->update([
        'full_name' => 'Ada Lovelace',
        'professional_title' => 'Título del Maestro',
        'phone' => '+54 11 5555 1234',
        'email' => 'ada@example.com',
        'location' => 'Buenos Aires',
        'linkedin_url' => 'https://www.linkedin.com/in/ada',
        'github_url' => 'https://github.com/ada',
        'website_url' => 'https://ada.example.com',
        'summary' => 'Resumen del Maestro',
    ]);
    $master->links()->create(['label' => 'Blog', 'url' => 'https://blog.example.com']);

    $profile = CvProfile::factory()->create(['name' => 'Analista']);

    $this->get("/profiles/{$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('personal_data.full_name', 'Ada Lovelace')
            ->where('personal_data.phone', '+54 11 5555 1234')
            ->where('personal_data.email', 'ada@example.com')
            ->where('personal_data.location', 'Buenos Aires')
            ->where('personal_data.linkedin_url', 'https://www.linkedin.com/in/ada')
            ->where('personal_data.github_url', 'https://github.com/ada')
            ->where('personal_data.website_url', 'https://ada.example.com')
            ->where('personal_data.links.0.label', 'Blog')
            ->where('personal_data.has_data', true)
            ->missing('personal_data.professional_title')
            ->missing('personal_data.summary'));
});

it('reports no inherited data when the master profile is empty', function () {
    Profile::current();
    $profile = CvProfile::factory()->create();

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('personal_data.has_data', false)
            ->where('personal_data.full_name', null));
});

it('uses the own title and never falls back to the master title', function () {
    $master = Profile::current();
    $master->update(['professional_title' => 'Título del Maestro']);

    $withoutTitle = CvProfile::factory()->create([
        'name' => 'Analista',
        'professional_title' => null,
    ]);
    $withTitle = CvProfile::factory()->create([
        'name' => 'Programador',
        'professional_title' => 'Título propio',
    ]);

    $this->get("/profiles/{$withoutTitle->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.professional_title', null));

    $this->get("/profiles/{$withTitle->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.professional_title', 'Título propio'));

    $master->update(['professional_title' => 'Título modificado del Maestro']);

    $this->get("/profiles/{$withTitle->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.professional_title', 'Título propio'));
});

it('reflects master personal data changes without synchronization', function () {
    $master = Profile::current();
    $master->update(['email' => 'viejo@example.com']);

    $profile = CvProfile::factory()->create();

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('personal_data.email', 'viejo@example.com'));

    $master->update(['email' => 'nuevo@example.com']);

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('personal_data.email', 'nuevo@example.com'));

    $master->update(['email' => null]);

    $this->get("/profiles/{$profile->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('personal_data.email', null));
});

it('allows consulting inactive profiles', function () {
    $profile = CvProfile::factory()->inactive()->create();

    $this->get("/profiles/{$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.is_active', false));
});

it('does not expose inherited fields as editable profile data', function () {
    $profile = CvProfile::factory()->create();

    $this->get("/profiles/{$profile->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profiles/Edit')
            ->where('profile.name', $profile->name)
            ->missing('profile.email')
            ->missing('profile.full_name')
            ->missing('profile.professional_title_master')
            ->has('options')
            ->has('selections'));
});

it('does not expose profile deletion', function () {
    $profile = CvProfile::factory()->create();

    expect(Route::has('profiles.destroy'))->toBeFalse()
        ->and(method_exists(CvProfileController::class, 'destroy'))->toBeFalse();

    $deleteRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'profiles')
            && in_array('DELETE', $route->methods(), true));

    expect($deleteRoutes)->toBeEmpty();

    $this->delete("/profiles/{$profile->id}")->assertStatus(405);

    expect(CvProfile::query()->count())->toBe(1);
});
