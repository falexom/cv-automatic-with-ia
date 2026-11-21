<?php

use App\Models\Certification;
use App\Models\Document;
use App\Support\CertificationImport\CertificationImportAnalyzer;
use App\Support\CertificationImport\CertificationImportException;
use App\Support\CertificationImport\LocalOcrExtractor;
use App\Support\CertificationImport\PdfTextExtractor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

final class FakeOcrNativeExtractor implements PdfTextExtractor
{
    /**
     * @param  array<int, array{page: int, text: string}>  $pages
     */
    public function __construct(private array $pages) {}

    /**
     * @return array<int, array{page: int, text: string}>
     */
    public function extract(string $contents): array
    {
        return $this->pages;
    }
}

final class FakeOcrExtractor implements LocalOcrExtractor
{
    /**
     * @var array<int, array<int, int>>
     */
    public array $calls = [];

    /**
     * @param  Closure(array<int, int>): array<int, array{page: int, text: string}>  $callback
     * @param  array<int, string>  $languages
     */
    public function __construct(
        private Closure $callback,
        private array $languages = ['spa', 'eng'],
    ) {}

    /**
     * @param  array<int, int>  $pageNumbers
     * @return array<int, array{page: int, text: string}>
     */
    public function extract(string $contents, array $pageNumbers): array
    {
        $this->calls[] = $pageNumbers;

        return ($this->callback)($pageNumbers);
    }

    /**
     * @return array<int, string>
     */
    public function usedLanguages(): array
    {
        return $this->languages;
    }
}

final class FakeOcrAnalyzer implements CertificationImportAnalyzer
{
    /**
     * @var array<int, array{skills: array<int, array<string, mixed>>, pages: array<int, array<string, mixed>>}>
     */
    public array $calls = [];

    public function __construct(private Closure $callback) {}

    /**
     * @param  array<int, array{ref: string, name: string, category: string}>  $skills
     * @param  array<int, array{page: int, text: string, origin?: string}>  $pages
     * @return array<string, mixed>
     */
    public function analyze(array $skills, array $pages): array
    {
        $this->calls[] = ['skills' => $skills, 'pages' => $pages];

        return ($this->callback)($skills, $pages);
    }
}

/**
 * @param  array<int, array{page: int, text: string}>  $pages
 */
function bindOcrNativeExtractor(array $pages): void
{
    app()->bind(PdfTextExtractor::class, fn (): FakeOcrNativeExtractor => new FakeOcrNativeExtractor($pages));
}

function bindFakeOcrExtractor(Closure $callback): FakeOcrExtractor
{
    $fake = new FakeOcrExtractor($callback);
    app()->bind(LocalOcrExtractor::class, fn (): FakeOcrExtractor => $fake);

    return $fake;
}

function bindOcrAnalyzer(Closure $callback): FakeOcrAnalyzer
{
    $fake = new FakeOcrAnalyzer($callback);
    app()->bind(CertificationImportAnalyzer::class, fn (): FakeOcrAnalyzer => $fake);

    return $fake;
}

/**
 * @return array<int, array{page: int, text: string}>
 */
function ocrScannedPages(): array
{
    return [[
        'page' => 1,
        'text' => implode("\n", [
            'Certificado de finalización',
            'Curso de Laravel',
            'Academy',
            'Emitido en 2024',
            'Duración: 40 horas',
        ]),
    ]];
}

/**
 * @return array<string, mixed>
 */
function ocrProposal(): array
{
    return [
        'schema_version' => 1,
        'sufficiency' => 'sufficient',
        'sufficiency_note' => null,
        'fields' => [
            'name' => [
                'state' => 'found',
                'value' => 'Curso de Laravel',
                'support' => 'Curso de Laravel',
                'page' => 1,
                'reason' => null,
            ],
            'institution' => [
                'state' => 'found',
                'value' => 'Academy',
                'support' => 'Academy',
                'page' => 1,
                'reason' => null,
            ],
            'date' => [
                'state' => 'found',
                'year' => 2024,
                'month' => null,
                'support' => 'Emitido en 2024',
                'page' => 1,
                'reason' => null,
            ],
            'duration' => [
                'state' => 'found',
                'value' => '40 horas',
                'support' => 'Duración: 40 horas',
                'page' => 1,
                'reason' => null,
            ],
            'code' => [
                'state' => 'absent',
                'value' => null,
                'support' => null,
                'page' => null,
                'reason' => null,
            ],
            'validation_url' => [
                'state' => 'absent',
                'value' => null,
                'support' => null,
                'page' => null,
                'reason' => null,
            ],
            'category' => [
                'state' => 'absent',
                'value' => null,
                'support' => null,
                'page' => null,
                'reason' => null,
            ],
        ],
        'skills' => [],
        'ambiguities' => [],
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function ocrPrepare(array $overrides = []): array
{
    return test()->post('/certifications/import/prepare', [
        'file' => pdfUpload(),
        ...$overrides,
    ], ['Accept' => 'application/json'])->assertOk()->json();
}

/**
 * @param  array<string, mixed>  $preparation
 */
function ocrAnalyze(array $preparation): TestResponse
{
    return test()->postJson('/certifications/import/analyze', [
        'preparation_token' => $preparation['preparation_token'],
        'consent' => true,
        'pages' => $preparation['pages'],
    ]);
}

beforeEach(function () {
    Storage::fake('local');
});

it('falls back to local OCR for a scanned PDF and saves the unaltered original', function () {
    bindOcrNativeExtractor([['page' => 1, 'text' => '']]);
    $ocr = bindFakeOcrExtractor(fn (): array => ocrScannedPages());
    bindOcrAnalyzer(fn (): array => ocrProposal());

    $preparation = ocrPrepare();

    expect($preparation['status'])->toBe('ready');
    expect($preparation['ocr_used'])->toBeTrue();
    expect($preparation['ocr_languages'])->toBe(['spa', 'eng']);
    expect($preparation['pages'][0]['origin'])->toBe('ocr');
    expect($preparation['pages'][0]['text'])->toContain('Curso de Laravel');
    expect($preparation['preparation_token'])->toBeString();
    expect($ocr->calls)->toBe([[1]]);

    $analysis = ocrAnalyze($preparation)->assertOk()->json();

    $this->post('/certifications/import/confirm', [
        'name' => 'Curso de Laravel',
        'institution' => 'Academy',
        'date_year' => 2024,
        'duration' => '40 horas',
        'confirmation_id' => (string) Str::uuid(),
        'proposal_token' => $analysis['confirmation_token'],
        'file' => pdfUpload(),
    ], ['Accept' => 'application/json'])->assertOk()->assertJsonStructure(['redirect']);

    $document = Document::query()->sole();

    expect(Certification::query()->count())->toBe(1);
    expect($document->checksum)->toBe(hash('sha256', storedPdfContent()));
    Storage::disk('local')->assertExists($document->path());
    expect(Storage::disk('local')->get($document->path()))->toBe(storedPdfContent());
});

it('keeps an OCR value with a mismatched citation as an unverified suggestion', function () {
    bindOcrNativeExtractor([['page' => 1, 'text' => '']]);
    bindFakeOcrExtractor(fn (): array => ocrScannedPages());
    bindOcrAnalyzer(function (): array {
        $proposal = ocrProposal();
        $proposal['fields']['name']['support'] = 'Cita que no existe en el OCR';

        return $proposal;
    });

    $preparation = ocrPrepare();
    $analysis = ocrAnalyze($preparation)->assertOk()->json();

    expect($analysis['proposal']['fields']['name']['state'])->toBe('unverified');
    expect($analysis['proposal']['fields']['name']['value'])->toBe('Curso de Laravel');
    expect($analysis['proposal']['fields']['name']['support'])->toBeNull();
    expect($analysis['proposal']['fields']['name']['reason'])->toContain('cita');
    expect($analysis['proposal']['fields']['institution']['state'])->toBe('found');
    expect($analysis['confirmation_token'])->toBeString();

    expect(Certification::query()->count())->toBe(0);
    expect(Document::query()->count())->toBe(0);
});

it('requires explicit acceptance for an unverified category before saving', function () {
    bindOcrNativeExtractor([['page' => 1, 'text' => '']]);
    bindFakeOcrExtractor(fn (): array => ocrScannedPages());
    bindOcrAnalyzer(function (): array {
        $proposal = ocrProposal();
        $proposal['fields']['category'] = [
            'state' => 'found',
            'value' => 'Programación',
            'support' => 'Cita inexistente en el OCR',
            'page' => 1,
            'reason' => null,
        ];

        return $proposal;
    });

    $preparation = ocrPrepare();
    $analysis = ocrAnalyze($preparation)->assertOk()->json();

    expect($analysis['proposal']['fields']['category']['state'])->toBe('unverified');

    $payload = [
        'name' => 'Curso de Laravel',
        'institution' => 'Academy',
        'date_year' => 2024,
        'duration' => '40 horas',
        'category' => 'Programación',
        'confirmation_id' => (string) Str::uuid(),
        'proposal_token' => $analysis['confirmation_token'],
        'file' => pdfUpload(),
    ];

    $this->post('/certifications/import/confirm', $payload, ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('category');

    expect(Certification::query()->count())->toBe(0);

    $this->post('/certifications/import/confirm', [
        ...$payload,
        'accepted_category' => '1',
    ], ['Accept' => 'application/json'])->assertOk();

    expect(Certification::query()->sole()->category)->toBe('Programación');
});

it('does not run OCR when native text is sufficient', function () {
    bindOcrNativeExtractor([[
        'page' => 1,
        'text' => "Certificado de finalización\nCurso de Laravel\nAcademy\nEmitido en 2024\nDuración: 40 horas",
    ]]);
    $ocr = bindFakeOcrExtractor(fn (): array => []);

    $preparation = ocrPrepare();

    expect($preparation['status'])->toBe('ready');
    expect($preparation['ocr_used'])->toBeFalse();
    expect($preparation['pages'][0]['origin'])->toBe('native');
    expect($ocr->calls)->toBe([]);
});

it('OCRs only the mixed page that lacks native text', function () {
    bindOcrNativeExtractor([
        [
            'page' => 1,
            'text' => "Certificado de finalización\nCurso de Laravel\nAcademy",
        ],
        ['page' => 2, 'text' => ''],
    ]);
    $ocr = bindFakeOcrExtractor(fn (array $pageNumbers): array => [
        ['page' => 2, 'text' => "Contenido escaneado de la segunda página\nDuración: 40 horas"],
    ]);

    $preparation = ocrPrepare();

    expect($preparation['status'])->toBe('ready');
    expect($preparation['ocr_used'])->toBeTrue();
    expect($ocr->calls)->toBe([[2]]);
    expect($preparation['pages'][0]['origin'])->toBe('native');
    expect($preparation['pages'][0]['text'])->toContain('Curso de Laravel');
    expect($preparation['pages'][1]['origin'])->toBe('ocr');
    expect($preparation['pages'][1]['text'])->toContain('Contenido escaneado');
});

it('treats a blank page without images as blank instead of an OCR failure', function () {
    bindOcrNativeExtractor([
        [
            'page' => 1,
            'text' => "Certificado de finalización\nCurso de Laravel\nAcademy",
        ],
        ['page' => 2, 'text' => ''],
    ]);
    $ocr = bindFakeOcrExtractor(fn (): array => [
        ['page' => 2, 'text' => '', 'has_content' => false],
    ]);

    $preparation = ocrPrepare();

    expect($preparation['status'])->toBe('ready');
    expect($preparation['ocr_used'])->toBeFalse();
    expect($preparation['pages'][1]['origin'])->toBe('native');
    expect($preparation['pages'][1]['text'])->toBe('');
    expect($ocr->calls)->toBe([[2]]);
});

it('reports an OCR failure with the manual alternative and no persistence', function () {
    bindOcrNativeExtractor([['page' => 1, 'text' => '']]);
    $ocr = bindFakeOcrExtractor(fn (): array => throw new CertificationImportException(
        'ocr_unavailable',
        'No se encontró Tesseract para OCR local. Podés ingresar manualmente.',
        503,
    ));
    $analyzer = bindOcrAnalyzer(fn (): array => ocrProposal());

    $preparation = ocrPrepare();

    expect($preparation['status'])->toBe('insufficient');
    expect($preparation['preparation_token'])->toBeNull();
    expect($preparation['message'])->toContain('Tesseract');
    expect($preparation['ocr_used'])->toBeFalse();
    expect($preparation['pages'][0]['origin'])->toBe('native');
    expect($ocr->calls)->toBe([[1]]);
    expect($analyzer->calls)->toBe([]);
    expect(Certification::query()->count())->toBe(0);
    expect(Document::query()->count())->toBe(0);
});

it('uses a richer forced OCR text without duplicating the native page text', function () {
    bindOcrNativeExtractor([[
        'page' => 1,
        'text' => "Academy\nCurso de Laravel",
    ]]);
    $ocr = bindFakeOcrExtractor(fn (): array => [[
        'page' => 1,
        'text' => implode("\n", [
            'Academy',
            'Curso de Laravel',
            'Certificado de finalización',
            'Emitido en 2024',
            'Duración: 40 horas',
            'Código: ABC-123',
        ]),
    ]]);

    $preparation = ocrPrepare(['ocr' => '1']);

    expect($preparation['status'])->toBe('ready');
    expect($preparation['ocr_used'])->toBeTrue();
    expect($ocr->calls)->toBe([[1]]);
    expect($preparation['pages'][0]['origin'])->toBe('mixed');
    expect(substr_count($preparation['pages'][0]['text'], 'Curso de Laravel'))->toBe(1);
});

it('keeps native text when forced OCR does not add useful content', function () {
    bindOcrNativeExtractor([[
        'page' => 1,
        'text' => "Certificado de finalización\nCurso de Laravel\nAcademy\nEmitido en 2024\nDuración: 40 horas",
    ]]);
    bindFakeOcrExtractor(fn (): array => [[
        'page' => 1,
        'text' => "Certificado de finalización\nCurso de Laravel\nAcademy",
    ]]);

    $preparation = ocrPrepare(['ocr' => '1']);

    expect($preparation['status'])->toBe('ready');
    expect($preparation['ocr_used'])->toBeFalse();
    expect($preparation['pages'][0]['origin'])->toBe('native');
    expect($preparation['ocr_warning'])->toContain('texto nativo');
});
