<?php

use App\Models\Profile;
use Inertia\Testing\AssertableInertia as Assert;

it('creates the singleton profile only once', function () {
    $this->get('/profile')->assertOk();
    $this->get('/profile')->assertOk();

    expect(Profile::query()->count())->toBe(1);
});

it('shows the profile edit page with the current data', function () {
    Profile::current()->update([
        'full_name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    $this->get('/profile')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Edit')
            ->where('profile.full_name', 'Ada Lovelace')
            ->where('profile.email', 'ada@example.com'));
});

it('persists all personal data, summary and links', function () {
    $this->put('/profile', [
        'full_name' => 'Ada Lovelace',
        'professional_title' => 'Analista de Sistemas',
        'phone' => '+54 11 5555 1234',
        'email' => 'ada@example.com',
        'location' => 'Buenos Aires',
        'linkedin_url' => 'https://www.linkedin.com/in/ada',
        'github_url' => 'https://github.com/ada',
        'website_url' => 'https://ada.example.com',
        'summary' => 'Resumen con acentos: estadística y análisis.',
        'links' => [
            ['label' => 'Blog', 'url' => 'https://blog.example.com'],
        ],
    ])->assertRedirect(route('profile.edit'));

    $this->assertDatabaseHas('profiles', [
        'full_name' => 'Ada Lovelace',
        'professional_title' => 'Analista de Sistemas',
        'email' => 'ada@example.com',
        'summary' => 'Resumen con acentos: estadística y análisis.',
    ]);

    $this->assertDatabaseHas('profile_links', [
        'label' => 'Blog',
        'url' => 'https://blog.example.com',
    ]);
});

it('requires the full name to save personal data', function () {
    $this->put('/profile', [
        'full_name' => '',
        'email' => 'ada@example.com',
    ])->assertSessionHasErrors('full_name');

    expect(Profile::query()->whereNotNull('email')->count())->toBe(0);
});

it('validates the email and link formats', function () {
    $this->put('/profile', [
        'full_name' => 'Ada Lovelace',
        'email' => 'no-es-un-correo',
        'linkedin_url' => 'no-es-una-url',
        'links' => [
            ['label' => 'Blog', 'url' => 'tampoco-es-url'],
        ],
    ])->assertSessionHasErrors(['email', 'linkedin_url', 'links.0.url']);

    expect(Profile::query()->whereNotNull('email')->count())->toBe(0);
});

it('replaces the links collection on save', function () {
    $profile = Profile::current();
    $profile->links()->createMany([
        ['label' => 'Viejo 1', 'url' => 'https://viejo1.example.com'],
        ['label' => 'Viejo 2', 'url' => 'https://viejo2.example.com'],
    ]);

    $this->put('/profile', [
        'full_name' => 'Ada Lovelace',
        'links' => [
            ['label' => 'Nuevo', 'url' => 'https://nuevo.example.com'],
        ],
    ])->assertRedirect(route('profile.edit'));

    expect($profile->links()->count())->toBe(1);
    $this->assertDatabaseHas('profile_links', ['label' => 'Nuevo']);
    $this->assertDatabaseMissing('profile_links', ['label' => 'Viejo 1']);
    $this->assertDatabaseMissing('profile_links', ['label' => 'Viejo 2']);
});
