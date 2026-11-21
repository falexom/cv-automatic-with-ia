<?php

namespace App\Support\CertificationImport;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

final class CertificationImportCodex implements CertificationImportAnalyzer
{
    public function __construct(
        private readonly CertificationImportPrompt $prompt,
        private readonly CertificationImportConfig $config,
    ) {}

    /**
     * @param  array<int, array{ref: string, name: string, category: string}>  $skills
     * @param  array<int, array{page: int, text: string, origin?: string}>  $pages
     * @return array<string, mixed>
     */
    public function analyze(array $skills, array $pages): array
    {
        $input = [
            'prompt' => $this->prompt->build($skills, $pages),
            'schema' => CertificationImportSchema::definition(),
            'model' => $this->config->model(),
            'effort' => $this->config->reasoningEffort(),
            'binary' => $this->config->binary(),
            'timeout_ms' => $this->config->timeout() * 1000,
        ];

        $process = new Process(
            [
                $this->config->nodeBinary(),
                base_path('scripts/codex-structured.mjs'),
            ],
            base_path(),
            null,
            json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            $this->config->timeout() + 30,
        );

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw new CertificationImportException(
                'codex_timeout',
                'El análisis de Codex superó el tiempo máximo. No se guardó ninguna certificación.',
                504,
            );
        } catch (Throwable) {
            throw new CertificationImportException(
                'codex_unavailable',
                'No se pudo iniciar el proceso local de Codex. Podés reintentar o ingresar manualmente.',
                503,
            );
        }

        if (! $process->isSuccessful()) {
            throw new CertificationImportException(
                'codex_failed',
                'Codex no pudo completar el análisis. No se guardó ninguna certificación.',
                502,
            );
        }

        $output = json_decode(trim($process->getOutput()), true);

        if (! is_array($output)) {
            throw new CertificationImportException(
                'codex_failed',
                'El proceso de Codex devolvió una respuesta ilegible. No se guardó ninguna certificación.',
                502,
            );
        }

        if (($output['ok'] ?? false) !== true) {
            $error = is_array($output['error'] ?? null) ? $output['error'] : [];
            $code = is_string($error['code'] ?? null) ? $error['code'] : 'codex_failed';
            $message = is_string($error['message'] ?? null)
                ? $error['message']
                : 'Codex no pudo completar el análisis. No se guardó ninguna certificación.';

            throw new CertificationImportException(
                $code,
                $message,
                $this->statusFor($code),
            );
        }

        $proposal = $output['proposal'] ?? null;

        if (! is_array($proposal)) {
            throw new CertificationImportException(
                'codex_schema',
                'Codex no devolvió una propuesta estructurada. No se guardó ninguna certificación.',
                502,
            );
        }

        return $proposal;
    }

    private function statusFor(string $code): int
    {
        return match ($code) {
            'codex_timeout' => 504,
            'codex_unavailable' => 503,
            default => 502,
        };
    }
}
