<?php

namespace App\Http\Requests;

use App\Models\CvProfile;
use App\Support\CvAdaptation\CvAdaptationReferences;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyCvAdaptationRequest extends FormRequest
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
        $rules = [
            'operation' => ['required', 'string', Rule::in(['reuse', 'duplicate', 'create'])],
            'ref_nonce' => ['required', 'string', 'regex:/^[a-f0-9]{32}$/'],
            'source_profile_ref' => [
                'nullable',
                'string',
                'regex:/^cv_[a-f0-9]{12}$/',
                Rule::requiredIf(fn (): bool => in_array($this->input('operation'), ['reuse', 'duplicate'], true)),
                Rule::prohibitedIf(fn (): bool => $this->input('operation') === 'create'),
            ],
            'profile_details' => [
                'nullable',
                'array',
                Rule::requiredIf(fn (): bool => in_array($this->input('operation'), ['duplicate', 'create'], true)),
                Rule::prohibitedIf(fn (): bool => $this->input('operation') === 'reuse'),
            ],
            'profile_details.name' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf(fn (): bool => in_array($this->input('operation'), ['duplicate', 'create'], true)),
            ],
            'profile_details.professional_title' => ['nullable', 'string', 'max:255'],
            'profile_details.summary' => ['nullable', 'string'],
            'selected_content' => [
                'nullable',
                'array',
                Rule::requiredIf(fn (): bool => in_array($this->input('operation'), ['duplicate', 'create'], true)),
                Rule::prohibitedIf(fn (): bool => $this->input('operation') === 'reuse'),
            ],
        ];

        foreach (self::contentFields() as $field => $prefix) {
            $rules["selected_content.{$field}"] = [
                'array',
                Rule::requiredIf(fn (): bool => in_array($this->input('operation'), ['duplicate', 'create'], true)),
            ];
            $rules["selected_content.{$field}.*"] = ['required', 'array'];
            $rules["selected_content.{$field}.*.ref"] = [
                'required',
                'string',
                "regex:/^{$prefix}_[a-f0-9]{12}$/",
            ];
            $rules["selected_content.{$field}.*.relevance"] = [
                'required',
                'string',
                Rule::in(CvProfile::RELEVANCES),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [
            'operation.required' => 'La operación es obligatoria.',
            'operation.in' => 'La operación seleccionada no es válida.',
            'ref_nonce.required' => 'La propuesta no es válida. Volvé a analizar la oferta.',
            'ref_nonce.regex' => 'La propuesta no es válida. Volvé a analizar la oferta.',
            'source_profile_ref.required' => 'La propuesta no incluye el perfil de origen.',
            'source_profile_ref.regex' => 'La referencia del perfil de origen no es válida.',
            'source_profile_ref.prohibited' => 'La operación de creación no puede tener un perfil de origen.',
            'profile_details.required' => 'La propuesta no incluye los datos del perfil nuevo.',
            'profile_details.prohibited' => 'La operación de reutilización no modifica el perfil.',
            'profile_details.name.required' => 'El nombre del perfil es obligatorio.',
            'profile_details.name.string' => 'El nombre del perfil debe ser texto.',
            'profile_details.name.max' => 'El nombre del perfil no puede superar los 100 caracteres.',
            'profile_details.professional_title.string' => 'El título profesional debe ser texto.',
            'profile_details.professional_title.max' => 'El título profesional no puede superar los 255 caracteres.',
            'profile_details.summary.string' => 'El resumen debe ser texto.',
            'selected_content.required' => 'La propuesta no incluye la selección de contenido.',
            'selected_content.prohibited' => 'La operación de reutilización no modifica el perfil.',
        ];

        foreach (self::contentFields() as $field => $prefix) {
            $messages["selected_content.{$field}.required"] = 'La propuesta no incluye la selección de contenido.';
            $messages["selected_content.{$field}.array"] = 'La selección enviada no es válida.';
            $messages["selected_content.{$field}.*.array"] = 'Cada elemento seleccionado debe ser válido.';
            $messages["selected_content.{$field}.*.ref.required"] = 'Cada elemento seleccionado debe tener una referencia.';
            $messages["selected_content.{$field}.*.ref.regex"] = 'La referencia de un elemento seleccionado no es válida.';
            $messages["selected_content.{$field}.*.relevance.in"] = 'La relevancia debe ser Alta, Media o Baja.';
        }

        return $messages;
    }

    /**
     * @return array<string, string>
     */
    private static function contentFields(): array
    {
        return CvAdaptationReferences::CONTENT_PREFIXES;
    }
}
