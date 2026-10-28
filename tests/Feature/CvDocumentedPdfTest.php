<?php

use App\Models\Certification;
use App\Models\CvProfile;
use App\Models\Document;
use App\Models\EducationEntry;
use App\Models\WorkExperience;
use App\Support\CvEvidenceOrder;
use App\Support\CvPdfRenderer;
use App\Support\CvPdfValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\Process\Process;

function documentedPdfFixture(string $token): string
{
    static $pdfs = [];

    if (isset($pdfs[$token])) {
        return $pdfs[$token];
    }

    $browsershot = Browsershot::html(
        '<html><body><h1>'.$token.'</h1></body></html>',
    )
        ->newHeadless()
        ->format('A4')
        ->showBackground()
        ->disableJavascript()
        ->setNodeModulePath(base_path('node_modules'))
        ->setChromePath(cvPdfChromePath());

    $pdfs[$token] = $browsershot->pdf();

    return $pdfs[$token];
}

function documentedPdfProfile(string $name = 'Analista Senior'): CvProfile
{
    return CvProfile::factory()->create([
        'name' => $name,
        'professional_title' => 'Analista de Sistemas',
        'summary' => 'Resumen del perfil documentado.',
    ]);
}

function documentedPdfDocument(string $title, string $pdf): Document
{
    $document = Document::factory()->create([
        'title' => $title,
        'checksum' => hash('sha256', $pdf),
        'size_bytes' => strlen($pdf),
        'is_available' => true,
        'last_verified_at' => now()->startOfSecond(),
    ]);

    Storage::disk('local')->put($document->path(), $pdf);

    return $document;
}

function documentedPdfUrl(CvProfile $profile, array $documentIds): string
{
    $url = '/cv/documentado?profile='.$profile->id;

    foreach ($documentIds as $documentId) {
        $url .= '&documents%5B%5D='.$documentId;
    }

    return $url;
}

function documentedPdfText(string $pdf): string
{
    return trim((string) preg_replace(
        '/\s+/u',
        ' ',
        (new Parser)->parseContent($pdf)->getText(),
    ));
}

function documentedPdfPageCount(string $pdf): int
{
    return count((new Parser)->parseContent($pdf)->getPages());
}

function documentedPdfEncrypted(string $pdf): string
{
    $directory = sys_get_temp_dir().'/cv-documented-'.uniqid('', true);

    if (! mkdir($directory, 0700, true) && ! is_dir($directory)) {
        throw new RuntimeException('Unable to create the encryption directory.');
    }

    $input = $directory.'/input.pdf';
    $output = $directory.'/output.pdf';

    try {
        file_put_contents($input, $pdf);

        $process = new Process([
            'qpdf',
            '--encrypt',
            'user',
            'owner',
            '256',
            '--',
            $input,
            $output,
        ]);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput());
        }

        return (string) file_get_contents($output);
    } finally {
        @unlink($input);
        @unlink($output);
        @rmdir($directory);
    }
}

beforeEach(function () {
    Storage::fake('local');
    config(['services.qpdf.binary' => 'qpdf']);

    $this->app->bind(CvPdfRenderer::class, fn () => new class extends CvPdfRenderer
    {
        public function render(array $preview): string
        {
            return documentedPdfFixture('CVDOC');
        }
    });
});

it('downloads the CV followed by the selected annexes in the approved order', function () {
    $profile = documentedPdfProfile();

    $educationOld = EducationEntry::factory()->create([
        'institution' => 'Universidad Antigua',
        'program' => 'Sistemas',
        'end_year' => 2018,
        'end_month' => 12,
    ]);
    $educationNew = EducationEntry::factory()->create([
        'institution' => 'Universidad Nueva',
        'program' => 'Sistemas',
        'end_year' => 2020,
        'end_month' => 12,
    ]);
    $certification = Certification::factory()->create([
        'name' => 'Certificación documentada',
        'date_year' => 2024,
        'date_month' => 6,
    ]);
    $experience = WorkExperience::factory()->create([
        'company' => 'Acme',
        'position' => 'Analista funcional',
        'end_year' => 2022,
        'end_month' => 3,
    ]);

    $profile->educationEntries()->attach($educationOld, ['relevance' => 'media']);
    $profile->educationEntries()->attach($educationNew, ['relevance' => 'alta']);
    $profile->certifications()->attach($certification, ['relevance' => 'alta']);
    $profile->workExperiences()->attach($experience, ['relevance' => 'media']);

    $documentEducationOld = documentedPdfDocument(
        'Educación antigua',
        documentedPdfFixture('EDUOLD'),
    );
    $documentEducationOld->educationEntries()->attach($educationOld);

    $documentEducationNew = documentedPdfDocument(
        'Educación nueva',
        documentedPdfFixture('EDUNEW'),
    );
    $documentEducationNew->educationEntries()->attach($educationNew);

    $documentCertification = documentedPdfDocument(
        'Certificación',
        documentedPdfFixture('CERTX'),
    );
    $documentCertification->certifications()->attach($certification);

    $documentExperience = documentedPdfDocument(
        'Experiencia',
        documentedPdfFixture('EXPX'),
    );
    $documentExperience->workExperiences()->attach($experience);

    $documents = [
        $documentEducationOld,
        $documentEducationNew,
        $documentCertification,
        $documentExperience,
    ];
    $hashes = [];
    $verifiedAt = [];

    foreach ($documents as $document) {
        $hashes[$document->id] = hash_file(
            'sha256',
            Storage::disk('local')->path($document->path()),
        );
        $verifiedAt[$document->id] = $document->last_verified_at;
    }

    $response = $this->get(documentedPdfUrl($profile, [
        $documentExperience->id,
        $documentCertification->id,
        $documentEducationOld->id,
        $documentEducationNew->id,
    ]));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader(
            'Content-Disposition',
            'attachment; filename="cv-analista-senior-documentado.pdf"',
        );

    $pdf = (string) $response->getContent();
    $text = documentedPdfText($pdf);
    $previous = -1;

    foreach ([
        'CVDOC',
        'EDUNEW',
        'EDUOLD',
        'CERTX',
        'EXPX',
    ] as $token) {
        $position = strpos($text, $token);

        expect($position)->not->toBeFalse()
            ->and($position)->toBeGreaterThan($previous);

        $previous = $position;
    }

    expect($pdf)->toStartWith('%PDF-')
        ->and(documentedPdfPageCount($pdf))->toBe(5)
        ->and((string) $response->headers->get('Content-Length'))->toBe((string) strlen($pdf))
        ->and((string) $response->headers->get('Cache-Control'))->toContain('no-store')
        ->and((string) $response->headers->get('Cache-Control'))->toContain('private')
        ->and((string) $response->headers->get('Pragma'))->toBe('no-cache')
        ->and((string) $response->headers->get('X-Content-Type-Options'))->toBe('nosniff');

    foreach ($documents as $document) {
        expect(hash_file('sha256', Storage::disk('local')->path($document->path())))
            ->toBe($hashes[$document->id])
            ->and($document->fresh()->is_available)->toBeTrue()
            ->and($document->fresh()->last_verified_at->equalTo($verifiedAt[$document->id]))->toBeTrue();
    }

    expect(Storage::disk('local')->allFiles('cv-documented'))->toBe([])
        ->and(Document::count())->toBe(4)
        ->and(DB::table('document_education_entry')->count())->toBe(2)
        ->and(DB::table('document_certification')->count())->toBe(1)
        ->and(DB::table('document_work_experience')->count())->toBe(1);
});

it('includes only the selected eligible documents', function () {
    $profile = documentedPdfProfile();
    $education = EducationEntry::factory()->create(['end_year' => 2020, 'end_month' => 1]);
    $profile->educationEntries()->attach($education);

    $selected = documentedPdfDocument(
        'Anexo seleccionado',
        documentedPdfFixture('KEEP'),
    );
    $selected->educationEntries()->attach($education);

    $excluded = documentedPdfDocument(
        'Anexo no seleccionado',
        documentedPdfFixture('OMIT'),
    );
    $excluded->educationEntries()->attach($education);

    $response = $this->get(documentedPdfUrl($profile, [$selected->id]));

    $response->assertOk();

    $pdf = (string) $response->getContent();
    $text = documentedPdfText($pdf);

    expect(documentedPdfPageCount($pdf))->toBe(2)
        ->and($text)->toContain('KEEP')
        ->and($text)->not->toContain('OMIT');
});

it('includes a selected document only once when its id is repeated', function () {
    $profile = documentedPdfProfile();
    $education = EducationEntry::factory()->create(['end_year' => 2020, 'end_month' => 1]);
    $profile->educationEntries()->attach($education);

    $document = documentedPdfDocument(
        'Anexo único',
        documentedPdfFixture('UNICO'),
    );
    $document->educationEntries()->attach($education);

    $response = $this->get(documentedPdfUrl($profile, [
        $document->id,
        $document->id,
    ]));

    $response->assertOk();

    $text = documentedPdfText((string) $response->getContent());

    expect(documentedPdfPageCount((string) $response->getContent()))->toBe(2)
        ->and(substr_count($text, 'UNICO'))->toBe(1);
});

it('includes a document associated with several included records only once', function () {
    $profile = documentedPdfProfile();
    $education = EducationEntry::factory()->create(['end_year' => 2020, 'end_month' => 1]);
    $certification = Certification::factory()->create([
        'name' => 'Certificación compartida',
        'date_year' => 2024,
        'date_month' => 1,
    ]);
    $profile->educationEntries()->attach($education);
    $profile->certifications()->attach($certification);

    $document = documentedPdfDocument(
        'Anexo compartido',
        documentedPdfFixture('SHARED'),
    );
    $document->educationEntries()->attach($education);
    $document->certifications()->attach($certification);

    $response = $this->get(documentedPdfUrl($profile, [$document->id]));

    $response->assertOk();

    $pdf = (string) $response->getContent();
    $text = documentedPdfText($pdf);

    expect(documentedPdfPageCount($pdf))->toBe(2)
        ->and(substr_count($text, 'SHARED'))->toBe(1);
});

it('orders evidence by group, date, name and id', function () {
    $profile = documentedPdfProfile();
    $education = EducationEntry::factory()->create(['end_year' => 2020, 'end_month' => 1]);
    $certification = Certification::factory()->create([
        'date_year' => 2024,
        'date_month' => 1,
    ]);
    $experience = WorkExperience::factory()->create([
        'end_year' => 2022,
        'end_month' => 1,
    ]);

    $profile->educationEntries()->attach($education);
    $profile->certifications()->attach($certification);
    $profile->workExperiences()->attach($experience);

    $documentB = Document::factory()->create(['title' => 'B']);
    $documentA = Document::factory()->create(['title' => 'A']);
    $documentSameFirst = Document::factory()->create(['title' => 'Same']);
    $documentSameSecond = Document::factory()->create(['title' => 'Same']);
    $documentCertification = Document::factory()->create(['title' => 'Certificación']);
    $documentExperience = Document::factory()->create(['title' => 'Experiencia']);

    foreach ([
        $documentB,
        $documentA,
        $documentSameFirst,
        $documentSameSecond,
    ] as $document) {
        $document->educationEntries()->attach($education);
    }

    $documentCertification->certifications()->attach($certification);
    $documentExperience->workExperiences()->attach($experience);

    $ordered = app(CvEvidenceOrder::class)->order($profile, [
        $documentExperience->id,
        $documentSameSecond->id,
        $documentCertification->id,
        $documentSameFirst->id,
        $documentB->id,
        $documentA->id,
    ]);

    expect(array_map(fn (Document $document): int => $document->id, $ordered))
        ->toBe([
            $documentA->id,
            $documentB->id,
            $documentSameFirst->id,
            $documentSameSecond->id,
            $documentCertification->id,
            $documentExperience->id,
        ]);
});

it('rejects a documented download without selected evidence', function () {
    $profile = documentedPdfProfile();

    $response = $this->get('/cv/documentado?profile='.$profile->id);

    $response->assertRedirect("/cv?profile={$profile->id}")
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas(
            'toast.message',
            'Seleccioná al menos una evidencia para generar el CV documentado.',
        );

    expect($response->getContent())->not->toStartWith('%PDF-');
});

it('rejects evidence outside the profile and malformed document ids', function () {
    $profile = documentedPdfProfile();
    $includedExperience = WorkExperience::factory()->create();
    $excludedExperience = WorkExperience::factory()->create();
    $profile->workExperiences()->attach($includedExperience);

    $document = documentedPdfDocument(
        'Anexo ajeno',
        documentedPdfFixture('AJENO'),
    );
    $document->workExperiences()->attach($excludedExperience);

    $urls = [
        documentedPdfUrl($profile, [$document->id]),
        '/cv/documentado?profile='.$profile->id.'&documents[]=abc',
        '/cv/documentado?profile='.$profile->id.'&documents[]=0',
        '/cv/documentado?profile='.$profile->id.'&documents=1',
    ];

    foreach ($urls as $url) {
        $response = $this->get($url);

        $response->assertRedirect("/cv?profile={$profile->id}")
            ->assertSessionHas('toast.type', 'error')
            ->assertSessionHas(
                'toast.message',
                'La selección de evidencias no es válida. Volvé a elegir las evidencias del perfil.',
            );

        expect($response->getContent())->not->toStartWith('%PDF-');
    }
});

it('rejects a selected document whose file is missing', function () {
    $profile = documentedPdfProfile();
    $education = EducationEntry::factory()->create();
    $profile->educationEntries()->attach($education);

    $document = Document::factory()->create([
        'title' => 'Documento faltante',
        'checksum' => hash('sha256', 'missing'),
    ]);
    $document->educationEntries()->attach($education);

    $response = $this->get(documentedPdfUrl($profile, [$document->id]));

    $response->assertRedirect("/cv?profile={$profile->id}")
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas(
            'toast.message',
            'No se pudo incluir «Documento faltante»: el archivo no está disponible.',
        );

    expect($response->getContent())->not->toStartWith('%PDF-')
        ->and(Storage::disk('local')->allFiles('cv-documented'))->toBe([]);
});

it('rejects a selected document whose file was altered', function () {
    $profile = documentedPdfProfile();
    $education = EducationEntry::factory()->create();
    $profile->educationEntries()->attach($education);

    $document = documentedPdfDocument(
        'Documento alterado',
        documentedPdfFixture('ORIGX'),
    );
    $document->educationEntries()->attach($education);
    Storage::disk('local')->put(
        $document->path(),
        documentedPdfFixture('ALTX'),
    );

    $response = $this->get(documentedPdfUrl($profile, [$document->id]));

    $response->assertRedirect("/cv?profile={$profile->id}")
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas(
            'toast.message',
            'No se pudo incluir «Documento alterado»: el archivo fue alterado.',
        );

    expect($response->getContent())->not->toStartWith('%PDF-')
        ->and(Storage::disk('local')->allFiles('cv-documented'))->toBe([]);
});

it('rejects a selected file that is not a valid pdf', function () {
    $profile = documentedPdfProfile();
    $education = EducationEntry::factory()->create();
    $profile->educationEntries()->attach($education);

    $document = documentedPdfDocument('Documento inválido', 'no es un pdf');
    $document->educationEntries()->attach($education);

    $response = $this->get(documentedPdfUrl($profile, [$document->id]));

    $response->assertRedirect("/cv?profile={$profile->id}")
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas(
            'toast.message',
            'No se pudo validar «Documento inválido»: el PDF no es válido o no puede leerse.',
        );

    expect($response->getContent())->not->toStartWith('%PDF-')
        ->and(Storage::disk('local')->allFiles('cv-documented'))->toBe([]);
});

it('rejects a selected protected document without downloading a partial pdf', function () {
    $profile = documentedPdfProfile();
    $education = EducationEntry::factory()->create();
    $profile->educationEntries()->attach($education);

    $protectedPdf = documentedPdfEncrypted(
        documentedPdfFixture('PROTX'),
    );
    $document = documentedPdfDocument('Documento protegido', $protectedPdf);
    $document->educationEntries()->attach($education);

    $response = $this->get(documentedPdfUrl($profile, [$document->id]));

    $response->assertRedirect("/cv?profile={$profile->id}")
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas(
            'toast.message',
            'No se pudo incluir «Documento protegido»: el documento está protegido y no puede anexarse.',
        );

    expect($response->getContent())->not->toStartWith('%PDF-')
        ->and(Storage::disk('local')->allFiles('cv-documented'))->toBe([]);
});

it('rejects generation when qpdf is unavailable', function () {
    $profile = documentedPdfProfile();
    $education = EducationEntry::factory()->create();
    $profile->educationEntries()->attach($education);

    $document = documentedPdfDocument(
        'Anexo válido',
        documentedPdfFixture('VALIDX'),
    );
    $document->educationEntries()->attach($education);

    config(['services.qpdf.binary' => '/missing/qpdf']);

    $response = $this->get(documentedPdfUrl($profile, [$document->id]));

    $response->assertRedirect("/cv?profile={$profile->id}")
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas(
            'toast.message',
            'La herramienta de concatenación no está disponible. Verificá qpdf.',
        );

    expect($response->getContent())->not->toStartWith('%PDF-')
        ->and(Storage::disk('local')->allFiles('cv-documented'))->toBe([]);
});

it('rejects concatenation failures without leaving a partial pdf', function () {
    $profile = documentedPdfProfile();
    $education = EducationEntry::factory()->create();
    $profile->educationEntries()->attach($education);

    $document = documentedPdfDocument(
        'Anexo válido',
        documentedPdfFixture('VALIDX'),
    );
    $document->educationEntries()->attach($education);

    $script = <<<'SH'
#!/bin/sh
case "$1" in
    --version) echo "qpdf version fake"; exit 0 ;;
    --is-encrypted) exit 2 ;;
    --show-npages) echo "1"; exit 0 ;;
    *) echo "fallo de concatenación" >&2; exit 2 ;;
esac
SH;
    $scriptPath = Storage::disk('local')->path('fake-qpdf.sh');
    file_put_contents($scriptPath, $script);
    chmod($scriptPath, 0755);
    config(['services.qpdf.binary' => $scriptPath]);

    $response = $this->get(documentedPdfUrl($profile, [$document->id]));

    $response->assertRedirect("/cv?profile={$profile->id}")
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas(
            'toast.message',
            'No se pudo concatenar el CV con las evidencias seleccionadas. Volvé a intentar.',
        );

    expect($response->getContent())->not->toStartWith('%PDF-')
        ->and(Storage::disk('local')->allFiles('cv-documented'))->toBe([]);
});

it('rejects a generated pdf that fails structural validation', function () {
    $profile = documentedPdfProfile();
    $education = EducationEntry::factory()->create();
    $profile->educationEntries()->attach($education);

    $document = documentedPdfDocument(
        'Anexo válido',
        documentedPdfFixture('VALIDX'),
    );
    $document->educationEntries()->attach($education);

    $this->app->bind(CvPdfValidator::class, fn () => new class extends CvPdfValidator
    {
        public function isValid(string $pdf): bool
        {
            return false;
        }
    });

    $response = $this->get(documentedPdfUrl($profile, [$document->id]));

    $response->assertRedirect("/cv?profile={$profile->id}")
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas(
            'toast.message',
            'No se pudo generar el PDF del CV. Volvé a intentar.',
        );

    expect($response->getContent())->not->toStartWith('%PDF-')
        ->and(Storage::disk('local')->allFiles('cv-documented'))->toBe([]);
});

it('keeps the generator states when no usable profile is selected', function () {
    CvProfile::factory()->inactive()->create();

    $this->get('/cv/documentado')
        ->assertRedirect('/cv')
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas(
            'toast.message',
            'No hay perfiles activos para exportar.',
        );

    $active = documentedPdfProfile();
    CvProfile::factory()->inactive()->create();

    $this->get('/cv/documentado')
        ->assertRedirect('/cv')
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas(
            'toast.message',
            'Seleccioná un perfil para exportar el CV documentado.',
        );

    $this->get('/cv/documentado?profile=abc')
        ->assertRedirect('/cv')
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas(
            'toast.message',
            'El perfil seleccionado no está disponible. Elegí un perfil activo.',
        );

    $inactive = CvProfile::factory()->inactive()->create();

    $this->get('/cv/documentado?profile='.$inactive->id)
        ->assertRedirect('/cv?profile='.$inactive->id)
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas(
            'toast.message',
            'El perfil seleccionado no está disponible. Elegí un perfil activo.',
        );

    expect($active->id)->toBeGreaterThan(0);
});

it('only accepts GET on the documented pdf route', function () {
    foreach (['post', 'put', 'patch', 'delete'] as $method) {
        $this->{$method}('/cv/documentado')->assertStatus(405);
    }
});
