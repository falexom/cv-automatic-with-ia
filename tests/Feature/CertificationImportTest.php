<?php

use App\Models\Certification;
use App\Models\Document;
use App\Models\Profile;
use App\Models\Skill;
use App\Support\CertificationImport\CertificationImportAnalyzer;
use App\Support\CertificationImport\LocalOcrExtractor;
use App\Support\CertificationImport\PdfTextExtractor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

final class FakeImportPdfTextExtractor implements PdfTextExtractor
{
    /**
     * @var array<int, array{page: int, text: string}>
     */
    public array $calls = [];

    /**
     * @param  array<int, array{page: int, text: string}>  $pages
     */
    public function __construct(private array $pages) {}

    /**
     * @param  array<int, array{page: int, text: string}>  $pages
     */
    public function setPages(array $pages): void
    {
        $this->pages = $pages;
    }

    /**
     * @return array<int, array{page: int, text: string}>
     */
    public function extract(string $contents): array
    {
        $this->calls[] = ['size' => strlen($contents)];

        return $this->pages;
    }
}

final class FakeImportAnalyzer implements CertificationImportAnalyzer
{
    /**
     * @var array<int, array{skills: array<int, array<string, mixed>>, pages: array<int, array{page: int, text: string}>}>
     */
    public array $calls = [];

    public function __construct(private Closure $callback) {}

    /**
     * @param  array<int, array{ref: string, name: string, category: string}>  $skills
     * @param  array<int, array{page: int, text: string}>  $pages
     * @return array<string, mixed>
     */
    public function analyze(array $skills, array $pages): array
    {
        $this->calls[] = ['skills' => $skills, 'pages' => $pages];

        return ($this->callback)($skills, $pages);
    }
}

function bindImportExtractor(array $pages): FakeImportPdfTextExtractor
{
    $fake = new FakeImportPdfTextExtractor($pages);
    app()->bind(PdfTextExtractor::class, fn (): FakeImportPdfTextExtractor => $fake);

    return $fake;
}

function bindImportAnalyzer(Closure $callback): FakeImportAnalyzer
{
    $fake = new FakeImportAnalyzer($callback);
    app()->bind(CertificationImportAnalyzer::class, fn (): FakeImportAnalyzer => $fake);

    return $fake;
}

final class FakeImportOcrExtractor implements LocalOcrExtractor
{
    /**
     * @var array<int, array<int, int>>
     */
    public array $calls = [];

    /**
     * @param  Closure(array<int, int>): array<int, array{page: int, text: string}>  $callback
     */
    public function __construct(private Closure $callback) {}

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
        return ['spa', 'eng'];
    }
}

function bindImportOcr(Closure $callback): FakeImportOcrExtractor
{
    $fake = new FakeImportOcrExtractor($callback);
    app()->bind(LocalOcrExtractor::class, fn (): FakeImportOcrExtractor => $fake);

    return $fake;
}

/**
 * @return array<int, array{page: int, text: string}>
 */
function importTextPages(): array
{
    return [[
        'page' => 1,
        'text' => implode("\n", [
            'Certificado de finalización',
            'Curso de Laravel',
            'Academy',
            'Emitido en 2024',
            'Duración: 40 horas',
            'Código: ABC-123',
            'Validación: https://example.com/validar',
            'Temario: Laravel y PHP',
        ]),
    ]];
}

/**
 * @param  array<int, array{ref: string, name: string, category: string}>  $skills
 * @return array<string, mixed>
 */
function importProposal(array $skills, array $overrides = []): array
{
    $skillRef = $skills[0]['ref'] ?? null;

    $proposal = [
        'schema_version' => 1,
        'sufficiency' => 'sufficient',
        'sufficiency_note' => null,
        'fields' => [
            'name' => [
                'state' => 'found',
                'value' => 'Curso de Laravel',
                'support' => 'Curso de Laravel',
                'page' => null,
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
                'state' => 'found',
                'value' => 'ABC-123',
                'support' => 'Código: ABC-123',
                'page' => 1,
                'reason' => null,
            ],
            'validation_url' => [
                'state' => 'found',
                'value' => 'https://example.com/validar',
                'support' => 'Validación: https://example.com/validar',
                'page' => 1,
                'reason' => null,
            ],
            'category' => [
                'state' => 'suggested',
                'value' => 'Programación',
                'support' => null,
                'page' => null,
                'reason' => 'El temario trata desarrollo con Laravel.',
            ],
        ],
        'skills' => $skillRef === null ? [] : [[
            'ref' => $skillRef,
            'state' => 'suggested',
            'support' => null,
            'page' => null,
            'reason' => 'El temario menciona Laravel.',
        ]],
        'ambiguities' => [],
    ];

    return array_replace_recursive($proposal, $overrides);
}

/**
 * @return array<string, mixed>
 */
function importPost(string $uri, array $data): TestResponse
{
    return test()->post($uri, $data, ['Accept' => 'application/json']);
}

function importPrepare(string $filename = 'certificado.pdf'): array
{
    return importPost('/certifications/import/prepare', [
        'file' => pdfUpload($filename),
    ])->assertOk()->json();
}

/**
 * @param  array<string, mixed>  $preparation
 * @param  array<int, array{page: int, text: string}>|null  $pages
 * @param  array<string, mixed>  $extra
 */
function importAnalyze(array $preparation, ?array $pages = null, array $extra = []): TestResponse
{
    return test()->postJson('/certifications/import/analyze', [
        'preparation_token' => $preparation['preparation_token'],
        'consent' => true,
        'pages' => $pages ?? $preparation['pages'],
        ...$extra,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function importConfirmData(array $overrides = []): array
{
    return [
        'name' => 'Curso de Laravel',
        'institution' => 'Academy',
        'date_year' => 2024,
        'duration' => '40 horas',
        'code' => 'ABC-123',
        'validation_url' => 'https://example.com/validar',
        'category' => 'Programación',
        'skill_ids' => [],
        'confirmation_id' => (string) Str::uuid(),
        'file' => pdfUpload(),
        ...$overrides,
    ];
}

beforeEach(function () {
    Storage::fake('local');
    bindImportExtractor(importTextPages());
    bindImportOcr(fn (): array => []);
});

it('prepares native text, redacts personal data and persists nothing', function () {
    $profile = Profile::current();
    $profile->update([
        'full_name' => 'Persona Secreta',
        'email' => 'privado@example.com',
        'phone' => '+54 11 9999 8888',
    ]);

    $extractor = bindImportExtractor([[
        'page' => 1,
        'text' => implode("\n", [
            'Persona Secreta',
            'privado@example.com',
            'Teléfono: +54 11 9999 8888',
            'Curso de Laravel',
            'Academy',
        ]),
    ]]);

    $response = importPrepare();

    expect($response['status'])->toBe('ready');
    expect($response['preparation_token'])->toBeString();
    expect($response['redactions'])->toBeGreaterThanOrEqual(3);
    expect($response['pages'][0]['text'])->toContain('Curso de Laravel');
    expect($response['pages'][0]['text'])->not->toContain('Persona Secreta');
    expect($response['pages'][0]['text'])->not->toContain('privado@example.com');
    expect($response['pages'][0]['text'])->not->toContain('+54 11 9999 8888');
    expect($extractor->calls)->toHaveCount(1);

    expect(Certification::query()->count())->toBe(0);
    expect(Document::query()->count())->toBe(0);
    expect(Storage::disk('local')->allFiles('documents'))->toBe([]);
});

it('reports insufficient native and OCR text with a manual alternative', function () {
    bindImportExtractor([['page' => 1, 'text' => 'Membrete']]);
    $analyzer = bindImportAnalyzer(fn (): array => []);

    $response = importPrepare();

    expect($response['status'])->toBe('insufficient');
    expect($response['preparation_token'])->toBeNull();
    expect($response['message'])->toContain('OCR');
    expect($analyzer->calls)->toBe([]);
    expect(Certification::query()->count())->toBe(0);
});

it('rejects an invalid PDF before extracting text', function () {
    $extractor = bindImportExtractor(importTextPages());

    importPost('/certifications/import/prepare', [
        'file' => UploadedFile::fake()->createWithContent('roto.pdf', 'esto no es un PDF'),
    ])->assertStatus(422)->assertJsonValidationErrors('file');

    expect($extractor->calls)->toBe([]);
    expect(Certification::query()->count())->toBe(0);
});

it('rejects files over the current 20 MB limit before analysis', function () {
    $extractor = bindImportExtractor(importTextPages());

    importPost('/certifications/import/prepare', [
        'file' => UploadedFile::fake()->create('grande.pdf', 20481, 'application/pdf'),
    ])->assertStatus(422)->assertJsonValidationErrors('file');

    expect($extractor->calls)->toBe([]);
    expect(Certification::query()->count())->toBe(0);
});

it('requires explicit consent before calling the external analyzer', function () {
    $preparation = importPrepare();
    $analyzer = bindImportAnalyzer(fn (): array => importProposal([]));

    $this->postJson('/certifications/import/analyze', [
        'preparation_token' => $preparation['preparation_token'],
        'consent' => false,
        'pages' => $preparation['pages'],
    ])->assertStatus(422)->assertJsonValidationErrors('consent');

    expect($analyzer->calls)->toBe([]);
});

it('rejects skills outside the current catalog', function () {
    Skill::factory()->create(['name' => 'Laravel', 'category' => 'tecnologia']);
    $preparation = importPrepare();
    bindImportAnalyzer(fn (): array => importProposal([], [
        'skills' => [[
            'ref' => 'skl_ffffffffffff',
            'state' => 'suggested',
            'support' => null,
            'page' => null,
            'reason' => 'Fuera del catálogo.',
        ]],
    ]));

    importAnalyze($preparation)
        ->assertStatus(502)
        ->assertJsonPath('code', 'invalid_reference');

    expect(Certification::query()->count())->toBe(0);
});

it('keeps an invented citation as an unverified suggestion and reviews the rest', function () {
    $preparation = importPrepare();
    bindImportAnalyzer(fn (array $skills): array => importProposal($skills, [
        'fields' => [
            'name' => [
                'state' => 'found',
                'value' => 'Certificación inventada',
                'support' => 'Certificación inventada',
                'page' => 1,
                'reason' => null,
            ],
        ],
    ]));

    $response = importAnalyze($preparation)->assertOk()->json();

    expect($response['proposal']['fields']['name']['state'])->toBe('unverified');
    expect($response['proposal']['fields']['name']['value'])->toBe('Certificación inventada');
    expect($response['proposal']['fields']['name']['support'])->toBeNull();
    expect($response['proposal']['fields']['name']['reason'])->toContain('cita');
    expect($response['proposal']['fields']['institution']['state'])->toBe('found');
    expect($response['confirmation_token'])->toBeString();

    expect(Certification::query()->count())->toBe(0);
});

it('returns provenance, support, page and suggestions without persisting', function () {
    $skill = Skill::factory()->create(['name' => 'Laravel', 'category' => 'tecnologia']);
    $preparation = importPrepare();
    $analyzer = bindImportAnalyzer(fn (array $skills): array => importProposal($skills, [
        'ambiguities' => ['La fecha no incluye mes.'],
    ]));

    $response = importAnalyze($preparation)->assertOk()->json();

    expect($response['proposal']['fields']['name']['state'])->toBe('found');
    expect($response['proposal']['fields']['name']['page'])->toBe(1);
    expect($response['proposal']['fields']['date']['month'])->toBeNull();
    expect($response['proposal']['fields']['category']['state'])->toBe('suggested');
    expect($response['proposal']['fields']['category']['reason'])->toContain('temario');
    expect($response['proposal']['skills'][0]['id'])->toBe($skill->id);
    expect($response['proposal']['skills'][0]['state'])->toBe('suggested');
    expect($response['proposal']['ambiguities'])->toBe(['La fecha no incluye mes.']);
    expect($response['confirmation_token'])->toBeString();

    expect($analyzer->calls)->toHaveCount(1);
    expect($analyzer->calls[0]['skills'][0]['ref'])->toStartWith('skl_');
    expect($analyzer->calls[0]['pages'][0]['text'])->toContain('Curso de Laravel');
    expect(Certification::query()->count())->toBe(0);
    expect(Document::query()->count())->toBe(0);
});

it('saves the reviewed record, accepted skill and original atomically', function () {
    $skill = Skill::factory()->create(['name' => 'Laravel', 'category' => 'tecnologia']);
    $preparation = importPrepare();
    bindImportAnalyzer(fn (array $skills): array => importProposal($skills));
    $analysis = importAnalyze($preparation)->assertOk()->json();

    importPost('/certifications/import/confirm', importConfirmData([
        'proposal_token' => $analysis['confirmation_token'],
        'accepted_category' => '1',
        'date_year' => '2024',
        'skill_ids' => [(string) $skill->id],
        'accepted_skill_ids' => [(string) $skill->id],
    ]))->assertOk()->assertJsonStructure(['redirect']);

    $certification = Certification::query()->sole();
    $document = Document::query()->sole();
    $contents = storedPdfContent();

    expect($certification->name)->toBe('Curso de Laravel');
    expect($certification->institution)->toBe('Academy');
    expect($certification->date_year)->toBe(2024);
    expect($certification->date_month)->toBeNull();
    expect($certification->duration)->toBe('40 horas');
    expect($certification->code)->toBe('ABC-123');
    expect($certification->validation_url)->toBe('https://example.com/validar');
    expect($certification->category)->toBe('Programación');
    expect($certification->import_confirmation_id)->toBeString();
    expect($certification->skills()->pluck('skills.id')->all())->toBe([$skill->id]);
    expect($certification->documents()->pluck('documents.id')->all())->toBe([$document->id]);
    expect($document->certifications()->pluck('certifications.id')->all())->toBe([$certification->id]);
    expect($document->checksum)->toBe(hash('sha256', $contents));
    expect($document->title)->toBeNull();
    expect($document->description)->toBeNull();
    Storage::disk('local')->assertExists($document->path());
    expect(Storage::disk('local')->get($document->path()))->toBe($contents);
});

it('blocks a suggested category until it is explicitly accepted', function () {
    $preparation = importPrepare();
    bindImportAnalyzer(fn (array $skills): array => importProposal($skills));
    $analysis = importAnalyze($preparation)->assertOk()->json();

    importPost('/certifications/import/confirm', importConfirmData([
        'proposal_token' => $analysis['confirmation_token'],
        'category' => 'Programación',
    ]))->assertStatus(422)->assertJsonValidationErrors('category');

    expect(Certification::query()->count())->toBe(0);
    expect(Document::query()->count())->toBe(0);
    expect(Storage::disk('local')->allFiles('documents'))->toBe([]);
});

it('blocks a suggested skill until it is individually accepted', function () {
    $skill = Skill::factory()->create(['name' => 'Laravel', 'category' => 'tecnologia']);
    $preparation = importPrepare();
    bindImportAnalyzer(fn (array $skills): array => importProposal($skills));
    $analysis = importAnalyze($preparation)->assertOk()->json();

    importPost('/certifications/import/confirm', importConfirmData([
        'proposal_token' => $analysis['confirmation_token'],
        'accepted_category' => true,
        'skill_ids' => [$skill->id],
    ]))->assertStatus(422)->assertJsonValidationErrors('skill_ids');

    expect(Certification::query()->count())->toBe(0);
    expect(Document::query()->count())->toBe(0);
});

it('does not require accepting suggestions already written before the analysis', function () {
    $skill = Skill::factory()->create(['name' => 'Laravel', 'category' => 'tecnologia']);
    $preparation = importPrepare();
    bindImportAnalyzer(fn (array $skills): array => importProposal($skills));
    $analysis = importAnalyze($preparation, null, [
        'existing_category' => 'Programación',
        'existing_skill_ids' => [$skill->id],
    ])->assertOk()->json();

    importPost('/certifications/import/confirm', importConfirmData([
        'proposal_token' => $analysis['confirmation_token'],
        'category' => 'Programación',
        'skill_ids' => [$skill->id],
    ]))->assertOk()->assertJsonStructure(['redirect']);

    $certification = Certification::query()->sole();
    expect($certification->category)->toBe('Programación');
    expect($certification->skills()->pluck('skills.id')->all())->toBe([$skill->id]);
});

it('warns about a duplicate and saves only after explicit confirmation', function () {
    Certification::factory()->create([
        'name' => 'Curso de Laravel',
        'institution' => 'Academy',
    ]);
    $preparation = importPrepare();
    bindImportAnalyzer(fn (array $skills): array => importProposal($skills));
    $analysis = importAnalyze($preparation)->assertOk()->json();
    $payload = importConfirmData([
        'proposal_token' => $analysis['confirmation_token'],
        'accepted_category' => true,
    ]);

    importPost('/certifications/import/confirm', $payload)
        ->assertOk()
        ->assertJsonPath('requires_confirmation', true)
        ->assertJsonStructure(['duplicate_warning' => ['existing_id', 'name', 'institution']]);

    expect(Certification::query()->count())->toBe(1);
    expect(Document::query()->count())->toBe(0);

    importPost('/certifications/import/confirm', [
        ...$payload,
        'confirm_duplicate' => '1',
    ])->assertOk()->assertJsonStructure(['redirect']);

    expect(Certification::query()->count())->toBe(2);
    expect(Document::query()->count())->toBe(1);
});

it('does not duplicate a repeated confirmation', function () {
    $preparation = importPrepare();
    bindImportAnalyzer(fn (array $skills): array => importProposal($skills));
    $analysis = importAnalyze($preparation)->assertOk()->json();
    $payload = importConfirmData([
        'proposal_token' => $analysis['confirmation_token'],
        'accepted_category' => true,
    ]);

    importPost('/certifications/import/confirm', $payload)->assertOk();
    importPost('/certifications/import/confirm', $payload)->assertOk()->assertJsonStructure(['redirect']);

    expect(Certification::query()->count())->toBe(1);
    expect(Document::query()->count())->toBe(1);
    expect(DB::table('document_certification')->count())->toBe(1);
});

it('reuses an identical intact original without altering it', function () {
    $document = makeStoredDocument('original.pdf');
    $preparation = importPrepare();
    bindImportAnalyzer(fn (array $skills): array => importProposal($skills));
    $analysis = importAnalyze($preparation)->assertOk()->json();

    importPost('/certifications/import/confirm', importConfirmData([
        'proposal_token' => $analysis['confirmation_token'],
        'accepted_category' => true,
    ]))->assertOk()->assertSessionHas('toast', fn (mixed $toast): bool => is_array($toast)
        && str_contains((string) $toast['message'], 'reutilizado'));

    expect(Document::query()->count())->toBe(1);
    expect(Certification::query()->count())->toBe(1);
    $reused = Document::query()->sole();
    expect($reused->id)->toBe($document->id);
    expect($reused->original_filename)->toBe('original.pdf');
    expect($reused->title)->toBeNull();
    expect($reused->certifications()->count())->toBe(1);
    Storage::disk('local')->assertExists($reused->path());
    expect(Storage::disk('local')->get($reused->path()))->toBe(storedPdfContent());
});

it('refuses a missing or altered preexisting original without modifying it', function () {
    $missing = Document::factory()->create([
        'checksum' => hash('sha256', storedPdfContent()),
        'size_bytes' => strlen(storedPdfContent()),
    ]);
    $preparation = importPrepare();
    bindImportAnalyzer(fn (array $skills): array => importProposal($skills));
    $analysis = importAnalyze($preparation)->assertOk()->json();

    importPost('/certifications/import/confirm', importConfirmData([
        'proposal_token' => $analysis['confirmation_token'],
        'accepted_category' => true,
    ]))->assertStatus(409)->assertJsonPath('code', 'document_conflict');

    expect(Certification::query()->count())->toBe(0);
    expect(Document::query()->count())->toBe(1);
    expect($missing->fresh()->certifications()->count())->toBe(0);
    expect(Storage::disk('local')->allFiles('documents'))->toBe([]);
});

it('refuses an altered preexisting original without overwriting it', function () {
    $altered = makeStoredDocument(storedContents: 'contenido alterado');
    $preparation = importPrepare();
    bindImportAnalyzer(fn (array $skills): array => importProposal($skills));
    $analysis = importAnalyze($preparation)->assertOk()->json();

    importPost('/certifications/import/confirm', importConfirmData([
        'proposal_token' => $analysis['confirmation_token'],
        'accepted_category' => true,
    ]))->assertStatus(409)->assertJsonPath('code', 'document_conflict');

    expect(Certification::query()->count())->toBe(0);
    expect(Document::query()->count())->toBe(1);
    expect(Storage::disk('local')->get($altered->path()))->toBe('contenido alterado');
});

it('rejects a PDF that is different from the analyzed one', function () {
    $preparation = importPrepare();
    bindImportAnalyzer(fn (array $skills): array => importProposal($skills));
    $analysis = importAnalyze($preparation)->assertOk()->json();

    importPost('/certifications/import/confirm', importConfirmData([
        'proposal_token' => $analysis['confirmation_token'],
        'accepted_category' => true,
        'file' => UploadedFile::fake()->createWithContent(
            'otro.pdf',
            "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF",
        ),
    ]))->assertStatus(422)->assertJsonPath('code', 'file_mismatch');

    expect(Certification::query()->count())->toBe(0);
    expect(Document::query()->count())->toBe(0);
});

it('revalidates skills removed after the analysis', function () {
    $skill = Skill::factory()->create(['name' => 'Laravel', 'category' => 'tecnologia']);
    $preparation = importPrepare();
    bindImportAnalyzer(fn (array $skills): array => importProposal($skills));
    $analysis = importAnalyze($preparation)->assertOk()->json();
    $skillId = $skill->id;
    $skill->delete();

    importPost('/certifications/import/confirm', importConfirmData([
        'proposal_token' => $analysis['confirmation_token'],
        'accepted_category' => true,
        'skill_ids' => [$skillId],
        'accepted_skill_ids' => [$skillId],
    ]))->assertStatus(422)->assertJsonValidationErrors('skill_ids.0');

    expect(Certification::query()->count())->toBe(0);
    expect(Document::query()->count())->toBe(0);
    expect(Storage::disk('local')->allFiles('documents'))->toBe([]);
});

it('saves a manual fallback with the valid original and no proposal', function () {
    importPost('/certifications/import/confirm', importConfirmData())
        ->assertOk()
        ->assertJsonStructure(['redirect']);

    expect(Certification::query()->count())->toBe(1);
    expect(Document::query()->count())->toBe(1);
    expect(DB::table('document_certification')->count())->toBe(1);
    Storage::disk('local')->assertExists(Document::query()->sole()->path());
});

it('does not leave partial records or files when the association fails', function () {
    DB::statement("CREATE TRIGGER fail_document_certification BEFORE INSERT ON document_certification BEGIN SELECT RAISE(ABORT, 'boom'); END");

    try {
        importPost('/certifications/import/confirm', importConfirmData())
            ->assertStatus(500);

        expect(Certification::query()->count())->toBe(0);
        expect(Document::query()->count())->toBe(0);
        expect(DB::table('document_certification')->count())->toBe(0);
        expect(Storage::disk('local')->allFiles('documents'))->toBe([]);
    } finally {
        DB::statement('DROP TRIGGER IF EXISTS fail_document_certification');
    }
});

it('rejects invalid fields before persistence', function () {
    importPost('/certifications/import/confirm', importConfirmData([
        'validation_url' => 'no-es-una-url',
    ]))->assertStatus(422)->assertJsonValidationErrors('validation_url');

    expect(Certification::query()->count())->toBe(0);
    expect(Document::query()->count())->toBe(0);
    expect(Storage::disk('local')->allFiles('documents'))->toBe([]);
});

it('rejects a malformed preparation token', function () {
    $this->postJson('/certifications/import/analyze', [
        'preparation_token' => 'token-invalido',
        'consent' => true,
        'pages' => importTextPages(),
    ])->assertStatus(422)->assertJsonPath('code', 'invalid_preparation');

    expect(Certification::query()->count())->toBe(0);
});

it('exposes the configured notice and limits to the create page', function () {
    $this->get('/certifications/create')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Certifications/Create')
            ->where('import_notice.destination', 'Codex/OpenAI')
            ->has('import_notice.model_label')
            ->where('import_limits.max_kilobytes', 20480)
            ->has('import_limits.max_text_chars')
            ->has('skills'));
});
