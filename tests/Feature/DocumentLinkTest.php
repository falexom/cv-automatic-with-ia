<?php

use App\Models\Certification;
use App\Models\Document;
use App\Models\EducationEntry;
use App\Models\WorkExperience;
use Illuminate\Support\Facades\Storage;

it('associates a document to a certification from the repository', function () {
    Storage::fake('local');

    $document = Document::factory()->create();
    $certification = Certification::factory()->create(['name' => 'Curso de Laravel']);

    $this->post("/documents/{$document->id}/links", [
        'linkable_type' => 'certification',
        'linkable_id' => $certification->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('document_certification', [
        'document_id' => $document->id,
        'certification_id' => $certification->id,
    ]);
});

it('uploads a PDF from a certification and links it in one flow', function () {
    Storage::fake('local');

    $certification = Certification::factory()->create();

    $this->post('/documents', [
        'file' => pdfUpload('certificado-laravel.pdf'),
        'linkable_type' => 'certification',
        'linkable_id' => $certification->id,
    ])->assertRedirect(route('certifications.edit', $certification));

    $document = Document::query()->sole();

    $this->assertDatabaseHas('document_certification', [
        'document_id' => $document->id,
        'certification_id' => $certification->id,
    ]);

    $this->get(route('certifications.edit', $certification))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Certifications/Edit')
            ->where('documents.0.id', $document->id));
});

it('rejects a duplicate association to the same entity', function () {
    Storage::fake('local');

    $document = Document::factory()->create();
    $certification = Certification::factory()->create();

    $document->certifications()->attach($certification);

    $this->post("/documents/{$document->id}/links", [
        'linkable_type' => 'certification',
        'linkable_id' => $certification->id,
    ])->assertSessionHasErrors('linkable_id');
});

it('rejects entity types outside the allow-list', function () {
    Storage::fake('local');

    $document = Document::factory()->create();

    $this->post("/documents/{$document->id}/links", [
        'linkable_type' => 'project',
        'linkable_id' => 1,
    ])->assertSessionHasErrors('linkable_type');
});

it('rejects a link to an entity that does not exist', function () {
    Storage::fake('local');

    $document = Document::factory()->create();

    $this->post("/documents/{$document->id}/links", [
        'linkable_type' => 'certification',
        'linkable_id' => 999,
    ])->assertSessionHasErrors('linkable_id');

    $this->assertDatabaseCount('document_certification', 0);
});

it('unlinks a document keeping the original file and the entity', function () {
    Storage::fake('local');

    $document = makeStoredDocument();
    $certification = Certification::factory()->create();
    $document->certifications()->attach($certification);

    $this->delete("/documents/{$document->id}/links", [
        'linkable_type' => 'certification',
        'linkable_id' => $certification->id,
    ])->assertRedirect();

    $this->assertDatabaseMissing('document_certification', [
        'document_id' => $document->id,
    ]);
    expect(Document::query()->whereKey($document->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists($document->path());
});

it('keeps documents unassociated when their certification is deleted', function () {
    Storage::fake('local');

    $document = makeStoredDocument();
    $certification = Certification::factory()->create();
    $document->certifications()->attach($certification);

    $this->delete("/certifications/{$certification->id}")
        ->assertRedirect(route('certifications.index'));

    $this->assertDatabaseMissing('document_certification', [
        'document_id' => $document->id,
    ]);
    $this->assertDatabaseMissing('certifications', ['id' => $certification->id]);

    expect(Document::query()->whereKey($document->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists($document->path());
});

it('deletes a document with its links and file but keeps the certification', function () {
    Storage::fake('local');

    $document = makeStoredDocument();
    $certification = Certification::factory()->create();
    $document->certifications()->attach($certification);

    $this->delete("/documents/{$document->id}")
        ->assertRedirect(route('documents.index'));

    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    $this->assertDatabaseMissing('document_certification', [
        'document_id' => $document->id,
    ]);
    Storage::disk('local')->assertMissing($document->path());
    $this->assertDatabaseHas('certifications', ['id' => $certification->id]);
});

it('lists associations on the repository page', function () {
    Storage::fake('local');

    $document = Document::factory()->create(['title' => 'Certificado']);
    $certification = Certification::factory()->create(['name' => 'Curso de Laravel']);
    $document->certifications()->attach($certification);

    $this->get('/documents')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Documents/Index')
            ->where('documents.0.associations.0.type', 'certification')
            ->where('documents.0.associations.0.id', $certification->id)
            ->where('documents.0.associations.0.label', 'Curso de Laravel'));
});

it('associates a document to an education entry', function () {
    Storage::fake('local');

    $document = Document::factory()->create();
    $education = EducationEntry::factory()->create();

    $this->post("/documents/{$document->id}/links", [
        'linkable_type' => 'education',
        'linkable_id' => $education->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('document_education_entry', [
        'document_id' => $document->id,
        'education_entry_id' => $education->id,
    ]);
});

it('uploads a PDF from an education entry and links it in one flow', function () {
    Storage::fake('local');

    $education = EducationEntry::factory()->create();

    $this->post('/documents', [
        'file' => pdfUpload('titulo.pdf'),
        'linkable_type' => 'education',
        'linkable_id' => $education->id,
    ])->assertRedirect(route('education.edit', $education));

    $document = Document::query()->sole();

    $this->assertDatabaseHas('document_education_entry', [
        'document_id' => $document->id,
        'education_entry_id' => $education->id,
    ]);

    $this->get(route('education.edit', $education))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Education/Edit')
            ->where('documents.0.id', $document->id));
});

it('associates a document to a work experience', function () {
    Storage::fake('local');

    $document = Document::factory()->create();
    $experience = WorkExperience::factory()->create();

    $this->post("/documents/{$document->id}/links", [
        'linkable_type' => 'experience',
        'linkable_id' => $experience->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('document_work_experience', [
        'document_id' => $document->id,
        'work_experience_id' => $experience->id,
    ]);
});

it('uploads a PDF from an experience and links it in one flow', function () {
    Storage::fake('local');

    $experience = WorkExperience::factory()->create();

    $this->post('/documents', [
        'file' => pdfUpload('constancia.pdf'),
        'linkable_type' => 'experience',
        'linkable_id' => $experience->id,
    ])->assertRedirect(route('experiences.edit', $experience));

    $document = Document::query()->sole();

    $this->assertDatabaseHas('document_work_experience', [
        'document_id' => $document->id,
        'work_experience_id' => $experience->id,
    ]);

    $this->get(route('experiences.edit', $experience))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Experiences/Edit')
            ->where('documents.0.id', $document->id));
});

it('keeps documents unassociated when their education entry is deleted', function () {
    Storage::fake('local');

    $document = makeStoredDocument();
    $education = EducationEntry::factory()->create();
    $document->educationEntries()->attach($education);

    $this->delete("/education/{$education->id}")
        ->assertRedirect(route('education.index'));

    $this->assertDatabaseMissing('document_education_entry', [
        'document_id' => $document->id,
    ]);
    $this->assertDatabaseMissing('education_entries', ['id' => $education->id]);

    expect(Document::query()->whereKey($document->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists($document->path());
});

it('keeps documents unassociated when their experience is deleted', function () {
    Storage::fake('local');

    $document = makeStoredDocument();
    $experience = WorkExperience::factory()->create();
    $document->workExperiences()->attach($experience);

    $this->delete("/experiences/{$experience->id}")
        ->assertRedirect(route('experiences.index'));

    $this->assertDatabaseMissing('document_work_experience', [
        'document_id' => $document->id,
    ]);
    $this->assertDatabaseMissing('work_experiences', ['id' => $experience->id]);

    expect(Document::query()->whereKey($document->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists($document->path());
});

it('rejects a link when the target education entry does not exist', function () {
    Storage::fake('local');

    $document = Document::factory()->create();

    $this->post("/documents/{$document->id}/links", [
        'linkable_type' => 'education',
        'linkable_id' => 999,
    ])->assertSessionHasErrors('linkable_id');

    $this->assertDatabaseCount('document_education_entry', 0);
});
