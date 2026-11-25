<?php

use App\Support\CvPdfChrome;
use App\Support\CvPdfChromeCheck;
use App\Support\CvPdfChromeResolution;
use App\Support\CvPdfChromeValidator;

function cvPdfChromeTestExecutable(): string
{
    $path = tempnam(sys_get_temp_dir(), 'cv-pdf-chrome-');

    if ($path === false) {
        throw new RuntimeException('No se pudo crear el ejecutable temporal de prueba.');
    }

    file_put_contents($path, "#!/bin/sh\nexit 0\n");
    chmod($path, 0755);

    register_shutdown_function(static fn () => @unlink($path));

    return $path;
}

it('uses the configured chrome path when it is an executable', function () {
    $path = cvPdfChromeTestExecutable();
    config(['services.browsershot.chrome_path' => $path]);

    $resolution = app(CvPdfChrome::class)->resolve();

    expect($resolution->isAvailable())->toBeTrue()
        ->and($resolution->path)->toBe($path)
        ->and($resolution->source)->toBe(CvPdfChrome::SOURCE_CONFIGURED);
});

it('rejects an invalid configured chrome path without a silent fallback', function () {
    config(['services.browsershot.chrome_path' => '/ruta/que-no-existe/Google Chrome']);

    $resolution = app(CvPdfChrome::class)->resolve();

    expect($resolution->isAvailable())->toBeFalse()
        ->and($resolution->error)->toContain('BROWSERSHOT_CHROME_PATH')
        ->and($resolution->error)->toContain('Corregí la ruta');
});

it('prefers the chrome resolved by puppeteer over other copies', function () {
    $puppeteer = cvPdfChromeTestExecutable();
    $system = cvPdfChromeTestExecutable();
    $playwright = cvPdfChromeTestExecutable();

    config(['services.browsershot.chrome_path' => null]);

    $resolver = new class($puppeteer, $system, $playwright) extends CvPdfChrome
    {
        public function __construct(
            private readonly string $puppeteer,
            private readonly string $system,
            private readonly string $playwright,
        ) {}

        protected function puppeteerPath(): ?string
        {
            return $this->puppeteer;
        }

        protected function systemPath(): ?string
        {
            return $this->system;
        }

        protected function playwrightPath(): ?string
        {
            return $this->playwright;
        }
    };

    $resolution = $resolver->resolve();

    expect($resolution->path)->toBe($puppeteer)
        ->and($resolution->source)->toBe(CvPdfChrome::SOURCE_PUPPETEER);
});

it('falls back to a system chrome when puppeteer has no browser', function () {
    $system = cvPdfChromeTestExecutable();
    $playwright = cvPdfChromeTestExecutable();

    config(['services.browsershot.chrome_path' => null]);

    $resolver = new class($system, $playwright) extends CvPdfChrome
    {
        public function __construct(
            private readonly string $system,
            private readonly string $playwright,
        ) {}

        protected function puppeteerPath(): ?string
        {
            return null;
        }

        protected function systemPath(): ?string
        {
            return $this->system;
        }

        protected function playwrightPath(): ?string
        {
            return $this->playwright;
        }
    };

    $resolution = $resolver->resolve();

    expect($resolution->path)->toBe($system)
        ->and($resolution->source)->toBe(CvPdfChrome::SOURCE_SYSTEM);
});

it('returns an actionable message when no chrome is available', function () {
    config(['services.browsershot.chrome_path' => null]);

    $resolver = new class extends CvPdfChrome
    {
        protected function puppeteerPath(): ?string
        {
            return null;
        }

        protected function systemPath(): ?string
        {
            return null;
        }

        protected function playwrightPath(): ?string
        {
            return null;
        }
    };

    $resolution = $resolver->resolve();

    expect($resolution->isAvailable())->toBeFalse()
        ->and($resolution->error)->toContain('pnpm exec puppeteer browsers install chrome')
        ->and($resolution->error)->toContain('BROWSERSHOT_CHROME_PATH')
        ->and($resolution->error)->toContain('chrome-headless-shell');
});

it('fails the validation command with an actionable message when chrome is missing', function () {
    $this->app->instance(CvPdfChrome::class, new class extends CvPdfChrome
    {
        public function resolve(): CvPdfChromeResolution
        {
            return new CvPdfChromeResolution(null, error: $this->missingMessage());
        }
    });

    $this->artisan('cv:pdf:chrome')
        ->expectsOutputToContain('pnpm exec puppeteer browsers install chrome')
        ->assertExitCode(1);

    $this->artisan('cv:pdf:chrome --warn-only')
        ->expectsOutputToContain('pnpm exec puppeteer browsers install chrome')
        ->assertExitCode(0);
});

it('reports a successful test pdf from the validation command', function () {
    $path = cvPdfChromeTestExecutable();

    $this->app->instance(CvPdfChrome::class, new class($path) extends CvPdfChrome
    {
        public function __construct(private readonly string $path) {}

        public function resolve(): CvPdfChromeResolution
        {
            return new CvPdfChromeResolution($this->path, CvPdfChrome::SOURCE_CONFIGURED);
        }
    });

    $this->app->instance(CvPdfChromeValidator::class, new class extends CvPdfChromeValidator
    {
        public function check(string $chromePath): CvPdfChromeCheck
        {
            return new CvPdfChromeCheck(ok: true, bytes: 2048);
        }
    });

    $this->artisan('cv:pdf:chrome')
        ->expectsOutputToContain('PDF de prueba generado correctamente')
        ->assertExitCode(0);
});

it('generates a valid test pdf with the resolved chrome', function () {
    $check = app(CvPdfChromeValidator::class)->check(cvPdfChromePath());

    expect($check->ok)->toBeTrue($check->error ?? '');
});
