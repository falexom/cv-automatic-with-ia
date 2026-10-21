<?php

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        $this->withoutVite();
    })
    ->in('Feature');

/**
 * Uploaded PDF with valid content (header + %%EOF trailer) for the
 * documents feature tests.
 */
function pdfUpload(string $name = 'certificado.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, storedPdfContent());
}

function storedPdfContent(): string
{
    return (string) file_get_contents(base_path('tests/Fixtures/minimal.pdf'));
}

/**
 * Document row whose checksum matches the fixture content, with the
 * file already present on the fake local disk.
 */
function makeStoredDocument(string $filename = 'certificado.pdf', ?string $storedContents = null): Document
{
    $contents = storedPdfContent();

    $document = Document::factory()->create([
        'original_filename' => $filename,
        'size_bytes' => strlen($contents),
        'checksum' => hash('sha256', $contents),
    ]);

    Storage::disk('local')->put($document->path(), $storedContents ?? $contents);

    return $document;
}
