<?php

namespace App\Support\CertificationImport;

use App\Support\PdfFile;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Native text extraction through the locally installed pdftotext binary.
 * The PDF is streamed through stdin, so no temporary copy is written to
 * the server. OCR is deliberately not invoked here: that belongs to US-011.
 */
final class PdftotextExtractor implements PdfTextExtractor
{
    public function __construct(
        private readonly CertificationImportConfig $config,
    ) {}

    /**
     * @return array<int, array{page: int, text: string}>
     */
    public function extract(string $contents): array
    {
        if (! PdfFile::looksLikePdf($contents)) {
            throw new CertificationImportException(
                'pdf_invalid',
                'El archivo no es un PDF válido o está truncado.',
                422,
            );
        }

        $process = new Process(
            [
                $this->config->pdftotextBinary(),
                '-layout',
                '-enc',
                'UTF-8',
                '-',
                '-',
            ],
            base_path(),
            null,
            $contents,
            $this->config->pdftotextTimeout(),
        );

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw new CertificationImportException(
                'text_timeout',
                'La extracción de texto superó el tiempo máximo. No se guardó nada. Podés reintentar o ingresar manualmente.',
                504,
            );
        } catch (Throwable) {
            throw new CertificationImportException(
                'text_unavailable',
                'No se encontró o no se pudo ejecutar pdftotext. Podés ingresar los datos manualmente.',
                503,
            );
        }

        $this->assertProcessSucceeded($process);

        $output = $process->getOutput();

        if (mb_strlen($output) > $this->config->hardMaxTextChars()) {
            throw new CertificationImportException(
                'text_too_long',
                'El texto extraído es demasiado extenso para analizarlo con seguridad. Podés ingresar los datos manualmente.',
                422,
            );
        }

        return $this->splitPages($output);
    }

    private function assertProcessSucceeded(Process $process): void
    {
        if ($process->isSuccessful()) {
            return;
        }

        $stderr = strtolower($process->getErrorOutput());

        if (
            $process->getExitCode() === 3
            || str_contains($stderr, 'password')
            || str_contains($stderr, 'encrypted')
            || str_contains($stderr, 'protected')
        ) {
            throw new CertificationImportException(
                'pdf_protected',
                'El PDF está protegido con contraseña. No se intenta eludir la protección. Podés ingresar los datos manualmente.',
                422,
            );
        }

        throw new CertificationImportException(
            'text_failed',
            'No se pudo extraer texto del PDF. No se guardó nada. Podés reintentar o ingresar manualmente.',
            502,
        );
    }

    /**
     * @return array<int, array{page: int, text: string}>
     */
    private function splitPages(string $output): array
    {
        $chunks = explode("\f", str_replace("\r\n", "\n", $output));

        // pdftotext terminates each page with a form feed. Drop only the
        // final separator so empty pages are preserved for OCR decisions.
        if (count($chunks) > 1 && trim((string) end($chunks)) === '') {
            array_pop($chunks);
        }

        $pages = [];

        foreach ($chunks as $index => $text) {
            $pages[] = [
                'page' => $index + 1,
                'text' => rtrim($text),
            ];
        }

        return $pages;
    }
}
