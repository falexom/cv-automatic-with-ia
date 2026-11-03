<?php

use App\Models\Certification;
use App\Models\CvProfile;
use App\Models\Document as DocumentModel;
use App\Models\EducationEntry;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;
use App\Support\CvPdfRenderer;
use App\Support\CvPdfValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Smalot\PdfParser\Document;
use Smalot\PdfParser\Element;
use Smalot\PdfParser\Header;
use Smalot\PdfParser\Parser;
use Smalot\PdfParser\PDFObject;

beforeAll(fn () => cvPdfStartSsr());

afterAll(fn () => cvPdfStopSsr());

beforeEach(function () {
    $this->withVite();

    config(['services.browsershot.chrome_path' => cvPdfChromePath()]);
});

function cvPdfMaster(array $attributes = []): Profile
{
    $master = Profile::current();
    $master->update($attributes);

    return $master;
}

function cvPdfDocument(string $pdf): Document
{
    return (new Parser)->parseContent($pdf);
}

function cvPdfText(string $pdf): string
{
    return cvPdfNormalize(cvPdfDocument($pdf)->getText());
}

function cvPdfNormalize(string $text): string
{
    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function cvPdfHeader(TestResponse $response, string $header): string
{
    return (string) $response->headers->get($header);
}

function cvPdfMediaBox(Document $document): string
{
    $details = $document->getPages()[0]->getDetails();
    $mediaBox = $details['MediaBox'] ?? '';

    return is_array($mediaBox) ? implode(' ', $mediaBox) : (string) $mediaBox;
}

/**
 * @return array<int, string>
 */
function cvPdfTemporaryEntries(): array
{
    $root = storage_path('app/private/cv-pdf');

    if (! is_dir($root)) {
        return [];
    }

    $entries = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );

    foreach ($iterator as $entry) {
        $entries[] = $entry->getPathname();
    }

    return $entries;
}

/**
 * @return array<int, string>
 */
function cvPdfGeneratedFiles(): array
{
    $files = glob(base_path('cv-*.pdf')) ?: [];

    foreach ([public_path(), storage_path('app')] as $root) {
        if (! is_dir($root)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $entry) {
            if ($entry->isFile()
                && str_ends_with(strtolower($entry->getFilename()), '.pdf')
                && str_starts_with($entry->getFilename(), 'cv-')) {
                $files[] = $entry->getPathname();
            }
        }
    }

    return $files;
}

/**
 * @return array<int, string>
 */
function cvPdfUriAnnotations(Document $document): array
{
    $uris = [];

    foreach ($document->getObjectsByType('Annot', 'Link') as $annotation) {
        $action = $annotation->get('A');

        if (! $action instanceof Header
            && ! $action instanceof PDFObject) {
            continue;
        }

        $uri = $action->get('URI');

        if ($uri instanceof Element) {
            $uris[] = (string) $uri;
        }
    }

    return $uris;
}

it('downloads a valid pdf with the required headers and deterministic name', function () {
    cvPdfMaster(['full_name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

    $profile = CvProfile::factory()->create([
        'name' => 'Programador / Desarrollador',
        'professional_title' => 'Desarrolladora',
        'summary' => 'Resumen para la exportación.',
    ]);

    expect(Route::has('cv.pdf'))->toBeTrue();

    $response = $this->get("/cv/pdf?profile={$profile->id}");

    $response->assertOk();

    $pdf = $response->getContent();

    expect($pdf)->not->toBe('')
        ->and($pdf)->toStartWith('%PDF-')
        ->and(trim($pdf))->toEndWith('%%EOF')
        ->and(cvPdfHeader($response, 'Content-Type'))->toStartWith('application/pdf')
        ->and(cvPdfHeader($response, 'Content-Disposition'))
        ->toBe('attachment; filename="cv-programador-desarrollador.pdf"')
        ->and(cvPdfHeader($response, 'Content-Length'))->toBe((string) strlen($pdf))
        ->and(cvPdfHeader($response, 'Cache-Control'))->toContain('no-store')
        ->and(cvPdfHeader($response, 'Cache-Control'))->toContain('private')
        ->and(cvPdfHeader($response, 'Pragma'))->toBe('no-cache')
        ->and(cvPdfHeader($response, 'X-Content-Type-Options'))->toBe('nosniff');
});

it('produces a readable A4 portrait pdf with embedded fonts and real text', function () {
    cvPdfMaster(['full_name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

    $profile = CvProfile::factory()->create([
        'name' => 'Analista',
        'professional_title' => 'Analista de Sistemas',
        'summary' => 'Resumen propio con ñandú y estadística.',
    ]);

    $pdf = $this->get("/cv/pdf?profile={$profile->id}")->assertOk()->getContent();
    $document = cvPdfDocument($pdf);
    $text = cvPdfText($pdf);
    $mediaBox = cvPdfMediaBox($document);

    [, , $width, $height] = array_map('floatval', explode(' ', $mediaBox));

    expect($document->getPages())->toHaveCount(1)
        ->and($width)->toBeGreaterThan(590.0)
        ->and($width)->toBeLessThan(600.0)
        ->and($height)->toBeGreaterThan(837.0)
        ->and($height)->toBeLessThan(846.0)
        ->and($height)->toBeGreaterThan($width)
        ->and($pdf)->toMatch('~/FontFile[23]?~')
        ->and($text)->toContain('Ada Lovelace')
        ->and($text)->toContain('Analista de Sistemas')
        ->and($text)->toContain('Resumen propio con ñandú y estadística.')
        ->and($text)->toMatch('/\b1\s*\/\s*1\b/');
});

it('exports exactly the included content in the preview order without metadata or fallback', function () {
    $master = cvPdfMaster([
        'full_name' => 'Ada Lovelace',
        'phone' => '+54 11 5555 1234',
        'email' => 'ada@example.com',
        'location' => 'Buenos Aires, Argentina',
        'linkedin_url' => 'https://www.linkedin.com/in/ada',
        'github_url' => 'https://github.com/ada',
        'website_url' => 'https://ada.example.com',
        'professional_title' => 'Título del Maestro',
        'summary' => 'Resumen del Maestro',
    ]);

    $master->links()->create([
        'label' => 'Blog',
        'url' => 'https://blog.example.com',
    ]);

    $experience = WorkExperience::factory()->create([
        'position' => 'Analista funcional incluida',
        'company' => 'Acme Incluida',
        'start_year' => 2022,
        'start_month' => 3,
        'end_year' => null,
        'end_month' => null,
        'is_current' => true,
        'description' => 'Descripción incluida.',
        'responsibilities' => "Responsabilidad uno.\nResponsabilidad dos.",
        'achievements' => 'Logro incluido.',
    ]);
    $excludedExperience = WorkExperience::factory()->create([
        'position' => 'Experiencia excluida',
        'company' => 'Empresa excluida',
        'description' => 'No debe aparecer.',
    ]);

    $education = EducationEntry::factory()->create([
        'institution' => 'Universidad Incluida',
        'program' => 'Sistemas incluidos',
        'degree_level' => 'Licenciatura incluida',
        'description' => 'Formación incluida.',
    ]);
    $excludedEducation = EducationEntry::factory()->create([
        'institution' => 'Educación excluida',
        'program' => 'Programa excluido',
    ]);

    $certification = Certification::factory()->create([
        'name' => 'Certificación incluida',
        'institution' => 'Academia incluida',
        'category' => 'Categoría incluida',
        'code' => 'CODIGO-INCLUIDO',
        'validation_url' => 'https://academy.example.com/validar/incluida',
    ]);
    $excludedCertification = Certification::factory()->create([
        'name' => 'Certificación excluida',
    ]);

    $skill = Skill::factory()->create([
        'name' => 'Habilidad incluida',
        'category' => 'tecnologia',
    ]);
    $excludedSkill = Skill::factory()->create([
        'name' => 'Habilidad excluida',
    ]);

    $project = Project::factory()->create([
        'name' => 'Proyecto incluido',
        'role' => 'Rol incluido',
        'description' => 'Descripción del proyecto incluido.',
        'results' => 'Resultado incluido.',
        'url' => 'https://example.com/proyecto-incluido',
    ]);
    $excludedProject = Project::factory()->create([
        'name' => 'Proyecto excluido',
    ]);

    $profile = CvProfile::factory()->create([
        'name' => 'Alfa Perfil',
        'professional_title' => 'Título propio Alfa',
        'summary' => 'Resumen propio Alfa.',
    ]);

    $profile->workExperiences()->attach($experience, ['relevance' => 'alta']);
    $profile->educationEntries()->attach($education, ['relevance' => 'media']);
    $profile->certifications()->attach($certification, ['relevance' => 'alta']);
    $profile->skills()->attach($skill, ['relevance' => 'alta']);
    $profile->projects()->attach($project, ['relevance' => 'media']);

    $other = CvProfile::factory()->create([
        'name' => 'Beta Perfil',
        'professional_title' => 'Título del otro perfil',
        'summary' => 'Resumen del otro perfil.',
    ]);
    $otherExperience = WorkExperience::factory()->create([
        'position' => 'Contenido del otro perfil',
    ]);
    $other->workExperiences()->attach($otherExperience, ['relevance' => 'alta']);

    $pdf = $this->get("/cv/pdf?profile={$profile->id}")->assertOk()->getContent();
    $text = cvPdfText($pdf);

    expect($text)->toContain('Ada Lovelace')
        ->and($text)->toContain('+54 11 5555 1234')
        ->and($text)->toContain('ada@example.com')
        ->and($text)->toContain('Buenos Aires, Argentina')
        ->and($text)->toContain('Título propio Alfa')
        ->and($text)->toContain('Resumen propio Alfa.')
        ->and($text)->toContain('EXPERIENCIA LABORAL')
        ->and($text)->toContain('Analista funcional incluida')
        ->and($text)->toContain('Acme Incluida')
        ->and($text)->toContain('Descripción incluida.')
        ->and($text)->toContain('Responsabilidad uno.')
        ->and($text)->toContain('Responsabilidad dos.')
        ->and($text)->toContain('Logro incluido.')
        ->and($text)->toContain('EDUCACIÓN')
        ->and($text)->toContain('Sistemas incluidos')
        ->and($text)->toContain('Universidad Incluida')
        ->and($text)->toContain('LICENCIATURA INCLUIDA')
        ->and($text)->not->toContain('Formación incluida.')
        ->and($text)->toContain('CURSOS Y CERTIFICACIONES')
        ->and($text)->toContain('Certificación incluida')
        ->and($text)->toContain('Academia incluida')
        ->and($text)->toContain('Categoría incluida')
        ->and($text)->toContain('CODIGO-INCLUIDO')
        ->and($text)->toContain('HABILIDADES')
        ->and($text)->toContain('Habilidad incluida')
        ->and($text)->toContain('TECNOLOGÍAS')
        ->and($text)->toContain('PROYECTOS')
        ->and($text)->toContain('Proyecto incluido')
        ->and($text)->toContain('Rol incluido')
        ->and($text)->toContain('Descripción del proyecto incluido.')
        ->and($text)->toContain('Resultado incluido.')
        ->and($text)->not->toContain('Experiencia excluida')
        ->and($text)->not->toContain('Empresa excluida')
        ->and($text)->not->toContain('Educación excluida')
        ->and($text)->not->toContain('Certificación excluida')
        ->and($text)->not->toContain('Habilidad excluida')
        ->and($text)->not->toContain('Proyecto excluido')
        ->and($text)->not->toContain('Título del Maestro')
        ->and($text)->not->toContain('Resumen del Maestro')
        ->and($text)->not->toContain('Alfa Perfil')
        ->and($text)->not->toContain('Beta Perfil')
        ->and($text)->not->toContain('Contenido del otro perfil')
        ->and($text)->not->toContain('/profiles/')
        ->and($text)->not->toContain('/experiences')
        ->and($text)->not->toContain('/education')
        ->and($text)->not->toContain('/certifications')
        ->and($text)->not->toContain('/skills')
        ->and($text)->not->toContain('/projects')
        ->and($text)->not->toContain('alta')
        ->and($text)->not->toContain('baja')
        ->and($text)->not->toContain('media');

    expect($education->fresh()->description)->toBe('Formación incluida.');

    $tokens = [
        'Ada Lovelace',
        'Título propio Alfa',
        'Resumen propio Alfa.',
        'EXPERIENCIA LABORAL',
        'Analista funcional incluida',
        'PROYECTOS',
        'Proyecto incluido',
        'CURSOS Y CERTIFICACIONES',
        'Certificación incluida',
        'CONTACTO',
        'EDUCACIÓN',
        'Sistemas incluidos',
        'HABILIDADES',
        'Habilidad incluida',
    ];

    $previous = -1;

    foreach ($tokens as $token) {
        $position = strpos($text, $token);

        expect($position)->not->toBeFalse()
            ->and($position)->toBeGreaterThan($previous);

        $previous = $position;
    }
});

it('redirects with a toast when there are no active profiles or no selection', function () {
    CvProfile::factory()->inactive()->create(['name' => 'Inactivo']);

    $this->get('/cv/pdf?profile=1')
        ->assertRedirect('/cv')
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas('toast.message', 'No hay perfiles activos para exportar.');

    $profile = CvProfile::factory()->create(['name' => 'Activo']);

    foreach (['/cv/pdf', '/cv/pdf?profile='] as $url) {
        $this->get($url)
            ->assertRedirect('/cv')
            ->assertSessionHas('toast.type', 'error')
            ->assertSessionHas('toast.message', 'Seleccioná un perfil para exportar el PDF.');
    }

    expect($profile->id)->toBeGreaterThan(0);
});

it('redirects invalid, unavailable or empty profiles without downloading a pdf', function () {
    CvProfile::factory()->create(['name' => 'Activo']);
    $inactive = CvProfile::factory()->inactive()->create(['name' => 'Inactivo']);

    $invalidQueries = ['?profile=abc', '?profile=0', '?profile=-1', '?profile=%20', '?profile[]=1'];

    foreach ($invalidQueries as $query) {
        $response = $this->get('/cv/pdf'.$query);

        $response->assertRedirect('/cv')
            ->assertSessionHas('toast.type', 'error')
            ->assertSessionHas('toast.message', 'El perfil seleccionado no está disponible. Elegí un perfil activo.');

        expect($response->headers->get('Content-Type'))->not->toContain('application/pdf')
            ->and($response->getContent())->not->toStartWith('%PDF-');
    }

    foreach (["/cv/pdf?profile={$inactive->id}", '/cv/pdf?profile=999999'] as $url) {
        $this->get($url)
            ->assertRedirect('/cv?profile='.($url === "/cv/pdf?profile={$inactive->id}" ? $inactive->id : '999999'))
            ->assertSessionHas('toast.type', 'error')
            ->assertSessionHas('toast.message', 'El perfil seleccionado no está disponible. Elegí un perfil activo.');
    }
});

it('redirects an active profile without data and a profile deactivated after preview', function () {
    Profile::current();

    $empty = CvProfile::factory()->create([
        'name' => 'Sin datos',
        'professional_title' => null,
        'summary' => null,
    ]);

    $this->get("/cv/pdf?profile={$empty->id}")
        ->assertRedirect("/cv?profile={$empty->id}")
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas('toast.message', 'Este perfil no tiene datos para exportar.');

    $active = CvProfile::factory()->create([
        'name' => 'Sigue activo',
        'professional_title' => 'Título activo',
        'summary' => 'Resumen activo.',
    ]);
    $deactivated = CvProfile::factory()->create([
        'name' => 'Se desactiva',
        'professional_title' => 'Título temporal',
        'summary' => 'Resumen temporal.',
    ]);

    $this->get("/cv?profile={$deactivated->id}")->assertOk();
    $deactivated->update(['is_active' => false]);

    $this->get("/cv/pdf?profile={$deactivated->id}")
        ->assertRedirect("/cv?profile={$deactivated->id}")
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas('toast.message', 'El perfil seleccionado no está disponible. Elegí un perfil activo.');

    expect($active->id)->toBeGreaterThan(0);
});

it('handles renderer failures without leaking details or leaving a partial pdf', function () {
    cvPdfMaster(['full_name' => 'Ada Lovelace']);
    $profile = CvProfile::factory()->create([
        'name' => 'Perfil válido',
        'professional_title' => 'Título válido',
        'summary' => 'Resumen válido.',
    ]);

    $this->app->bind(CvPdfRenderer::class, fn () => new class extends CvPdfRenderer
    {
        public function render(array $preview): string
        {
            throw new RuntimeException('Fallo interno del renderizador.');
        }
    });

    $response = $this->get("/cv/pdf?profile={$profile->id}");

    $response->assertRedirect("/cv?profile={$profile->id}")
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas('toast.message', 'No se pudo generar el PDF. Volvé a intentar.');

    expect($response->getContent())
        ->not->toStartWith('%PDF-')
        ->not->toContain('Fallo interno del renderizador.')
        ->and(cvPdfTemporaryEntries())->toBe([]);
});

it('rejects a renderer result that is not a valid pdf', function () {
    cvPdfMaster(['full_name' => 'Ada Lovelace']);
    $profile = CvProfile::factory()->create([
        'name' => 'Perfil válido',
        'professional_title' => 'Título válido',
        'summary' => 'Resumen válido.',
    ]);

    $this->app->bind(CvPdfRenderer::class, fn () => new class extends CvPdfRenderer
    {
        public function render(array $preview): string
        {
            return 'documento roto';
        }
    });

    $response = $this->get("/cv/pdf?profile={$profile->id}");

    $response->assertRedirect("/cv?profile={$profile->id}")
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas('toast.message', 'No se pudo generar el PDF. Volvé a intentar.');

    expect($response->getContent())->not->toStartWith('%PDF-')
        ->and(cvPdfTemporaryEntries())->toBe([]);
});

it('handles validator failures and allows a later retry', function () {
    cvPdfMaster(['full_name' => 'Ada Lovelace']);
    $profile = CvProfile::factory()->create([
        'name' => 'Perfil válido',
        'professional_title' => 'Título válido',
        'summary' => 'Resumen válido.',
    ]);

    $this->app->singleton(CvPdfValidator::class, fn () => new class extends CvPdfValidator
    {
        private bool $failed = false;

        public function isValid(string $pdf): bool
        {
            if (! $this->failed) {
                $this->failed = true;

                return false;
            }

            return parent::isValid($pdf);
        }
    });

    $this->get("/cv/pdf?profile={$profile->id}")
        ->assertRedirect("/cv?profile={$profile->id}")
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas('toast.message', 'No se pudo generar el PDF. Volvé a intentar.');

    expect(cvPdfTemporaryEntries())->toBe([]);

    $retry = $this->get("/cv/pdf?profile={$profile->id}");

    $retry->assertOk();
    expect($retry->headers->get('Content-Type'))->toContain('application/pdf')
        ->and($retry->getContent())->toStartWith('%PDF-')
        ->and(cvPdfTemporaryEntries())->toBe([]);
});

it('does not persist or modify data and leaves no recoverable file after success', function () {
    $master = cvPdfMaster([
        'full_name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);
    $experience = WorkExperience::factory()->create(['position' => 'Experiencia medida']);
    DocumentModel::factory()->create();

    $profileA = CvProfile::factory()->create([
        'name' => 'Perfil A',
        'professional_title' => 'Título A',
        'summary' => 'Resumen A.',
    ]);
    $profileB = CvProfile::factory()->create([
        'name' => 'Perfil B',
        'professional_title' => 'Título B',
        'summary' => 'Resumen B.',
    ]);

    $profileA->workExperiences()->attach($experience, ['relevance' => 'alta']);

    $counts = [
        'profiles' => Profile::count(),
        'cv_profiles' => CvProfile::count(),
        'work_experiences' => WorkExperience::count(),
        'documents' => DocumentModel::count(),
        'work_pivot' => DB::table('cv_profile_work_experience')->count(),
        'education_pivot' => DB::table('cv_profile_education_entry')->count(),
        'certification_pivot' => DB::table('cv_profile_certification')->count(),
        'skill_pivot' => DB::table('cv_profile_skill')->count(),
        'project_pivot' => DB::table('cv_profile_project')->count(),
    ];
    $masterUpdatedAt = $master->updated_at;
    $profileAUpdatedAt = $profileA->updated_at;
    $profileBUpdatedAt = $profileB->updated_at;
    $experienceUpdatedAt = $experience->updated_at;

    $this->get("/cv/pdf?profile={$profileA->id}")->assertOk();

    expect(Profile::count())->toBe($counts['profiles'])
        ->and(CvProfile::count())->toBe($counts['cv_profiles'])
        ->and(WorkExperience::count())->toBe($counts['work_experiences'])
        ->and(DocumentModel::count())->toBe($counts['documents'])
        ->and(DB::table('cv_profile_work_experience')->count())->toBe($counts['work_pivot'])
        ->and(DB::table('cv_profile_education_entry')->count())->toBe($counts['education_pivot'])
        ->and(DB::table('cv_profile_certification')->count())->toBe($counts['certification_pivot'])
        ->and(DB::table('cv_profile_skill')->count())->toBe($counts['skill_pivot'])
        ->and(DB::table('cv_profile_project')->count())->toBe($counts['project_pivot'])
        ->and($master->fresh()->updated_at->equalTo($masterUpdatedAt))->toBeTrue()
        ->and($profileA->fresh()->updated_at->equalTo($profileAUpdatedAt))->toBeTrue()
        ->and($profileB->fresh()->updated_at->equalTo($profileBUpdatedAt))->toBeTrue()
        ->and($experience->fresh()->updated_at->equalTo($experienceUpdatedAt))->toBeTrue()
        ->and(cvPdfTemporaryEntries())->toBe([])
        ->and(cvPdfGeneratedFiles())->toBe([]);
});

it('does not modify data or leave recoverable files after a controlled failure', function () {
    $master = cvPdfMaster(['full_name' => 'Ada Lovelace']);
    $profile = CvProfile::factory()->create([
        'name' => 'Perfil con fallo',
        'professional_title' => 'Título con fallo',
        'summary' => 'Resumen con fallo.',
    ]);

    $counts = [
        'profiles' => Profile::count(),
        'cv_profiles' => CvProfile::count(),
    ];
    $masterUpdatedAt = $master->updated_at;
    $profileUpdatedAt = $profile->updated_at;

    $this->app->bind(CvPdfValidator::class, fn () => new class extends CvPdfValidator
    {
        public function isValid(string $pdf): bool
        {
            return false;
        }
    });

    $this->get("/cv/pdf?profile={$profile->id}")->assertRedirect("/cv?profile={$profile->id}");

    expect(Profile::count())->toBe($counts['profiles'])
        ->and(CvProfile::count())->toBe($counts['cv_profiles'])
        ->and($master->fresh()->updated_at->equalTo($masterUpdatedAt))->toBeTrue()
        ->and($profile->fresh()->updated_at->equalTo($profileUpdatedAt))->toBeTrue()
        ->and(cvPdfTemporaryEntries())->toBe([])
        ->and(cvPdfGeneratedFiles())->toBe([]);
});

it('only accepts GET on the pdf route', function () {
    foreach (['post', 'put', 'patch', 'delete'] as $method) {
        $this->{$method}('/cv/pdf')->assertStatus(405);
    }
});

it('generates a safe pdf without encryption scripts forms attachments launches or internal metadata', function () {
    cvPdfMaster([
        'full_name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'phone' => '+54 11 5555 1234',
    ]);

    $other = CvProfile::factory()->create([
        'name' => 'Otro perfil secreto',
        'professional_title' => 'Título ajeno',
        'summary' => 'ContenidoAjenoXYZ.',
    ]);
    $otherExperience = WorkExperience::factory()->create(['position' => 'ContenidoAjenoXYZ']);
    $other->workExperiences()->attach($otherExperience, ['relevance' => 'alta']);

    $profile = CvProfile::factory()->create([
        'name' => 'PerfilConfidencialXYZ',
        'professional_title' => 'Título visible',
        'summary' => 'Resumen visible.',
    ]);

    $pdf = $this->get("/cv/pdf?profile={$profile->id}")->assertOk()->getContent();
    $document = cvPdfDocument($pdf);
    $text = cvPdfText($pdf);
    $metadata = (string) json_encode($document->getDetails());

    expect($pdf)->not->toContain('/Encrypt')
        ->and($pdf)->not->toContain('/JavaScript')
        ->and($pdf)->not->toContain('/AcroForm')
        ->and($pdf)->not->toContain('/EmbeddedFile')
        ->and($pdf)->not->toContain('/Launch')
        ->and($text)->not->toContain('PerfilConfidencialXYZ')
        ->and($text)->not->toContain('ContenidoAjenoXYZ')
        ->and($text)->not->toContain('/profiles/')
        ->and($metadata)->not->toContain('PerfilConfidencialXYZ')
        ->and($metadata)->not->toContain('ContenidoAjenoXYZ')
        ->and($metadata)->not->toContain('relevance')
        ->and($metadata)->not->toContain('alta')
        ->and($metadata)->not->toContain('media')
        ->and($metadata)->not->toContain('baja')
        ->and($metadata)->not->toContain('/profiles/')
        ->and($metadata)->not->toContain('/experiences');
});

it('keeps safe visible links and external annotations without internal corrections', function () {
    $longUrl = 'https://example.com/'.str_repeat('b', 180);

    $master = cvPdfMaster([
        'full_name' => 'Ada Lovelace',
        'phone' => '+54 11 5555 1234',
        'email' => 'ada@example.com',
        'linkedin_url' => 'https://www.linkedin.com/in/ada',
        'github_url' => 'https://github.com/ada',
        'website_url' => 'https://ada.example.com',
    ]);

    $master->links()->create(['label' => 'Blog', 'url' => 'https://blog.example.com']);
    $master->links()->create(['label' => 'Contacto directo', 'url' => 'mailto:otro@example.com']);
    $master->links()->create(['label' => 'Llamadas', 'url' => 'tel:+54 11 4444 0000']);
    $master->links()->create(['label' => 'Enlace inseguro', 'url' => 'javascript:alert(1)']);

    $certification = Certification::factory()->create([
        'name' => 'Certificación enlazada',
        'validation_url' => 'https://academy.example.com/validar/123',
    ]);
    $safeProject = Project::factory()->create([
        'name' => 'Proyecto seguro',
        'url' => $longUrl,
    ]);
    $unsafeProject = Project::factory()->create([
        'name' => 'Proyecto inseguro',
        'url' => 'javascript:alert(2)',
    ]);

    $profile = CvProfile::factory()->create([
        'name' => 'Perfil con enlaces',
        'professional_title' => 'Título con enlaces',
        'summary' => 'Resumen con enlaces.',
    ]);

    $profile->certifications()->attach($certification, ['relevance' => 'alta']);
    $profile->projects()->attach($safeProject, ['relevance' => 'alta']);
    $profile->projects()->attach($unsafeProject, ['relevance' => 'media']);

    $pdf = $this->get("/cv/pdf?profile={$profile->id}")->assertOk()->getContent();
    $document = cvPdfDocument($pdf);
    $text = cvPdfText($pdf);
    $annotations = cvPdfUriAnnotations($document);

    expect($text)->toContain('+54 11 5555 1234')
        ->and($text)->toContain('ada@example.com')
        ->and($text)->toContain('LinkedIn')
        ->and($text)->toContain('GitHub')
        ->and($text)->toContain('Sitio web')
        ->and($text)->toContain('Blog')
        ->and($text)->toContain('Contacto directo')
        ->and($text)->toContain('Llamadas')
        ->and($text)->toContain('Enlace inseguro')
        ->and($text)->toContain('VALIDAR')
        ->and($text)->toContain('Proyecto seguro')
        ->and($text)->toContain('Proyecto inseguro')
        ->and(substr_count($text, 'VER PROYECTO'))->toBe(1)
        ->and($text)->not->toContain($longUrl)
        ->and($annotations)->toContain('tel:+54 11 5555 1234')
        ->and($annotations)->toContain('mailto:ada@example.com')
        ->and($annotations)->toContain('https://www.linkedin.com/in/ada')
        ->and($annotations)->toContain('https://github.com/ada')
        ->and($annotations)->toContain('https://ada.example.com/')
        ->and($annotations)->toContain('https://blog.example.com/')
        ->and($annotations)->toContain('mailto:otro@example.com')
        ->and($annotations)->toContain('tel:+541144440000')
        ->and($annotations)->toContain('https://academy.example.com/validar/123')
        ->and($annotations)->toContain($longUrl)
        ->and(array_filter($annotations, fn ($annotation) => $annotation === $longUrl))->toHaveCount(1)
        ->and(array_filter($annotations, fn ($annotation) => str_contains($annotation, 'javascript')))->toBe([]);

    foreach ($annotations as $annotation) {
        expect($annotation)->not->toMatch('/javascript:/i')
            ->and($annotation)->not->toContain('/profiles')
            ->and($annotation)->not->toContain('/profile')
            ->and($annotation)->not->toContain('/experiences')
            ->and($annotation)->not->toContain('/education')
            ->and($annotation)->not->toContain('/certifications')
            ->and($annotation)->not->toContain('/skills')
            ->and($annotation)->not->toContain('/projects');
    }
});

it('does not activate unsafe urls and keeps only additional link labels', function () {
    $master = cvPdfMaster([
        'full_name' => 'Ada Lovelace',
        'linkedin_url' => 'javascript:alert(10)',
        'github_url' => 'javascript:alert(11)',
        'website_url' => 'javascript:alert(12)',
    ]);

    $master->links()->create([
        'label' => 'Enlace adicional inseguro',
        'url' => 'javascript:alert(13)',
    ]);
    $master->links()->create([
        'label' => 'Enlace data inseguro',
        'url' => 'data:text/html,<script>alert(14)</script>',
    ]);
    $master->links()->create([
        'label' => 'Enlace file inseguro',
        'url' => 'file:///etc/passwd',
    ]);

    $certification = Certification::factory()->create([
        'name' => 'Certificación insegura',
        'validation_url' => 'javascript:alert(15)',
    ]);
    $project = Project::factory()->create([
        'name' => 'Proyecto inseguro',
        'url' => 'javascript:alert(16)',
    ]);

    $profile = CvProfile::factory()->create([
        'name' => 'Perfil sin enlaces seguros',
        'professional_title' => 'Título sin enlaces seguros',
        'summary' => 'Resumen sin enlaces seguros.',
    ]);

    $profile->certifications()->attach($certification, ['relevance' => 'alta']);
    $profile->projects()->attach($project, ['relevance' => 'alta']);

    $pdf = $this->get("/cv/pdf?profile={$profile->id}")->assertOk()->getContent();
    $text = cvPdfText($pdf);
    $annotations = cvPdfUriAnnotations(cvPdfDocument($pdf));

    expect($text)->toContain('Certificación insegura')
        ->and($text)->toContain('Proyecto inseguro')
        ->and($text)->toContain('Enlace adicional inseguro')
        ->and($text)->toContain('Enlace data inseguro')
        ->and($text)->toContain('Enlace file inseguro')
        ->and($text)->not->toContain('LinkedIn')
        ->and($text)->not->toContain('GitHub')
        ->and($text)->not->toContain('Sitio web')
        ->and($text)->not->toContain('VALIDAR')
        ->and($text)->not->toContain('VER PROYECTO')
        ->and($text)->not->toContain('javascript:')
        ->and($text)->not->toContain('data:text/html')
        ->and($text)->not->toContain('file:///etc/passwd')
        ->and($annotations)->toBe([]);
});

it('paginates long CVs without losing or duplicating content', function () {
    cvPdfMaster([
        'full_name' => 'Ada Ñandú — José Ángel',
        'email' => 'ada@example.com',
    ]);

    $profile = CvProfile::factory()->create([
        'name' => 'Perfil extenso',
        'professional_title' => 'Título con Áéíóú y ñ',
        'summary' => 'Resumen con Áéíóú ñandú — ✓ “comillas” (paréntesis) [corchetes].',
    ]);

    $longToken = 'https://example.com/'.str_repeat('a', 180);
    $tokens = [];

    for ($index = 1; $index <= 28; $index++) {
        $token = 'Entrada extensa número '.str_pad((string) $index, 2, '0', STR_PAD_LEFT).' fin';
        $tokens[] = $token;

        $experience = WorkExperience::factory()->create([
            'position' => $token,
            'company' => 'Empresa '.$index,
            'start_year' => 2000 + $index,
            'start_month' => 1,
            'end_year' => null,
            'end_month' => null,
            'is_current' => true,
            'description' => str_repeat('Descripción extensa con acentos, eñes y signos. ', 4),
            'responsibilities' => "Responsabilidad uno.\nResponsabilidad dos.\nResponsabilidad tres.",
            'achievements' => 'Logro '.$index.'.',
        ]);

        if ($index === 14) {
            $experience->update([
                'description' => $experience->description.' Cadena sin espacios: '.$longToken.' fin.',
            ]);
        }

        $profile->workExperiences()->attach($experience, [
            'relevance' => $index % 2 === 0 ? 'alta' : 'media',
        ]);
    }

    $first = $this->get("/cv/pdf?profile={$profile->id}")->assertOk()->getContent();
    $document = cvPdfDocument($first);
    $text = cvPdfText($first);
    $compact = (string) preg_replace('/\s+/u', '', $text);

    expect(count($document->getPages()))->toBeGreaterThan(1)
        ->and($text)->toContain('Ada Ñandú — José Ángel')
        ->and($text)->toContain('Título con Áéíóú y ñ')
        ->and($text)->toContain('Resumen con Áéíóú ñandú — ✓ “comillas” (paréntesis) [corchetes].')
        ->and($compact)->toContain($longToken);

    foreach ($document->getPages() as $page) {
        expect(trim($page->getText()))->not->toBe('');
    }

    foreach ($tokens as $token) {
        expect(substr_count($compact, str_replace(' ', '', $token)))->toBe(1);
    }

    $second = $this->get("/cv/pdf?profile={$profile->id}")->assertOk()->getContent();
    $secondDocument = cvPdfDocument($second);

    expect(count($secondDocument->getPages()))->toBe(count($document->getPages()))
        ->and(cvPdfText($second))->toBe($text);
});

it('paginates a sidebar longer than the main column without repetition or blank pages', function () {
    cvPdfMaster([
        'full_name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    $profile = CvProfile::factory()->create([
        'name' => 'Perfil con barra extensa',
        'professional_title' => 'Analista de Sistemas',
        'summary' => 'Resumen breve de la columna principal.',
    ]);

    $tokens = [];

    for ($index = 1; $index <= 20; $index++) {
        $token = 'Educación extensa '.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
        $tokens[] = $token;

        $education = EducationEntry::factory()->create([
            'institution' => 'Universidad '.$index,
            'program' => $token,
            'degree_level' => 'Licenciatura',
            'description' => 'Formación complementaria de la barra lateral.',
        ]);

        $profile->educationEntries()->attach($education, ['relevance' => 'alta']);
    }

    for ($index = 1; $index <= 50; $index++) {
        $token = 'Habilidad extensa '.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
        $tokens[] = $token;

        $skill = Skill::factory()->create([
            'name' => $token,
            'category' => 'tecnologia',
        ]);

        $profile->skills()->attach($skill, ['relevance' => 'alta']);
    }

    $pdf = $this->get("/cv/pdf?profile={$profile->id}")->assertOk()->getContent();
    $document = cvPdfDocument($pdf);
    $text = cvPdfText($pdf);
    $compact = (string) preg_replace('/\s+/u', '', $text);

    expect(count($document->getPages()))->toBeGreaterThan(1)
        ->and($text)->toContain('Resumen breve de la columna principal.')
        ->and($text)->toContain('EDUCACIÓN')
        ->and($text)->toContain('HABILIDADES');

    foreach ($document->getPages() as $page) {
        expect(trim($page->getText()))->not->toBe('');
    }

    foreach ($tokens as $token) {
        expect(substr_count($compact, str_replace(' ', '', $token)))->toBe(1);
    }
});

it('continues a single long professional entry without losing its content', function () {
    cvPdfMaster(['full_name' => 'Ada Lovelace']);

    $profile = CvProfile::factory()->create([
        'name' => 'Perfil con entrada larga',
        'professional_title' => 'Analista de Sistemas',
        'summary' => 'Resumen de prueba.',
    ]);

    $endToken = 'FIN-DE-LA-ENTRADA-LARGA';
    $longDescription = str_repeat(
        'Descripción extensa con acentos, eñes y signos. ',
        260,
    ).$endToken;

    $experience = WorkExperience::factory()->create([
        'position' => 'Entrada profesional muy extensa',
        'company' => 'Empresa extensa',
        'description' => $longDescription,
    ]);

    $profile->workExperiences()->attach($experience, ['relevance' => 'alta']);

    $pdf = $this->get("/cv/pdf?profile={$profile->id}")->assertOk()->getContent();
    $document = cvPdfDocument($pdf);
    $text = cvPdfText($pdf);
    $compact = (string) preg_replace('/\s+/u', '', $text);

    expect(count($document->getPages()))->toBeGreaterThan(1)
        ->and($text)->toContain('Entrada profesional muy extensa')
        ->and(substr_count($compact, $endToken))->toBe(1)
        ->and(trim($document->getPages()[0]->getText()))
        ->toContain('Entrada profesional muy extensa');

    foreach ($document->getPages() as $page) {
        expect(trim($page->getText()))->not->toBe('');
    }
});
