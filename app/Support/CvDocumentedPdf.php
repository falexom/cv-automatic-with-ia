<?php

namespace App\Support;

use App\Models\CvProfile;
use App\Models\Document;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class CvDocumentedPdf
{
    private const QPDF_WARNING_EXIT_CODE = 3;

    public function __construct(
        private readonly CvEvidenceSelection $evidenceSelection,
        private readonly CvEvidenceOrder $evidenceOrder,
        private readonly CvPreviewContent $previewContent,
        private readonly CvPdfRenderer $renderer,
        private readonly CvPdfValidator $validator,
    ) {}

    /**
     * Build the documented CV as an in-memory PDF and remove every
     * temporary file before returning it.
     *
     * @param  array<int, int>  $documentIds
     */
    public function generate(CvProfile $profile, array $documentIds): string
    {
        $documentIds = array_values(array_unique(array_map('intval', $documentIds)));

        if ($documentIds === []) {
            throw new CvDocumentedPdfException('Seleccioná al menos una evidencia para generar el CV documentado.');
        }

        $eligibleIds = array_map(
            fn (array $evidence): int => $evidence['id'],
            $this->evidenceSelection->eligibleFor($profile),
        );

        if (array_diff($documentIds, $eligibleIds) !== []) {
            throw new CvDocumentedPdfException('La selección de evidencias no es válida. Volvé a elegir las evidencias del perfil.');
        }

        $documents = $this->evidenceOrder->order($profile, $documentIds);

        if (count($documents) !== count($documentIds)) {
            throw new CvDocumentedPdfException('La selección de evidencias cambió. Volvé a intentar.');
        }

        $this->assertQpdfAvailable();

        $preview = $this->previewContent->build($profile);

        if (! ($preview['has_data'] ?? false)) {
            throw new CvDocumentedPdfException('Este perfil no tiene datos para exportar.');
        }

        $cvPdf = $this->renderer->render($preview);

        if (! $this->validator->isValid($cvPdf)) {
            throw new CvDocumentedPdfException('No se pudo generar el PDF del CV. Volvé a intentar.');
        }

        $disk = Storage::disk(Document::DISK);
        $relative = 'cv-documented/'.Str::uuid()->toString();
        $directory = $disk->path($relative);

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new CvDocumentedPdfException('No se pudo preparar la generación del CV documentado.');
        }

        try {
            if (! $disk->put($relative.'/cv.pdf', $cvPdf)) {
                throw new CvDocumentedPdfException('No se pudo preparar la generación del CV documentado.');
            }

            /** @var array<int, array{0: string, 1: int}> $inputs */
            $inputs = [[
                $relative.'/cv.pdf',
                $this->pageCount($disk->path($relative.'/cv.pdf'), 'el CV'),
            ]];

            foreach ($documents as $document) {
                $this->assertDocumentIntegrity($disk, $document);
                $this->assertDocumentNotEncrypted($disk, $document);
                $inputs[] = [
                    $document->path(),
                    $this->pageCount($disk->path($document->path()), $document->displayName()),
                ];
            }

            $expectedPages = array_sum(array_map(
                fn (array $input): int => $input[1],
                $inputs,
            ));

            $output = $relative.'/documentado.pdf';
            $arguments = ['--empty', '--pages'];

            foreach ($inputs as [$path]) {
                $arguments[] = $disk->path($path);
                $arguments[] = '1-z';
            }

            $arguments[] = '--';
            $arguments[] = $disk->path($output);

            $merge = $this->runQpdf($arguments);
            $mergeExitCode = $merge->getExitCode();

            if ($mergeExitCode !== 0 && $mergeExitCode !== self::QPDF_WARNING_EXIT_CODE) {
                throw new CvDocumentedPdfException(
                    'No se pudo concatenar el CV con las evidencias seleccionadas. Volvé a intentar.',
                    0,
                    new RuntimeException($merge->getErrorOutput()),
                );
            }

            if (! $disk->exists($output)) {
                throw new CvDocumentedPdfException(
                    'No se pudo concatenar el CV con las evidencias seleccionadas. Volvé a intentar.',
                    0,
                    new RuntimeException($merge->getErrorOutput()),
                );
            }

            $actualPages = $this->pageCount($disk->path($output), 'el CV documentado');

            if ($actualPages !== $expectedPages) {
                throw new CvDocumentedPdfException(
                    'El PDF generado no contiene todas las páginas esperadas. Volvé a intentar.',
                );
            }

            $check = $this->runQpdf(['--check', $disk->path($output)]);

            if ($check->getExitCode() !== 0) {
                throw new CvDocumentedPdfException(
                    'No se pudo validar el PDF generado. Volvé a intentar.',
                    0,
                    new RuntimeException($check->getErrorOutput()),
                );
            }

            $pdf = $disk->get($output);

            if (! is_string($pdf) || ! $this->validator->isValid($pdf)) {
                throw new CvDocumentedPdfException(
                    'No se pudo validar el PDF generado. Volvé a intentar.',
                );
            }

            if ($mergeExitCode === self::QPDF_WARNING_EXIT_CODE) {
                Log::warning(
                    'qpdf completó la concatenación del CV documentado con advertencias recuperables.',
                    ['stderr' => trim($merge->getErrorOutput())],
                );
            }

            return $pdf;
        } finally {
            if (! $disk->deleteDirectory($relative)) {
                report(new RuntimeException('No se pudo limpiar el directorio temporal del CV documentado.'));
            }
        }
    }

    private function assertDocumentIntegrity(Filesystem $disk, Document $document): void
    {
        if (! $disk->exists($document->path())) {
            throw new CvDocumentedPdfException(
                "No se pudo incluir «{$document->displayName()}»: el archivo no está disponible.",
            );
        }

        $checksum = hash_file('sha256', $disk->path($document->path()));

        if (! is_string($checksum) || ! hash_equals($document->checksum, $checksum)) {
            throw new CvDocumentedPdfException(
                "No se pudo incluir «{$document->displayName()}»: el archivo fue alterado.",
            );
        }
    }

    private function assertDocumentNotEncrypted(Filesystem $disk, Document $document): void
    {
        $process = $this->runQpdf(['--is-encrypted', $disk->path($document->path())]);

        if ($process->getExitCode() === 0) {
            throw new CvDocumentedPdfException(
                "No se pudo incluir «{$document->displayName()}»: el documento está protegido y no puede anexarse.",
            );
        }

        if ($process->getExitCode() !== 2) {
            throw new CvDocumentedPdfException(
                "No se pudo validar «{$document->displayName()}»: el PDF no es válido o no puede leerse.",
                0,
                new RuntimeException($process->getErrorOutput()),
            );
        }
    }

    private function pageCount(string $path, string $label): int
    {
        $process = $this->runQpdf(['--show-npages', $path]);

        if (! $process->isSuccessful()) {
            throw new CvDocumentedPdfException(
                "No se pudo validar «{$label}»: el PDF no es válido o no puede leerse.",
                0,
                new RuntimeException($process->getErrorOutput()),
            );
        }

        $count = trim($process->getOutput());

        if (! ctype_digit($count) || (int) $count < 1) {
            throw new CvDocumentedPdfException(
                "No se pudo validar «{$label}»: el PDF no contiene páginas.",
            );
        }

        return (int) $count;
    }

    /**
     * @param  array<int, string>  $arguments
     */
    private function runQpdf(array $arguments): Process
    {
        $process = new Process([$this->qpdfBinary(), ...$arguments]);
        $process->setTimeout(120);

        try {
            $process->run();
        } catch (Throwable $exception) {
            throw new CvDocumentedPdfException(
                'No se pudo ejecutar la herramienta de concatenación. Volvé a intentar.',
                0,
                $exception,
            );
        }

        return $process;
    }

    private function assertQpdfAvailable(): void
    {
        $process = new Process([$this->qpdfBinary(), '--version']);
        $process->setTimeout(10);

        try {
            $process->run();
        } catch (Throwable $exception) {
            throw new CvDocumentedPdfException(
                'La herramienta de concatenación no está disponible. Verificá qpdf.',
                0,
                $exception,
            );
        }

        if (! $process->isSuccessful()) {
            throw new CvDocumentedPdfException(
                'La herramienta de concatenación no está disponible. Verificá qpdf.',
            );
        }
    }

    private function qpdfBinary(): string
    {
        $binary = config('services.qpdf.binary');

        return is_string($binary) && trim($binary) !== '' ? trim($binary) : 'qpdf';
    }
}
