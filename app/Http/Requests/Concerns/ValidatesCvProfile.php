<?php

namespace App\Http\Requests\Concerns;

use App\Models\CvProfile;
use App\Models\Profile;
use Illuminate\Validation\Rule;

trait ValidatesCvProfile
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function cvProfileRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'professional_title' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            ...$this->cvProfileContentRules(),
        ];
    }

    /**
     * Shared selection rules for the five master content classes.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function cvProfileContentRules(): array
    {
        $masterId = Profile::current()->getKey();

        $rules = [];

        foreach (self::contentTables() as $field => $table) {
            $rules[$field] = ['sometimes', 'array'];
            $rules[$field.'.*.id'] = [
                'required',
                'integer',
                'distinct',
                Rule::exists($table, 'id')->where('profile_id', $masterId),
            ];
            $rules[$field.'.*.relevance'] = [
                'sometimes',
                Rule::in(CvProfile::RELEVANCES),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function cvProfileMessages(): array
    {
        $messages = [
            'name.required' => 'El nombre del perfil es obligatorio.',
            'name.string' => 'El nombre del perfil debe ser texto.',
            'name.max' => 'El nombre del perfil no puede superar los 100 caracteres.',
            'professional_title.string' => 'El título profesional debe ser texto.',
            'professional_title.max' => 'El título profesional no puede superar los 255 caracteres.',
            'summary.string' => 'El resumen debe ser texto.',
        ];

        foreach (array_keys(self::contentTables()) as $field) {
            $messages[$field.'.array'] = 'La selección enviada no es válida.';
            $messages[$field.'.*.id.required'] = 'Cada elemento seleccionado debe tener un identificador.';
            $messages[$field.'.*.id.integer'] = 'El identificador del elemento no es válido.';
            $messages[$field.'.*.id.distinct'] = 'No se puede incluir el mismo elemento dos veces.';
            $messages[$field.'.*.id.exists'] = 'El elemento seleccionado no pertenece al Perfil Maestro o ya no existe.';
            $messages[$field.'.*.relevance.in'] = 'La relevancia debe ser Alta, Media o Baja.';
        }

        return $messages;
    }

    protected function prepareCvProfileForValidation(): void
    {
        $name = $this->input('name');

        if (is_string($name)) {
            $this->merge(['name' => trim($name)]);
        }

        foreach (['professional_title', 'summary'] as $field) {
            $value = $this->input($field);

            if (is_string($value) && trim($value) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    protected static function contentTables(): array
    {
        return [
            'work_experiences' => 'work_experiences',
            'education_entries' => 'education_entries',
            'certifications' => 'certifications',
            'skills' => 'skills',
            'projects' => 'projects',
        ];
    }
}
