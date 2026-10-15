<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesYearMonth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEducationRequest extends FormRequest
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
            'institution' => ['required', 'string', 'max:255'],
            'program' => ['required', 'string', 'max:255'],
            'degree_level' => ['nullable', 'string', 'max:100'],
            'start_year' => $this->yearRules('start_month'),
            'start_month' => $this->monthRules(),
            'end_year' => $this->yearRules('end_month'),
            'end_month' => $this->monthRules(),
            'is_in_progress' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
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
            'institution.required' => 'La institución es obligatoria.',
            'program.required' => 'El programa o carrera es obligatorio.',
        ];
    }
}
