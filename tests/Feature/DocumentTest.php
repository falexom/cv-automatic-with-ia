<?php

use App\Models\Certification;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

it('stores an uploaded PDF byte-identical and lists it', function () {
    Storage::fake('local');

    $contents = storedPdfContent();

    $this->post('/documents', [
        'file' => pdfUpload('Certificación AWS.pdf'),
        'title' => 'Certificación AWS',
    ])->assertRedirect(route('documents.index'));

    $document = Document::query()->sole();
    $checksum = hash('sha256', $contents);

    expect($document->original_filename)->toBe('Certificación AWS.pdf');
    expect($document->size_bytes)->toBe(strlen($contents));
    expect($document->checksum)->toBe($checksum);
    expect($document->is_available)->toBeTrue();

    Storage::disk('local')->assertExists("documents/{$checksum}.pdf");
    expect(Storage::disk('local')->get("documents/{$checksum}.pdf"))->toBe($contents);

    $this->get('/documents')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->where('documents.0.display_name', 'Certificación AWS')
            ->where('documents.0.original_filename', 'Certificación AWS.pdf')
            ->where('documents.0.is_available', true));
});

it('rejects a missing file', function () {
    $this->post('/documents', [])
        ->assertSessionHasErrors('file');

    expect(Document::query()->count())->toBe(0);
});

it('rejects a file that is not a PDF', function () {
    Storage::fake('local');

    $this->post('/documents', [
        'file' => UploadedFile::fake()->createWithContent('documento.pdf', 'esto no es un PDF'),
    ])->assertSessionHasErrors('file');

    expect(Document::query()->count())->toBe(0);
    expect(Storage::disk('local')->allFiles('documents'))->toBe([]);
});

it('rejects a truncated PDF without the EOF marker', function () {
    Storage::fake('local');

    $this->post('/documents', [
        'file' => UploadedFile::fake()->createWithContent('cortado.pdf', "%PDF-1.4\n1 0 obj\n<< >>\n"),
    ])->assertSessionHasErrors('file');

    expect(Document::query()->count())->toBe(0);
});

it('rejects files over the 20 MB limit', function () {
    Storage::fake('local');

    $this->post('/documents', [
        'file' => UploadedFile::fake()->create('grande.pdf', 20481, 'application/pdf'),
    ])->assertSessionHasErrors('file');

    expect(Document::query()->count())->toBe(0);
});

it('reuses the stored original when the same content is uploaded again', function () {
    Storage::fake('local');

    $this->post('/documents', ['file' => pdfUpload('primera.pdf')])
        ->assertRedirect(route('documents.index'));

    $this->post('/documents', ['file' => pdfUpload('segunda.pdf')])
        ->assertRedirect(route('documents.index'))
        ->assertSessionHas('toast', fn (mixed $toast): bool => is_array($toast)
            && str_contains((string) $toast['message'], 'reutilizó'));

    expect(Document::query()->count())->toBe(1);
    expect(Storage::disk('local')->allFiles('documents'))->toHaveCount(1);
    expect(Document::query()->sole()->original_filename)->toBe('primera.pdf');
});

it('does not persist anything when a PDF is rejected', function () {
    Storage::fake('local');

    $this->post('/documents', [
        'file' => UploadedFile::fake()->createWithContent('falso.pdf', 'no soy un pdf'),
    ])->assertSessionHasErrors('file');

    expect(Document::query()->count())->toBe(0);
    expect(Storage::disk('local')->allFiles('documents'))->toBe([]);
});

it('updates only the document metadata', function () {
    Storage::fake('local');

    $document = makeStoredDocument();

    $this->put("/documents/{$document->id}", [
        'title' => 'Título nuevo',
        'description' => 'Descripción del certificado',
    ])->assertRedirect(route('documents.index'));

    $document->refresh();

    expect($document->title)->toBe('Título nuevo');
    expect($document->description)->toBe('Descripción del certificado');
    expect($document->checksum)->toBe(hash('sha256', storedPdfContent()));
    expect($document->original_filename)->toBe('certificado.pdf');
    Storage::disk('local')->assertExists($document->path());
});

it('filters documents by text, state and entity', function () {
    Storage::fake('local');

    $certification = Certification::factory()->create(['name' => 'Curso de Laravel']);
    $linked = Document::factory()->create(['title' => 'Certificado Laravel']);
    $linked->certifications()->attach($certification);
    Document::factory()->create(['title' => 'Diploma', 'original_filename' => 'diploma.pdf']);

    $this->get('/documents?q=Laravel')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->has('documents', 1)
            ->where('documents.0.id', $linked->id)
            ->where('filters.q', 'Laravel'));

    $this->get('/documents?status=unlinked')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('documents', 1)
            ->where('documents.0.title', 'Diploma'));

    $this->get('/documents?status=linked')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('documents', 1)
            ->where('documents.0.id', $linked->id));

    $this->get("/documents?entity_type=certification&entity_id={$certification->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('documents', 1)
            ->where('documents.0.id', $linked->id));
});

it('ignores invalid filter values', function () {
    Storage::fake('local');

    Document::factory()->create();

    $this->get('/documents?status=whatever&entity_type=project&entity_id=1')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('documents', 1)
            ->where('filters.status', 'all')
            ->where('filters.entity_type', ''));
});
