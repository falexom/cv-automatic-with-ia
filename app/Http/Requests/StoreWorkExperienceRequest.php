<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesYearMonth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWorkExperienceRequest extends FormRequest
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
            'company' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'start_year' => $this->yearRules('start_month'),
            'start_month' => $this->monthRules(),
            'end_year' => $this->yearRules('end_month'),
            'end_month' => $this->monthRules(),
            'is_current' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'achievements' => ['nullable', 'string'],
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
            'company.required' => 'La empresa es obligatoria.',
            'position.required' => 'El cargo es obligatorio.',
            'skill_ids.*.exists' => 'Una de las habilidades seleccionadas ya no existe.',
        ];
    }
}
