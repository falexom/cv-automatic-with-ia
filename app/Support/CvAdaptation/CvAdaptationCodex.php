<?php

namespace App\Support\CvAdaptation;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

final class CvAdaptationCodex implements CvAdaptationAnalyzer
{
    public function __construct(
        private readonly CvAdaptationPrompt $prompt,
        private readonly CvAdaptationCodexConfig $config,
    ) {}

    /**
     * @param  array<string, mixed>  $catalog
     * @return array<string, mixed>
     */
    public function analyze(array $catalog, string $offer, ?string $strategy = null): array
    {
        $input = [
            'prompt' => $this->prompt->build($catalog, $offer, $strategy),
            'schema' => CvAdaptationSchema::definition(),
            'model' => $this->config->model(),
            'effort' => $this->config->reasoningEffort(),
            'binary' => $this->config->binary(),
            'timeout_ms' => $this->config->timeout() * 1000,
        ];

        $timeout = $this->config->timeout() + 30;

        $process = new Process(
            [
                $this->config->nodeBinary(),
                base_path('scripts/codex-structured.mjs'),
            ],
            base_path(),
            null,
            json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            $timeout,
        );

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw new CvAdaptationException(
                'codex_timeout',
                'El análisis de Codex superó el tiempo máximo. No se guardó ningún cambio.',
                504,
            );
        } catch (Throwable) {
            throw new CvAdaptationException(
                'codex_unavailable',
                'No se pudo iniciar el proceso local de Codex. Verificá Node.js y el CLI de Codex.',
                503,
            );
        }

        if (! $process->isSuccessful()) {
            throw new CvAdaptationException(
                'codex_failed',
                'Codex no pudo completar el análisis. No se guardó ningún cambio.',
                502,
            );
        }

        $output = json_decode(trim($process->getOutput()), true);

        if (! is_array($output)) {
            throw new CvAdaptationException(
                'codex_failed',
                'El proceso de Codex devolvió una respuesta ilegible. No se guardó ningún cambio.',
                502,
            );
        }

        if (($output['ok'] ?? false) !== true) {
            $error = is_array($output['error'] ?? null) ? $output['error'] : [];
            $code = is_string($error['code'] ?? null) ? $error['code'] : 'codex_failed';
            $message = is_string($error['message'] ?? null)
                ? $error['message']
                : 'Codex no pudo completar el análisis. No se guardó ningún cambio.';

            throw new CvAdaptationException(
                $code,
                $message,
                $this->statusFor($code),
            );
        }

        $proposal = $output['proposal'] ?? null;

        if (! is_array($proposal)) {
            throw new CvAdaptationException(
                'codex_schema',
                'Codex no devolvió una propuesta estructurada. No se guardó ningún cambio.',
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
            'codex_auth', 'codex_model', 'codex_schema', 'codex_failed' => 502,
            default => 502,
        };
    }
}
