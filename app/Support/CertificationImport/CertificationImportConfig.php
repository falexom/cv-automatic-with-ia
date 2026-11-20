<?php

namespace App\Support\CertificationImport;

final class CertificationImportConfig
{
    public function binary(): string
    {
        return (string) config('services.codex.binary');
    }

    public function nodeBinary(): string
    {
        return (string) config('services.codex.node_binary', 'node');
    }

    public function model(): string
    {
        return (string) config('services.codex.model');
    }

    public function reasoningEffort(): string
    {
        return (string) config('services.codex.reasoning_effort');
    }

    public function timeout(): int
    {
        return max(1, (int) config('services.codex.timeout'));
    }

    public function modelLabel(): string
    {
        return match ($this->model()) {
            'gpt-6-luna' => 'Luna 6',
            default => $this->model(),
        };
    }

    public function reasoningEffortLabel(): string
    {
        return match ($this->reasoningEffort()) {
            'minimal' => 'Minimal',
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'xhigh' => 'Extra High',
            'max' => 'Max',
            'ultra' => 'Ultra',
            'persistent' => 'Persistent',
            default => $this->reasoningEffort(),
        };
    }

    public function pdftotextBinary(): string
    {
        return (string) config('services.pdftotext.binary', 'pdftotext');
    }

    public function pdftotextTimeout(): int
    {
        return max(1, (int) config('services.pdftotext.timeout', 30));
    }

    public function maxTextChars(): int
    {
        return max(1000, (int) config('services.certification_import.max_text_chars', 60000));
    }

    public function maxPages(): int
    {
        return max(1, (int) config('services.certification_import.max_pages', 30));
    }

    public function maxPageChars(): int
    {
        return max(1000, (int) config('services.certification_import.max_page_chars', 20000));
    }

    /**
     * Hard extraction limit. Beyond this the document is not presented as
     * a partial extraction; the user is offered manual entry instead.
     */
    public function hardMaxTextChars(): int
    {
        return $this->maxTextChars() * 3;
    }
}
