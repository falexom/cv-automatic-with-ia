<?php

use App\Models\Document;
use App\Support\CvPdfChrome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
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
 * Locate the full Chrome/Chromium binary used by Browsershot tests.
 */
function cvPdfChromePath(): string
{
    $resolution = app(CvPdfChrome::class)->resolve();

    if ($resolution->isAvailable() && $resolution->path !== null) {
        return $resolution->path;
    }

    throw new RuntimeException(
        $resolution->error ?? 'Configurá BROWSERSHOT_CHROME_PATH con la ruta de Google Chrome/Chromium.',
    );
}

function cvPdfSsrBundlePath(): string
{
    $root = dirname(__DIR__);

    foreach ([
        $root.'/bootstrap/ssr/ssr.js',
        $root.'/bootstrap/ssr/ssr.mjs',
    ] as $bundle) {
        if (is_file($bundle)) {
            return $bundle;
        }
    }

    throw new RuntimeException(
        'Ejecutá `pnpm run build:ssr` antes de las pruebas de exportación PDF.',
    );
}

function cvPdfSsrIsHealthy(): bool
{
    $context = stream_context_create([
        'http' => ['timeout' => 1],
    ]);

    $response = @file_get_contents('http://127.0.0.1:13714/health', false, $context);

    if (! is_string($response)) {
        return false;
    }

    $payload = json_decode($response, true);

    return is_array($payload) && ($payload['status'] ?? null) === 'OK';
}

/**
 * Reuse a healthy SSR server or start the generated bundle for the test run.
 */
function cvPdfStartSsr(): ?Process
{
    global $cvPdfSsrProcess;

    if (cvPdfSsrIsHealthy()) {
        return null;
    }

    $process = new Process(['node', cvPdfSsrBundlePath()], dirname(__DIR__), null, null, null);
    $process->start();

    $deadline = microtime(true) + 30;

    while (microtime(true) < $deadline) {
        if (cvPdfSsrIsHealthy()) {
            $cvPdfSsrProcess = $process;

            return $process;
        }

        if (! $process->isRunning()) {
            break;
        }

        usleep(250000);
    }

    $output = trim($process->getErrorOutput()."\n".$process->getOutput());
    $process->stop(1);

    throw new RuntimeException(
        'No se pudo iniciar el SSR de Inertia. '.$output,
    );
}

function cvPdfStopSsr(): void
{
    global $cvPdfSsrProcess;

    if ($cvPdfSsrProcess instanceof Process && $cvPdfSsrProcess->isRunning()) {
        $cvPdfSsrProcess->stop(1);
    }

    $cvPdfSsrProcess = null;
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
