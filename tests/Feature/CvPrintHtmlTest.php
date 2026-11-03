<?php

use App\Models\CvProfile;
use App\Models\Profile;
use App\Models\WorkExperience;
use App\Support\CvPdfRenderer;
use App\Support\CvPreviewContent;
use App\Support\ProfilePhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Ssr\HttpGateway;

beforeAll(fn () => cvPdfStartSsr());

afterAll(fn () => cvPdfStopSsr());

beforeEach(function () {
    $this->withVite();

    app(HttpGateway::class)->disable(false);
});

it('renders the real CvPreview as a self-contained print document', function () {
    $master = Profile::current();
    $master->update([
        'full_name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'phone' => '+54 11 5555 1234',
    ]);

    $experience = WorkExperience::factory()->create([
        'position' => 'Analista funcional',
        'company' => 'Acme',
        'description' => 'Descripción incluida.',
    ]);

    $profile = CvProfile::factory()->create([
        'professional_title' => 'Analista de Sistemas',
        'summary' => 'Resumen propio.',
    ]);

    $profile->workExperiences()->attach($experience, ['relevance' => 'alta']);

    $preview = app(CvPreviewContent::class)->build($profile);
    $html = app(CvPdfRenderer::class)->html($preview);

    expect($html)
        ->toContain('<article')
        ->toContain('aria-labelledby="cv-preview-title"')
        ->toContain('Ada Lovelace')
        ->toContain('Analista de Sistemas')
        ->toContain('Experiencia laboral')
        ->toContain('Analista funcional')
        ->toContain('Descripción incluida.')
        ->toContain('@page')
        ->toContain('size: A4')
        ->toContain('margin: 0')
        ->toContain('cv-header-band')
        ->toContain('cv-header-caption')
        ->toContain('<caption')
        ->toContain('cv-layout')
        ->toContain('cv-print-sidebar-bg')
        ->toContain('cv-main')
        ->toContain('cv-sidebar')
        ->toContain('cv-col-main')
        ->toContain('cv-col-side')
        ->toContain('<thead>')
        ->toContain('<tfoot>')
        ->toContain('@font-face')
        ->toContain('Instrument Sans')
        ->toContain('data:font/woff2;base64,')
        ->toContain('max-width: none !important')
        ->toContain('padding: 0 !important')
        ->toContain('border: 0 !important')
        ->toContain('box-shadow: none !important')
        ->toContain('break-after: avoid')
        ->toContain('cv-project-intro')
        ->toContain('cv-project-desc p')
        ->toContain('cv-project--long .cv-project-desc p')
        ->toContain('orphans: 2')
        ->toContain('widows: 2')
        ->toContain('overflow-wrap: anywhere')
        ->not->toContain('data-page=')
        ->not->toContain('<script')
        ->not->toContain('source_links')
        ->not->toContain('/profiles/')
        ->not->toContain('/experiences')
        ->not->toContain('<nav')
        ->not->toContain('<aside')
        ->not->toContain('data-sidebar')
        ->not->toContain('<button')
        ->not->toContain('<link')
        ->not->toContain('<img')
        ->not->toContain('<iframe')
        ->not->toContain('<object')
        ->not->toContain('<embed')
        ->not->toContain('<form')
        ->not->toContain('url(http')
        ->not->toContain('fonts.bunny.net');
});

it('embeds the current photo and crop in the self-contained print document', function () {
    Storage::fake('local');

    $master = Profile::current();
    $stored = app(ProfilePhoto::class)->store(
        UploadedFile::fake()->image('foto.jpg', 400, 300),
    );

    $master->update([
        'full_name' => 'Ada Lovelace',
        'photo_path' => $stored['path'],
        'photo_mime' => $stored['mime'],
        'photo_focus_x' => 40,
        'photo_focus_y' => 60,
        'photo_zoom' => 150,
    ]);

    $profile = CvProfile::factory()->create([
        'professional_title' => 'Analista de Sistemas',
        'summary' => 'Resumen propio.',
    ]);

    $preview = app(CvPreviewContent::class)->build($profile);
    $html = app(CvPdfRenderer::class)->html($preview);

    expect($preview['personal_data']['photo'])
        ->not->toBeNull()
        ->and($html)
        ->toContain('<img')
        ->toContain('alt="Fotografía profesional"')
        ->toContain('data:image/jpeg;base64,')
        ->toContain('h-[36mm]')
        ->toContain('w-[36mm]')
        ->toContain('object-position:40% 60%')
        ->toContain('transform:scale(1.5)')
        ->toContain('transform-origin:40% 60%')
        ->not->toContain('profile-photos/');
});

it('fails in a controlled way when Inertia SSR is not available', function () {
    $master = Profile::current();
    $master->update(['full_name' => 'Ada Lovelace']);

    $profile = CvProfile::factory()->create([
        'professional_title' => 'Analista de Sistemas',
    ]);

    $preview = app(CvPreviewContent::class)->build($profile);

    config(['inertia.ssr.enabled' => false]);
    app(HttpGateway::class)->disable(true);

    expect(fn () => app(CvPdfRenderer::class)->html($preview))
        ->toThrow(RuntimeException::class);
});
