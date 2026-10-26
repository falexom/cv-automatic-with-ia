<?php

use App\Models\CvProfile;
use App\Models\Profile;
use App\Models\WorkExperience;
use App\Support\CvPdfRenderer;
use App\Support\CvPreviewContent;
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
        ->toContain('margin: 18mm')
        ->toContain('@font-face')
        ->toContain('Instrument Sans')
        ->toContain('data:font/woff2;base64,')
        ->toContain('max-width: none !important')
        ->toContain('padding: 0 !important')
        ->toContain('border: 0 !important')
        ->toContain('box-shadow: none !important')
        ->toContain('break-after: avoid')
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
