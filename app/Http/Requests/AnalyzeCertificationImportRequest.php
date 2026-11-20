<?php

namespace App\Http\Requests;

use App\Support\CertificationImport\CertificationImportConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AnalyzeCertificationImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $config = app(CertificationImportConfig::class);

        return [
            'preparation_token' => ['required', 'string', 'max:20000'],
            'consent' => ['required', 'accepted'],
            'pages' => ['required', 'array', 'min:1', 'max:'.$config->maxPages()],
            'pages.*.page' => ['required', 'integer', 'min:1'],
            'pages.*.text' => ['present', 'string', 'max:'.$config->maxPageChars()],
            'existing_category' => ['nullable', 'string', 'max:100'],
            'existing_skill_ids' => ['nullable', 'array', 'max:100'],
            'existing_skill_ids.*' => ['integer', 'exists:skills,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $pages = $this->input('pages');

            if (! is_array($pages)) {
                return;
            }

            $config = app(CertificationImportConfig::class);
            $total = 0;
            $seen = [];

            foreach ($pages as $page) {
                if (! is_array($page)) {
                    continue;
                }

                $number = $page['page'] ?? null;
                $text = $page['text'] ?? null;

                if (is_int($number)) {
                    if (isset($seen[$number])) {
                        $validator->errors()->add('pages', 'Las páginas no pueden repetirse.');
                    }

                    $seen[$number] = true;
                }

                if (is_string($text)) {
                    $total += mb_strlen($text);
                }
            }

            if ($total > $config->maxTextChars()) {
                $validator->errors()->add(
                    'pages',
                    'El texto a analizar supera el límite de '.$config->maxTextChars().' caracteres. Quitá contenido o continuá manualmente.',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preparation_token.required' => 'Volvé a preparar el texto del PDF antes de analizar.',
            'consent.required' => 'Necesitamos tu consentimiento para enviar el texto a Codex/OpenAI.',
            'consent.accepted' => 'Necesitamos tu consentimiento para enviar el texto a Codex/OpenAI.',
            'pages.required' => 'No hay texto preparado para analizar.',
            'pages.array' => 'El texto preparado no es válido.',
            'pages.min' => 'No hay texto preparado para analizar.',
            'pages.max' => 'El PDF supera la cantidad de páginas admitida para el análisis.',
            'pages.*.page.required' => 'Falta el número de página.',
            'pages.*.page.integer' => 'El número de página no es válido.',
            'pages.*.text.present' => 'Falta el texto de una página.',
            'pages.*.text.max' => 'El texto de una página supera el límite permitido.',
            'existing_category.max' => 'La categoría existente no es válida.',
            'existing_skill_ids.*.exists' => 'Una de las habilidades existentes ya no está disponible.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $category = $this->input('existing_category');

        if (is_string($category) && trim($category) === '') {
            $this->merge(['existing_category' => null]);
        }
    }
}
