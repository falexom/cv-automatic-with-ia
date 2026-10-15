<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesYearMonth;
use Illuminate\Foundation\Http\FormRequest;

class StoreCertificationRequest extends FormRequest
{
    use ValidatesYearMonth;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'date_year' => $this->yearRules('date_month'),
            'date_month' => $this->monthRules(),
            'duration' => ['nullable', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:255'],
            'validation_url' => ['nullable', 'url', 'max:2048'],
            'category' => ['nullable', 'string', 'max:100'],
            'confirm_duplicate' => ['sometimes', 'boolean'],
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->yearMonthMessages(),
            'name.required' => 'El nombre del curso o certificación es obligatorio.',
            'validation_url.url' => 'La URL de validación no tiene un formato válido.',
            'skill_ids.*.exists' => 'Una de las habilidades seleccionadas ya no existe.',
        ];
    }
}
