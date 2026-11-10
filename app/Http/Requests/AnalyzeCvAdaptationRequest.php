<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalyzeCvAdaptationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'offer' => ['required', 'string', 'max:30000'],
            'consent' => ['required', 'accepted'],
            'strategy' => ['nullable', 'string', Rule::in(['auto', 'reuse', 'duplicate', 'create'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'offer.required' => 'Pegá el texto de la oferta antes de analizar.',
            'offer.string' => 'La oferta debe ser texto.',
            'offer.max' => 'La oferta no puede superar los 30.000 caracteres.',
            'consent.required' => 'Necesitamos tu consentimiento para enviar los datos a Codex/OpenAI.',
            'consent.accepted' => 'Necesitamos tu consentimiento para enviar los datos a Codex/OpenAI.',
            'strategy.in' => 'La estrategia seleccionada no es válida.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $offer = $this->input('offer');

        if (is_string($offer)) {
            $this->merge(['offer' => trim($offer)]);
        }

        if ($this->input('strategy') === '') {
            $this->merge(['strategy' => null]);
        }
    }
}
