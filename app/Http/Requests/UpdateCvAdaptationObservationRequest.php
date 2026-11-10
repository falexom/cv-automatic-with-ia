<?php

namespace App\Http\Requests;

use App\Models\CvAdaptation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCvAdaptationObservationRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(CvAdaptation::OBSERVATION_STATUSES)],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El estado de la observación es obligatorio.',
            'status.in' => 'El estado de la observación no es válido.',
            'note.string' => 'La nota debe ser texto.',
            'note.max' => 'La nota no puede superar los 2.000 caracteres.',
        ];
    }
}
