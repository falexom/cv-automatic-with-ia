<?php

use App\Models\Profile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * FR-027 / SC-002: the whole profile survives between application
 * sessions. Rows are written through the HTTP layer and then re-read
 * with fresh queries, proving nothing lives only in request state.
 */
it('keeps the full profile in the database across sessions', function () {
    Storage::fake('local');

    $this->put('/profile', [
        'full_name' => 'Ada Lovelace',
        'professional_title' => 'Analista de Sistemas',
        'email' => 'ada@example.com',
        'summary' => 'Resumen con acentos: estadística.',
        'links' => [
            ['label' => 'Blog', 'url' => 'https://blog.example.com'],
        ],
    ])->assertRedirect();

    $this->post('/skills', ['name' => 'PHP', 'category' => 'tecnologia'])
        ->assertRedirect();

    $skillId = DB::table('skills')->where('name', 'PHP')->value('id');

    $this->post('/experiences', [
        'company' => 'Acme',
        'position' => 'Analista',
        'start_year' => 2020,
        'is_current' => true,
        'skill_ids' => [$skillId],
    ])->assertRedirect();

    $this->post('/education', [
        'institution' => 'Universidad Nacional',
        'program' => 'Ingeniería en Sistemas',
        'start_year' => 2015,
        'end_year' => 2020,
    ])->assertRedirect();

    $this->post('/certifications', [
        'name' => 'Curso de Laravel',
        'institution' => 'Laravel Academy',
        'date_year' => 2023,
        'skill_ids' => [$skillId],
    ])->assertRedirect();

    $certificationId = (int) DB::table('certifications')
        ->where('name', 'Curso de Laravel')
        ->value('id');

    $this->post('/documents', [
        'file' => pdfUpload('persistencia.pdf'),
        'linkable_type' => 'certification',
        'linkable_id' => $certificationId,
    ])->assertRedirect();

    $this->post('/projects', [
        'name' => 'Portal interno',
        'start_year' => 2021,
        'skill_ids' => [$skillId],
    ])->assertRedirect();

    // Simulate a fresh session: drop the in-memory model state and
    // re-read everything through independent queries and pages.
    DB::table('profiles')->get();

    expect(Profile::query()->count())->toBe(1);

    $this->assertDatabaseHas('profiles', ['full_name' => 'Ada Lovelace']);
    $this->assertDatabaseHas('profile_links', ['label' => 'Blog']);
    $this->assertDatabaseHas('skills', ['normalized_name' => 'php']);
    $this->assertDatabaseHas('work_experiences', ['company' => 'Acme', 'is_current' => 1]);
    $this->assertDatabaseHas('education_entries', ['institution' => 'Universidad Nacional']);
    $this->assertDatabaseHas('certifications', ['name' => 'Curso de Laravel']);
    $this->assertDatabaseHas('documents', ['original_filename' => 'persistencia.pdf']);
    $this->assertDatabaseHas('document_certification', ['certification_id' => $certificationId]);
    $this->assertDatabaseHas('projects', ['name' => 'Portal interno']);
    $this->assertDatabaseHas('skill_work_experience', ['skill_id' => $skillId]);
    $this->assertDatabaseHas('skill_certification', ['skill_id' => $skillId]);
    $this->assertDatabaseHas('skill_project', ['skill_id' => $skillId]);

    $experiences = DB::table('work_experiences')->join('skill_work_experience', 'work_experiences.id', '=', 'skill_work_experience.work_experience_id')->count();
    expect($experiences)->toBe(1);

    $this->get('/profile')->assertOk()->assertInertia(
        fn ($page) => $page->where('profile.full_name', 'Ada Lovelace'),
    );
    $this->get('/skills')->assertOk();
});
