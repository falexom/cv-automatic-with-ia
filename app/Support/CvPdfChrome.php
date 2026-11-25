<?php

namespace App\Support;

use Symfony\Component\Process\Process;
use Throwable;

class CvPdfChrome
{
    public const SOURCE_CONFIGURED = 'BROWSERSHOT_CHROME_PATH';

    public const SOURCE_PUPPETEER = 'Puppeteer';

    public const SOURCE_SYSTEM = 'sistema';

    public const SOURCE_PLAYWRIGHT = 'Playwright';

    public function resolve(): CvPdfChromeResolution
    {
        $configured = $this->configuredPath();

        if ($configured !== null) {
            if (! $this->isUsable($configured)) {
                return new CvPdfChromeResolution(
                    path: null,
                    error: implode("\n", [
                        'BROWSERSHOT_CHROME_PATH apunta a un ejecutable inexistente o no ejecutable:',
                        '  '.$configured,
                        '',
                        'Corregí la ruta o quitá la variable para reutilizar el Chrome de Puppeteer.',
                        'Si no hay otro Chrome disponible, instalalo una sola vez con:',
                        '  pnpm exec puppeteer browsers install chrome',
                    ]),
                );
            }

            return new CvPdfChromeResolution($configured, self::SOURCE_CONFIGURED);
        }

        foreach ($this->candidates() as [$path, $source]) {
            if ($path !== null && $path !== '' && $this->isUsable($path)) {
                return new CvPdfChromeResolution($path, $source);
            }
        }

        return new CvPdfChromeResolution(
            path: null,
            error: $this->missingMessage(),
        );
    }

    public function missingMessage(): string
    {
        return implode("\n", [
            'No se encontró un Chrome completo para exportar el CV a PDF.',
            '',
            'Opciones:',
            '  1. Reutilizar un Chrome ya instalado:',
            '     BROWSERSHOT_CHROME_PATH="/ruta/al/Chrome" php artisan cv:pdf:chrome',
            '  2. Instalarlo una sola vez con el comando explícito de Puppeteer:',
            '     pnpm exec puppeteer browsers install chrome',
            '',
            'Después, validá de nuevo con: php artisan cv:pdf:chrome',
            'No uses chrome-headless-shell: newHeadless() requiere Chrome completo.',
        ]);
    }

    public function isUsable(string $path): bool
    {
        return is_file($path) && is_executable($path);
    }

    /**
     * @return array<int, array{0: ?string, 1: string}>
     */
    protected function candidates(): array
    {
        return [
            [$this->puppeteerPath(), self::SOURCE_PUPPETEER],
            [$this->systemPath(), self::SOURCE_SYSTEM],
            [$this->playwrightPath(), self::SOURCE_PLAYWRIGHT],
        ];
    }

    protected function configuredPath(): ?string
    {
        $configured = config('services.browsershot.chrome_path');

        if (! is_string($configured)) {
            return null;
        }

        $configured = trim($configured);

        return $configured !== '' ? $configured : null;
    }

    /**
     * Ask Puppeteer for the Chrome it would launch. This only resolves the
     * cached path; it never downloads or installs a browser.
     */
    protected function puppeteerPath(): ?string
    {
        try {
            $process = new Process([
                'node',
                '-e',
                'const p = require("puppeteer"); Promise.resolve(p.executablePath()).then((value) => process.stdout.write(String(value))).catch((error) => { console.error(error instanceof Error ? error.message : String(error)); process.exit(1); });',
            ], base_path(), null, null, 10);

            $process->run();

            if (! $process->isSuccessful()) {
                return null;
            }

            $path = trim($process->getOutput());

            return $path !== '' ? $path : null;
        } catch (Throwable) {
            return null;
        }
    }

    protected function systemPath(): ?string
    {
        foreach ($this->systemCandidates() as $candidate) {
            if ($this->isUsable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function systemCandidates(): array
    {
        $home = getenv('HOME') ?: '';

        return [
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/Applications/Chromium.app/Contents/MacOS/Chromium',
            $home.'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
        ];
    }

    /**
     * Playwright owns this cache. CV Automático only reuses a full Chrome that
     * is already there; it never installs into or removes from it.
     */
    protected function playwrightPath(): ?string
    {
        foreach ($this->playwrightCandidates() as $candidate) {
            if ($this->isUsable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function playwrightCandidates(): array
    {
        $home = getenv('HOME') ?: '';

        $candidates = array_merge(
            glob($home.'/Library/Caches/ms-playwright/chromium-*/chrome-mac-arm64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing') ?: [],
            glob($home.'/Library/Caches/ms-playwright/chromium-*/chrome-mac/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing') ?: [],
            glob($home.'/.cache/ms-playwright/chromium-*/chrome-linux64/chrome') ?: [],
        );

        rsort($candidates);

        return $candidates;
    }
}
