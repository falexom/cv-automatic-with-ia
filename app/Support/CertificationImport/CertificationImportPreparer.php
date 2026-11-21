<?php

namespace App\Support\CertificationImport;

/**
 * Native-first preparation with a selective local OCR fallback. The
 * original PDF is never modified; OCR uses temporary derivatives that are
 * deleted by the OCR service.
 */
final class CertificationImportPreparer
{
    public function __construct(
        private readonly PdfTextExtractor $extractor,
        private readonly LocalOcrExtractor $ocr,
        private readonly CertificationImportText $text,
        private readonly CertificationImportConfig $config,
        private readonly CertificationImportPreparationToken $preparationToken,
    ) {}

    /**
     * @return array{
     *     preparation_token: string|null,
     *     checksum: string,
     *     original_filename: string,
     *     size_bytes: int,
     *     page_count: int,
     *     pages: array<int, array{page: int, text: string, origin: string}>,
     *     redactions: int,
     *     status: string,
     *     message: string|null,
     *     ocr_used: bool,
     *     ocr_languages: array<int, string>,
     *     ocr_warning: string|null
     * }
     */
    public function prepare(string $contents, string $filename, bool $forceOcr): array
    {
        $nativePages = $this->extractor->extract($contents);
        $nativeMinimized = $this->text->minimize($nativePages);
        /** @var array<int, array{page: int, text: string, origin: string}> $pages */
        $pages = array_map(
            static fn (array $page): array => [
                'page' => $page['page'],
                'text' => $page['text'],
                'origin' => 'native',
            ],
            $nativeMinimized['pages'],
        );
        $redactions = $nativeMinimized['redactions'];
        $pageCount = count($pages);
        $checksum = hash('sha256', $contents);
        $sizeBytes = mb_strlen($contents, '8bit');
        $ocrUsed = false;
        $ocrLanguages = [];
        $ocrWarning = null;

        if ($pageCount > $this->config->maxPages()) {
            return $this->response(
                $checksum,
                $filename,
                $sizeBytes,
                $pages,
                $redactions,
                'too_long',
                'El PDF tiene '.$pageCount.' páginas y el máximo para analizar es '.$this->config->maxPages().'. Podés ingresar los datos manualmente.',
                null,
                false,
                [],
                null,
            );
        }

        $pagesToOcr = $forceOcr
            ? array_map(static fn (array $page): int => $page['page'], $pages)
            : array_values(array_map(
                static fn (array $page): int => $page['page'],
                array_filter($pages, fn (array $page): bool => ! $this->isReadable($page['text'])),
            ));

        if ($pagesToOcr !== []) {
            try {
                $ocrPages = $this->ocr->extract($contents, $pagesToOcr);
                $ocrMinimized = $this->text->minimize($ocrPages);
                $redactions += $ocrMinimized['redactions'];
                $ocrLanguages = $this->ocr->usedLanguages();
                $missingLanguages = array_diff($this->config->ocrLanguages(), $ocrLanguages);
                $merged = $this->merge($pages, $ocrMinimized['pages'], $pagesToOcr, $forceOcr);
                $pages = $merged['pages'];
                $ocrUsed = $merged['used'];

                if ($ocrUsed && $missingLanguages !== []) {
                    $ocrWarning = 'Idiomas OCR no disponibles: '.implode('+', $missingLanguages).'. Se usó: '.implode('+', $ocrLanguages).'. Revisá el texto con especial cuidado.';
                }

                if ($merged['failed'] !== []) {
                    return $this->response(
                        $checksum,
                        $filename,
                        $sizeBytes,
                        $pages,
                        $redactions,
                        'insufficient',
                        'OCR local no pudo leer la(s) página(s) '.implode(', ', $merged['failed']).'. Podés reintentar o ingresar manualmente.',
                        null,
                        $ocrUsed,
                        $ocrLanguages,
                        $ocrWarning,
                    );
                }

                if ($forceOcr && ! $ocrUsed) {
                    $ocrWarning = 'OCR local no aportó texto adicional; se usó el texto nativo.';
                }
            } catch (CertificationImportException $exception) {
                $ocrWarning = $exception->getMessage();
                $unreadable = array_filter($pages, fn (array $page): bool => ! $this->isReadable($page['text']));

                if ($unreadable !== []) {
                    return $this->response(
                        $checksum,
                        $filename,
                        $sizeBytes,
                        $pages,
                        $redactions,
                        'insufficient',
                        $exception->getMessage(),
                        null,
                        false,
                        [],
                        null,
                    );
                }
            }
        }

        $quality = $this->text->quality($pages);
        $total = $this->text->totalChars($pages);

        if ($quality !== 'ok') {
            $message = $ocrUsed
                ? 'El texto nativo y el OCR local no son suficientes para una propuesta fiable. Podés reintentar o ingresar manualmente.'
                : 'No se detectó texto nativo ni OCR local suficiente en el PDF. Podés reintentar o ingresar manualmente.';

            return $this->response(
                $checksum,
                $filename,
                $sizeBytes,
                $pages,
                $redactions,
                'insufficient',
                $message,
                null,
                $ocrUsed,
                $ocrLanguages,
                $ocrWarning,
            );
        }

        if ($total > $this->config->hardMaxTextChars()) {
            throw new CertificationImportException(
                'text_too_long',
                'El texto extraído, incluido el OCR local, es demasiado extenso para analizarlo con seguridad. Podés ingresar los datos manualmente.',
                422,
            );
        }

        $origins = [];

        foreach ($pages as $page) {
            $origins[$page['page']] = $page['origin'];
        }

        if ($total > $this->config->maxTextChars()) {
            return $this->response(
                $checksum,
                $filename,
                $sizeBytes,
                $pages,
                $redactions,
                'too_long',
                'El texto extraído tiene '.$total.' caracteres y el máximo es '.$this->config->maxTextChars().'. Podés quitar páginas o líneas hasta ese límite, o ingresar manualmente.',
                $this->preparationToken->issue($checksum, $filename, $sizeBytes, $pageCount, $origins),
                $ocrUsed,
                $ocrLanguages,
                $ocrWarning,
            );
        }

        return $this->response(
            $checksum,
            $filename,
            $sizeBytes,
            $pages,
            $redactions,
            'ready',
            null,
            $this->preparationToken->issue($checksum, $filename, $sizeBytes, $pageCount, $origins),
            $ocrUsed,
            $ocrLanguages,
            $ocrWarning,
        );
    }

    /**
     * @param  array<int, array{page: int, text: string, origin: string}>  $nativePages
     * @param  array<int, array{page: int, text: string, has_content?: bool}>  $ocrPages
     * @param  array<int, int>  $pagesToOcr
     * @return array{
     *     pages: array<int, array{page: int, text: string, origin: string}>,
     *     used: bool,
     *     failed: array<int, int>
     * }
     */
    private function merge(array $nativePages, array $ocrPages, array $pagesToOcr, bool $forceOcr): array
    {
        $ocrByPage = [];

        foreach ($ocrPages as $page) {
            $ocrByPage[$page['page']] = $page;
        }

        $used = false;
        $failed = [];

        foreach ($nativePages as $index => $page) {
            if (! in_array($page['page'], $pagesToOcr, true)) {
                continue;
            }

            $ocrPage = $ocrByPage[$page['page']] ?? null;
            $nativeQuality = $this->text->quality([$page]);

            if ($ocrPage === null) {
                $failed[] = $page['page'];

                continue;
            }

            $ocrText = $ocrPage['text'];
            $hasContent = $ocrPage['has_content'] ?? true;
            $nativeEmpty = trim($page['text']) === '';
            $ocrQuality = $this->text->quality([[
                'page' => $page['page'],
                'text' => $ocrText,
            ]]);

            $useOcr = $nativeQuality !== 'ok'
                ? $ocrQuality === 'ok'
                : $forceOcr
                    && $ocrQuality === 'ok'
                    && $this->ocrAddsContent($page['text'], $ocrText);

            if ($useOcr) {
                $nativePages[$index]['text'] = $ocrText;
                $nativePages[$index]['origin'] = trim($page['text']) === '' ? 'ocr' : 'mixed';
                $used = true;

                continue;
            }

            if ($nativeQuality !== 'ok' && ! ($nativeEmpty && ! $hasContent)) {
                $failed[] = $page['page'];
            }
        }

        return ['pages' => $nativePages, 'used' => $used, 'failed' => $failed];
    }

    private function isReadable(string $text): bool
    {
        return $this->text->quality([['page' => 1, 'text' => $text]]) === 'ok';
    }

    private function ocrAddsContent(string $nativeText, string $ocrText): bool
    {
        $nativeLength = mb_strlen(trim($nativeText));
        $ocrLength = mb_strlen(trim($ocrText));

        return $ocrLength >= 20
            && $ocrLength >= max(20, (int) ceil($nativeLength * 1.2));
    }

    /**
     * @param  array<int, array{page: int, text: string, origin: string}>  $pages
     * @param  array<int, string>  $ocrLanguages
     * @return array{
     *     preparation_token: string|null,
     *     checksum: string,
     *     original_filename: string,
     *     size_bytes: int,
     *     page_count: int,
     *     pages: array<int, array{page: int, text: string, origin: string}>,
     *     redactions: int,
     *     status: string,
     *     message: string|null,
     *     ocr_used: bool,
     *     ocr_languages: array<int, string>,
     *     ocr_warning: string|null
     * }
     */
    private function response(
        string $checksum,
        string $filename,
        int $sizeBytes,
        array $pages,
        int $redactions,
        string $status,
        ?string $message,
        ?string $token,
        bool $ocrUsed,
        array $ocrLanguages,
        ?string $ocrWarning,
    ): array {
        return [
            'preparation_token' => $token,
            'checksum' => $checksum,
            'original_filename' => $filename,
            'size_bytes' => $sizeBytes,
            'page_count' => count($pages),
            'pages' => array_values($pages),
            'redactions' => $redactions,
            'status' => $status,
            'message' => $message,
            'ocr_used' => $ocrUsed,
            'ocr_languages' => $ocrLanguages,
            'ocr_warning' => $ocrWarning,
        ];
    }
}
