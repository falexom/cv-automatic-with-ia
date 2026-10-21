<?php

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

it('verifies integrity and shows the viewer when the file is intact', function () {
    Storage::fake('local');

    $document = makeStoredDocument();

    $this->get("/documents/{$document->id}/viewer")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Viewer')
            ->where('available', true)
            ->where('document.display_name', 'certificado.pdf')
            ->where('fileUrl', route('documents.file', $document)));

    $document->refresh();

    expect($document->is_available)->toBeTrue();
    expect($document->last_verified_at)->not->toBeNull();
});

it('marks the document as unavailable when the file is missing', function () {
    Storage::fake('local');

    $document = Document::factory()->create([
        'checksum' => hash('sha256', 'contenido perdido'),
    ]);

    $this->get("/documents/{$document->id}/viewer")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Viewer')
            ->where('available', false)
            ->where('fileUrl', null));

    expect($document->refresh()->is_available)->toBeFalse();
});

it('marks the document as unavailable when the stored file was altered', function () {
    Storage::fake('local');

    $document = makeStoredDocument(storedContents: 'contenido alterado');

    $this->get("/documents/{$document->id}/viewer")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Viewer')
            ->where('available', false));

    expect($document->refresh()->is_available)->toBeFalse();
});

it('streams the original PDF inline with its filename', function () {
    Storage::fake('local');

    $document = makeStoredDocument('Certificación ágil.pdf');

    $response = $this->get("/documents/{$document->id}/file");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain('inline');
    expect($response->streamedContent())->toBe(storedPdfContent());
});

it('returns 404 when the stored file does not exist', function () {
    Storage::fake('local');

    $document = Document::factory()->create();

    $this->get("/documents/{$document->id}/file")->assertNotFound();
});

it('verifies the whole repository on demand and reports problems', function () {
    Storage::fake('local');

    $ok = makeStoredDocument();
    $missing = Document::factory()->create([
        'checksum' => hash('sha256', 'ausente'),
    ]);
    $altered = Document::factory()->create([
        'original_filename' => 'alterado.pdf',
        'checksum' => hash('sha256', 'contenido esperado'),
    ]);
    Storage::disk('local')->put($altered->path(), 'contenido distinto al esperado');

    $this->post('/documents/verify')
        ->assertRedirect()
        ->assertSessionHas('verification', fn (mixed $report): bool => is_array($report)
            && $report['checked'] === 3
            && $report['ok'] === 1
            && $report['unavailable'] === 2
            && count($report['problems']) === 2);

    expect($ok->refresh()->is_available)->toBeTrue();
    expect($ok->last_verified_at)->not->toBeNull();
    expect($missing->refresh()->is_available)->toBeFalse();
    expect($altered->refresh()->is_available)->toBeFalse();
});
