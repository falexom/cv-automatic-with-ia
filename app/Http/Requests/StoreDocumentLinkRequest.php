<?php

namespace App\Http\Requests;

use App\Models\Document;
use App\Support\DocumentEntities;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDocumentLinkRequest extends FormRequest
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
            'linkable_type' => ['required', Rule::in(DocumentEntities::TYPES)],
            'linkable_id' => $this->linkableIdRules(),
        ];
    }

    /**
     * FR-011/FR-009: the same document cannot be linked twice to the
     * same entity.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $document = $this->route('document');
            $type = (string) $this->input('linkable_type');
            $id = $this->input('linkable_id');

            if (! $document instanceof Document
                || ! DocumentEntities::isValidType($type)
                || ! is_numeric($id)) {
                return;
            }

            $entity = DocumentEntities::resolve($type, (int) $id);

            if ($entity === null) {
                return;
            }

            if ($document->relationFor($type)->whereKey($entity->getKey())->exists()) {
                $validator->errors()->add('linkable_id', 'Este documento ya está asociado a esa entidad.');
            }
        });
    }

    /**
     * @return array<int, mixed>
     */
    private function linkableIdRules(): array
    {
        $rules = ['required', 'integer'];
        $type = (string) $this->input('linkable_type');

        if (DocumentEntities::isValidType($type)) {
            $rules[] = Rule::exists(DocumentEntities::table($type), 'id');
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'linkable_type.required' => 'Seleccioná el tipo de entidad.',
            'linkable_type.in' => 'El tipo de entidad seleccionado no es válido.',
            'linkable_id.required' => 'Seleccioná la entidad a asociar.',
            'linkable_id.exists' => 'La entidad seleccionada ya no existe.',
        ];
    }
}
