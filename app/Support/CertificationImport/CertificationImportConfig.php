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

    public function tesseractBinary(): string
    {
        return (string) config('services.ocr.tesseract_binary', 'tesseract');
    }

    public function pdftoppmBinary(): string
    {
        return (string) config('services.ocr.pdftoppm_binary', 'pdftoppm');
    }

    public function pdfimagesBinary(): string
    {
        return (string) config('services.ocr.pdfimages_binary', 'pdfimages');
    }

    /**
     * @return array<int, string>
     */
    public function ocrLanguages(): array
    {
        $configured = (string) config('services.ocr.languages', 'spa+eng');

        return array_values(array_filter(
            array_map('trim', explode('+', $configured)),
            static fn (string $language): bool => $language !== '',
        ));
    }

    public function ocrDpi(): int
    {
        return max(72, (int) config('services.ocr.dpi', 300));
    }

    public function ocrRenderTimeout(): int
    {
        return max(1, (int) config('services.ocr.render_timeout', 60));
    }

    public function ocrTimeout(): int
    {
        return max(1, (int) config('services.ocr.timeout', 120));
    }
}
