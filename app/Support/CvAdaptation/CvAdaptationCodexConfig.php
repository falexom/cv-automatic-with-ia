<?php

namespace App\Support\CvAdaptation;

final class CvAdaptationCodexConfig
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
}
