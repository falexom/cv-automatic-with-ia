<?php

namespace App\Support;

use FilesystemIterator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Ssr\SsrState;
use RuntimeException;
use Spatie\Browsershot\Browsershot;
use SplFileInfo;

class CvPdfRenderer
{
    /**
     * Render the self-contained print HTML for the preview payload.
     *
     * @param  array<string, mixed>  $preview
     */
    public function html(array $preview): string
    {
        $response = Inertia::render('Cv/Print', [
            'preview' => $this->printPayload($preview),
        ])
            ->rootView('cv.print')
            ->toResponse(request());

        if (app(SsrState::class)->response === null) {
            throw new RuntimeException('The Inertia SSR response is not available.');
        }

        $html = (string) $response->getContent();

        if (! str_contains($html, '<article')) {
            throw new RuntimeException('The Inertia SSR response does not contain the CV preview.');
        }

        return preg_replace(
            '~<script data-page="app" type="application/json">.*?</script>~s',
            '',
            $html,
        ) ?? $html;
    }

    /**
     * Render the preview payload as an A4 PDF string.
     *
     * @param  array<string, mixed>  $preview
     */
    public function render(array $preview): string
    {
        $directory = storage_path('app/private/cv-pdf/'.Str::uuid()->toString());

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the PDF working directory.');
        }

        try {
            $browsershot = Browsershot::html($this->html($preview))
                ->newHeadless()
                ->format('A4')
                ->setOption('preferCSSPageSize', true)
                ->margins(0, 0, 0, 0)
                ->showBackground()
                ->disableJavascript()
                ->emulateMedia('print')
                ->showBrowserHeaderAndFooter()
                ->headerHtml('<div></div>')
                ->footerHtml($this->footerHtml())
                ->blockUrls(['http://', 'https://'])
                // Keep the headless export on a disposable profile and use
                // Chrome's mock keychain instead of macOS Keychain access.
                ->addChromiumArguments(['use-mock-keychain'])
                ->userDataDir($directory.'/chrome-profile')
                ->timeout((int) config('services.browsershot.timeout', 30))
                ->setNodeModulePath(base_path('node_modules'))
                ->setCustomTempPath($directory);

            if ($chromePath = $this->chromePath()) {
                $browsershot->setChromePath($chromePath);
            }

            return $browsershot->pdf();
        } finally {
            $this->removeDirectory($directory);
        }
    }

    private function footerHtml(): string
    {
        $fontPath = resource_path('fonts/instrument-sans/instrument-sans-400.woff2');
        $fontFace = '';

        if (is_file($fontPath)) {
            $fontData = base64_encode((string) file_get_contents($fontPath));
            $fontFace = <<<CSS
<style>
@font-face {
    font-family: "Instrument Sans";
    font-style: normal;
    font-weight: 400;
    src: url(data:font/woff2;base64,{$fontData}) format("woff2");
}
</style>
CSS;
        }

        return $fontFace.<<<'HTML'
<div style="width:100%; padding:0 14mm 5mm; font-family:'Instrument Sans', Arial, sans-serif; font-size:7pt !important; color:#667085; text-align:right; letter-spacing:.14em; text-transform:uppercase;">
    <div style="padding-top:2mm; border-top:0.2mm solid #D8DEE6;">
        <span class="pageNumber"></span> / <span class="totalPages"></span>
    </div>
</div>
HTML;
    }

    private function chromePath(): ?string
    {
        $configured = config('services.browsershot.chrome_path');

        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        $home = getenv('HOME') ?: '';

        $playwright = array_merge(
            glob($home.'/Library/Caches/ms-playwright/chromium-*/chrome-mac-arm64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing') ?: [],
            glob($home.'/Library/Caches/ms-playwright/chromium-*/chrome-mac/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing') ?: [],
            glob($home.'/.cache/ms-playwright/chromium-*/chrome-linux64/chrome') ?: [],
        );

        rsort($playwright);

        $candidates = array_merge([
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/Applications/Chromium.app/Contents/MacOS/Chromium',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
        ], $playwright);

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Copy the preview without internal links and with safe URL values only.
     *
     * @param  array<string, mixed>  $preview
     * @return array<string, mixed>
     */
    private function printPayload(array $preview): array
    {
        $payload = $preview;
        unset($payload['source_links']);

        $personal = $payload['personal_data'] ?? null;

        if (is_array($personal)) {
            foreach (['linkedin_url', 'github_url', 'website_url'] as $key) {
                $personal[$key] = $this->safeHttpUrl($personal[$key] ?? null);
            }

            if (isset($personal['links']) && is_array($personal['links'])) {
                $personal['links'] = array_map(function (mixed $link): mixed {
                    if (! is_array($link)) {
                        return $link;
                    }

                    $link['url'] = $this->safeLinkUrl($link['url'] ?? null);

                    return $link;
                }, $personal['links']);
            }

            $payload['personal_data'] = $personal;
        }

        $content = $payload['content'] ?? null;

        if (is_array($content)) {
            foreach (['certifications' => 'validation_url', 'projects' => 'url'] as $section => $key) {
                if (! isset($content[$section]) || ! is_array($content[$section])) {
                    continue;
                }

                $content[$section] = array_map(function (mixed $item) use ($key): mixed {
                    if (! is_array($item)) {
                        return $item;
                    }

                    $item[$key] = $this->safeHttpUrl($item[$key] ?? null);

                    return $item;
                }, $content[$section]);
            }

            $payload['content'] = $content;
        }

        return $payload;
    }

    private function safeHttpUrl(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return null;
        }

        if (preg_match('/%(?:0[0-9a-f]|1[0-9a-f]|7f)/i', $value)) {
            return null;
        }

        $parts = parse_url($value);

        if ($parts === false
            || ! isset($parts['scheme'], $parts['host'])
            || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $value;
    }

    private function safeLinkUrl(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return null;
        }

        if ($httpUrl = $this->safeHttpUrl($value)) {
            return $httpUrl;
        }

        if (preg_match('/^mailto:(.+)$/i', $value, $matches)) {
            $email = trim($matches[1]);

            return filter_var($email, FILTER_VALIDATE_EMAIL) ? 'mailto:'.$email : null;
        }

        if (preg_match('/^tel:(.+)$/i', $value, $matches)) {
            $phone = trim($matches[1]);

            if ($phone === '' || ! preg_match('/^[0-9+().\-\s]+$/', $phone)) {
                return null;
            }

            $digits = preg_replace('/[^0-9+]/', '', $phone);

            if (! is_string($digits)
                || $digits === ''
                || substr_count($digits, '+') > 1
                || (str_contains($digits, '+') && ! str_starts_with($digits, '+'))) {
                return null;
            }

            return 'tel:'.$digits;
        }

        return null;
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            if (! $item instanceof SplFileInfo) {
                continue;
            }

            $path = $item->getPathname();

            if ($item->isDir() && ! $item->isLink()) {
                $this->removeDirectory($path);
            } elseif (! @unlink($path)) {
                throw new RuntimeException('Unable to remove a PDF temporary file.');
            }
        }

        if (! @rmdir($directory)) {
            throw new RuntimeException('Unable to remove the PDF working directory.');
        }
    }
}
