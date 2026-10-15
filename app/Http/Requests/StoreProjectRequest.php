<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesYearMonth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProjectRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'role' => ['nullable', 'string', 'max:255'],
            'results' => ['nullable', 'string'],
            'url' => ['nullable', 'url', 'max:2048'],
            'start_year' => $this->yearRules('start_month'),
            'start_month' => $this->monthRules(),
            'end_year' => $this->yearRules('end_month'),
            'end_month' => $this->monthRules(),
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->ensureEndIsNotBeforeStart($validator);
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->yearMonthMessages(),
            'name.required' => 'El nombre del proyecto es obligatorio.',
            'url.url' => 'La URL del proyecto no tiene un formato válido.',
            'skill_ids.*.exists' => 'Una de las habilidades seleccionadas ya no existe.',
        ];
    }
}
