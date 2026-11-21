<?php

namespace App\Support\CertificationImport;

use App\Support\PdfFile;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Local OCR through Tesseract. Pages are rendered to temporary images and
 * deleted immediately; neither the PDF nor the images are sent anywhere.
 */
final class TesseractOcrExtractor implements LocalOcrExtractor
{
    /**
     * @var array<int, string>
     */
    private array $usedLanguages = [];

    public function __construct(
        private readonly CertificationImportConfig $config,
    ) {}

    /**
     * @param  array<int, int>  $pageNumbers
     * @return array<int, array{page: int, text: string, has_content: bool}>
     */
    public function extract(string $contents, array $pageNumbers): array
    {
        $this->usedLanguages = [];
        $pageNumbers = array_values(array_unique(array_map('intval', $pageNumbers)));

        if ($pageNumbers === []) {
            return [];
        }

        if (! PdfFile::looksLikePdf($contents)) {
            throw new CertificationImportException(
                'ocr_invalid_pdf',
                'No se puede aplicar OCR local a un PDF inválido. Podés ingresar los datos manualmente.',
                422,
            );
        }

        $usedLanguages = $this->resolveLanguages();
        $this->usedLanguages = $usedLanguages;

        $directory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'cv-ocr-'.bin2hex(random_bytes(8));

        if (! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new CertificationImportException(
                'ocr_unavailable',
                'No se pudo preparar el espacio temporal para OCR local. Podés ingresar los datos manualmente.',
                503,
            );
        }

        $pdfPath = $directory.DIRECTORY_SEPARATOR.'source.pdf';

        register_shutdown_function(static function () use ($directory): void {
            foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }

            @rmdir($directory);
        });

        try {
            if (file_put_contents($pdfPath, $contents) === false) {
                throw new CertificationImportException(
                    'ocr_unavailable',
                    'No se pudo preparar el PDF para OCR local. Podés ingresar los datos manualmente.',
                    503,
                );
            }

            $pages = [];
            $pagesWithLargeImages = $this->pagesWithLargeImages($pdfPath);

            foreach ($pageNumbers as $pageNumber) {
                if ($pageNumber < 1) {
                    continue;
                }

                $hasLargeImage = $pagesWithLargeImages === null
                    || ($pagesWithLargeImages[$pageNumber] ?? false);
                $prefix = $directory.DIRECTORY_SEPARATOR.'page-'.$pageNumber;
                $imagePath = $prefix.'.png';

                $this->run(
                    [
                        $this->config->pdftoppmBinary(),
                        '-f',
                        (string) $pageNumber,
                        '-l',
                        (string) $pageNumber,
                        '-r',
                        (string) $this->config->ocrDpi(),
                        '-png',
                        '-singlefile',
                        $pdfPath,
                        $prefix,
                    ],
                    $this->config->ocrRenderTimeout(),
                    'ocr_unavailable',
                    'No se encontró pdftoppm para preparar las páginas del OCR local. Podés ingresar manualmente.',
                    'ocr_render_failed',
                    'No se pudo renderizar una página para OCR local. Podés reintentar o ingresar manualmente.',
                );

                if (! is_file($imagePath)) {
                    throw new CertificationImportException(
                        'ocr_render_failed',
                        'No se pudo preparar una página para OCR local. Podés reintentar o ingresar manualmente.',
                        502,
                    );
                }

                $text = $this->run(
                    [
                        $this->config->tesseractBinary(),
                        $imagePath,
                        'stdout',
                        '-l',
                        implode('+', $usedLanguages),
                        '--dpi',
                        (string) $this->config->ocrDpi(),
                        '--psm',
                        '3',
                        '-c',
                        'preserve_interword_spaces=1',
                    ],
                    $this->config->ocrTimeout(),
                    'ocr_unavailable',
                    'No se encontró Tesseract para OCR local. Podés ingresar manualmente.',
                    'ocr_failed',
                    'OCR local no pudo leer una página. Podés reintentar o ingresar manualmente.',
                );

                @unlink($imagePath);

                $text = rtrim(str_replace(["\r\n", "\f"], ["\n", "\n"], $text));
                $pages[] = [
                    'page' => $pageNumber,
                    'text' => $text,
                    'has_content' => $hasLargeImage || trim($text) !== '',
                ];
            }

            return $pages;
        } finally {
            $this->deleteDirectory($directory);
        }
    }

    /**
     * @return array<int, string>
     */
    public function usedLanguages(): array
    {
        return $this->usedLanguages;
    }

    /**
     * @return array<int, string>
     */
    private function resolveLanguages(): array
    {
        $available = $this->availableLanguages();
        $configured = $this->config->ocrLanguages();
        $used = array_values(array_intersect($configured, $available));

        if ($used === []) {
            throw new CertificationImportException(
                'ocr_language_unavailable',
                'No están disponibles los idiomas OCR configurados ('.implode('+', $configured).'). Podés ingresar los datos manualmente.',
                503,
            );
        }

        return $used;
    }

    /**
     * Detect pages that contain a page-sized raster image, such as a scan.
     * Small logos/decorations do not turn a blank page into an OCR failure.
     *
     * @return array<int, bool>|null null when the inspection is unavailable
     */
    private function pagesWithLargeImages(string $pdfPath): ?array
    {
        try {
            $output = $this->run(
                [$this->config->pdfimagesBinary(), '-list', $pdfPath],
                15,
                'ocr_unavailable',
                'No se encontró pdfimages para preparar el OCR local.',
                'ocr_unavailable',
                'No se pudo inspeccionar las imágenes del PDF para OCR local.',
            );
        } catch (CertificationImportException) {
            // PDF rendering can still work; assume content rather than
            // blocking a legitimate scanned page.
            return null;
        }

        $pages = [];

        foreach (preg_split('/\R/u', $output) ?: [] as $line) {
            if (preg_match('/^\s*(\d+)\s+\d+\s+(?:image|smask)\s+(\d+)\s+(\d+)\b/', $line, $matches) !== 1) {
                continue;
            }

            $width = (int) $matches[2];
            $height = (int) $matches[3];

            if ($width * $height >= 250000) {
                $pages[(int) $matches[1]] = true;
            }
        }

        return $pages;
    }

    /**
     * @return array<int, string>
     */
    private function availableLanguages(): array
    {
        $output = $this->run(
            [$this->config->tesseractBinary(), '--list-langs'],
            15,
            'ocr_unavailable',
            'No se encontró Tesseract para OCR local. Podés ingresar manualmente.',
            'ocr_unavailable',
            'No se pudieron consultar los idiomas de OCR local. Podés ingresar manualmente.',
        );

        $languages = [];

        foreach (preg_split('/\R/u', $output) ?: [] as $line) {
            $line = trim($line);

            if (
                $line === ''
                || str_contains($line, 'List of available languages')
                || preg_match('/^[A-Za-z0-9_]+$/', $line) !== 1
            ) {
                continue;
            }

            $languages[] = $line;
        }

        return array_values(array_unique($languages));
    }

    /**
     * @param  array<int, string>  $command
     */
    private function run(
        array $command,
        int $timeout,
        string $startFailureCode,
        string $startFailureMessage,
        string $exitFailureCode,
        string $exitFailureMessage,
    ): string {
        $process = new Process($command, base_path(), null, null, $timeout);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw new CertificationImportException(
                'ocr_timeout',
                'La preparación OCR local superó el tiempo máximo. Podés reintentar o ingresar manualmente.',
                504,
            );
        } catch (Throwable) {
            throw new CertificationImportException(
                $startFailureCode,
                $startFailureMessage,
                503,
            );
        }

        if (! $process->isSuccessful()) {
            throw new CertificationImportException(
                $exitFailureCode,
                $exitFailureMessage,
                502,
            );
        }

        return $process->getOutput();
    }

    private function deleteDirectory(string $directory): void
    {
        foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($directory);
    }
}
