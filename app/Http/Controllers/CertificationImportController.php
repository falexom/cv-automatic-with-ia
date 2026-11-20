<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyzeCertificationImportRequest;
use App\Http\Requests\ConfirmCertificationImportRequest;
use App\Http\Requests\PrepareCertificationImportRequest;
use App\Support\CertificationImport\CertificationImportAnalyzer;
use App\Support\CertificationImport\CertificationImportCatalog;
use App\Support\CertificationImport\CertificationImportConfig;
use App\Support\CertificationImport\CertificationImportException;
use App\Support\CertificationImport\CertificationImportPreparationToken;
use App\Support\CertificationImport\CertificationImportProposalToken;
use App\Support\CertificationImport\CertificationImportText;
use App\Support\CertificationImport\CertificationImportValidator;
use App\Support\CertificationImport\CertificationImportWriter;
use App\Support\CertificationImport\PdfTextExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Throwable;

class CertificationImportController extends Controller
{
    public function __construct(
        private readonly PdfTextExtractor $extractor,
        private readonly CertificationImportText $text,
        private readonly CertificationImportPreparationToken $preparationToken,
        private readonly CertificationImportAnalyzer $analyzer,
        private readonly CertificationImportCatalog $catalog,
        private readonly CertificationImportValidator $validator,
        private readonly CertificationImportProposalToken $proposalToken,
        private readonly CertificationImportWriter $writer,
        private readonly CertificationImportConfig $config,
    ) {}

    /**
     * Extract native text and return the exact, editable content that could
     * be sent later. No external call happens here and nothing is persisted.
     */
    public function prepare(PrepareCertificationImportRequest $request): JsonResponse
    {
        // La extracción local de un PDF grande puede superar el límite de PHP.
        @set_time_limit(0);

        $file = $request->file('file');

        if (! $file instanceof UploadedFile) {
            return response()->json([
                'message' => 'Seleccioná un único archivo PDF.',
                'code' => 'pdf_missing',
            ], 422);
        }

        try {
            $contents = (string) $file->get();
            $pages = $this->extractor->extract($contents);
            $minimized = $this->text->minimize($pages);
            $pages = $minimized['pages'];
            $quality = $this->text->quality($pages);
            $total = $this->text->totalChars($pages);
            $pageCount = count($pages);
            $checksum = hash('sha256', $contents);
            $filename = $file->getClientOriginalName();
            $status = 'ready';
            $message = null;
            $token = null;

            if ($quality !== 'ok') {
                $status = 'insufficient';
                $message = $quality === 'illegible'
                    ? 'El texto nativo del PDF no es legible. En esta versión no se ejecuta OCR. Podés ingresar los datos manualmente.'
                    : 'No se detectó texto nativo suficiente en el PDF. Si es un documento escaneado, en esta versión no se ejecuta OCR. Podés ingresar los datos manualmente.';
            } elseif ($pageCount > $this->config->maxPages()) {
                $status = 'too_long';
                $message = 'El PDF tiene '.$pageCount.' páginas y el máximo para analizar es '.$this->config->maxPages().'. Podés ingresar los datos manualmente.';
            } elseif ($total > $this->config->maxTextChars()) {
                $status = 'too_long';
                $message = 'El texto extraído tiene '.$total.' caracteres y el máximo es '.$this->config->maxTextChars().'. Podés quitar páginas o líneas hasta ese límite, o ingresar manualmente.';
                $token = $this->preparationToken->issue($checksum, $filename, mb_strlen($contents, '8bit'), $pageCount);
            } else {
                $token = $this->preparationToken->issue($checksum, $filename, mb_strlen($contents, '8bit'), $pageCount);
            }

            return response()->json([
                'preparation_token' => $token,
                'checksum' => $checksum,
                'original_filename' => $filename,
                'size_bytes' => mb_strlen($contents, '8bit'),
                'page_count' => $pageCount,
                'pages' => $pages,
                'redactions' => $minimized['redactions'],
                'status' => $status,
                'message' => $message,
            ]);
        } catch (CertificationImportException $exception) {
            return $this->errorResponse($exception);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No se pudo preparar el texto del PDF. No se guardó nada. Podés ingresar manualmente.',
                'code' => 'prepare_failed',
            ], 500);
        }
    }

    public function analyze(AnalyzeCertificationImportRequest $request): JsonResponse
    {
        // El análisis con Codex puede superar el límite de ejecución por defecto de PHP.
        @set_time_limit(0);

        /** @var array<string, mixed> $data */
        $data = $request->validated();

        try {
            $preparation = $this->preparationToken->parse((string) $data['preparation_token']);
            /** @var array<int, array{page: int, text: string}> $pages */
            $pages = $this->validatedPages($data['pages'] ?? null, $preparation['page_count']);

            if ($this->text->quality($pages) !== 'ok') {
                throw new CertificationImportException(
                    'text_insufficient',
                    'El texto seleccionado no es suficiente para analizarlo. En esta versión no se ejecuta OCR. Podés ingresar manualmente.',
                    422,
                );
            }

            if ($this->text->totalChars($pages) > $this->config->maxTextChars()) {
                throw new CertificationImportException(
                    'text_too_long',
                    'El texto a analizar supera el límite de '.$this->config->maxTextChars().' caracteres. Quitá contenido o continuá manualmente.',
                    422,
                );
            }

            $nonce = $this->catalog->nonce();
            $built = $this->catalog->build($nonce);
            $proposal = $this->analyzer->analyze($built['skills'], $pages);
            $proposal = $this->validator->validate($proposal, $pages, $built['lookup']);

            $existingSkillIds = [];

            if (is_array($data['existing_skill_ids'] ?? null)) {
                foreach ($data['existing_skill_ids'] as $skillId) {
                    if (is_int($skillId)) {
                        $existingSkillIds[] = $skillId;
                    } elseif (is_string($skillId) && ctype_digit($skillId)) {
                        $existingSkillIds[] = (int) $skillId;
                    }
                }
            }

            $token = $this->proposalToken->issue(
                $proposal,
                $preparation['checksum'],
                $preparation['original_filename'],
                is_string($data['existing_category'] ?? null) ? $data['existing_category'] : null,
                $existingSkillIds,
            );

            return response()->json([
                'proposal' => $proposal,
                'confirmation_token' => $token,
            ]);
        } catch (CertificationImportException $exception) {
            return $this->errorResponse($exception);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Ocurrió un error inesperado durante el análisis. No se guardó nada.',
                'code' => 'unexpected',
            ], 500);
        }
    }

    public function confirm(ConfirmCertificationImportRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $file = $request->file('file');

        if (! $file instanceof UploadedFile) {
            return response()->json([
                'message' => 'Seleccioná el PDF original antes de confirmar.',
                'code' => 'pdf_missing',
            ], 422);
        }

        $token = is_string($data['proposal_token'] ?? null) && $data['proposal_token'] !== ''
            ? $data['proposal_token']
            : null;

        try {
            $result = $this->writer->confirm($data, $file, $token);

            if ($result['duplicate_warning'] !== null) {
                return response()->json([
                    'duplicate_warning' => $result['duplicate_warning'],
                    'requires_confirmation' => true,
                ]);
            }

            $message = match (true) {
                $result['idempotent'] => 'La certificación ya estaba guardada. No se duplicó.',
                $result['reused_document'] => 'Certificación guardada y PDF original reutilizado.',
                default => 'Certificación guardada con su PDF original.',
            };

            try {
                $request->session()->flash('toast', ['type' => 'success', 'message' => $message]);
            } catch (Throwable $exception) {
                report($exception);
            }

            return response()->json(['redirect' => $result['redirect']]);
        } catch (CertificationImportException $exception) {
            return $this->errorResponse($exception);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Ocurrió un error inesperado al guardar. No se creó ningún registro parcial.',
                'code' => 'unexpected',
            ], 500);
        }
    }

    /**
     * @return array<int, array{page: int, text: string}>
     */
    private function validatedPages(mixed $rawPages, int $preparedPageCount): array
    {
        if (! is_array($rawPages) || $rawPages === []) {
            throw new CertificationImportException(
                'invalid_preparation',
                'No hay texto preparado para analizar. Volvé a preparar el PDF.',
                422,
            );
        }

        $pages = [];
        $seen = [];

        foreach ($rawPages as $rawPage) {
            if (! is_array($rawPage)) {
                throw new CertificationImportException('invalid_preparation', 'El texto preparado no es válido.', 422);
            }

            $number = $rawPage['page'] ?? null;
            $text = $rawPage['text'] ?? null;

            if (
                ! is_int($number)
                || $number < 1
                || $number > $preparedPageCount
                || isset($seen[$number])
                || ! is_string($text)
                || mb_strlen($text) > $this->config->maxPageChars()
            ) {
                throw new CertificationImportException(
                    'invalid_preparation',
                    'El texto preparado no coincide con el PDF. Volvé a prepararlo antes de analizar.',
                    422,
                );
            }

            $seen[$number] = true;
            $pages[] = ['page' => $number, 'text' => $text];
        }

        usort($pages, static fn (array $a, array $b): int => $a['page'] <=> $b['page']);

        return $pages;
    }

    private function errorResponse(CertificationImportException $exception): JsonResponse
    {
        $payload = [
            'message' => $exception->getMessage(),
            'code' => $exception->errorCode(),
        ];

        if ($exception->errors() !== []) {
            $payload['errors'] = $exception->errors();
        }

        return response()->json($payload, $exception->status());
    }
}
