<?php

namespace App\Http\Requests;

use App\Support\DocumentEntities;
use App\Support\PdfFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDocumentRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimetypes:application/pdf', 'max:'.PdfFile::MAX_KILOBYTES],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'linkable_type' => ['nullable', Rule::in(DocumentEntities::TYPES)],
            'linkable_id' => $this->linkableIdRules(),
        ];
    }

    /**
     * FR-002: the file must be a structurally valid PDF (header and
     * EOF marker), without adding a PDF parser dependency.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('file');

            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                return;
            }

            $contents = (string) file_get_contents($file->getRealPath() ?: '');

            if (! PdfFile::looksLikePdf($contents)) {
                $validator->errors()->add('file', 'El archivo debe ser un PDF válido.');
            }
        });
    }

    /**
     * @return array<int, mixed>
     */
    private function linkableIdRules(): array
    {
        $rules = ['nullable', 'integer', 'required_with:linkable_type'];
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
        $messages = [
            'file.required' => 'Seleccioná un archivo PDF.',
            'file.mimetypes' => 'El archivo debe ser un PDF.',
            'file.max' => 'El PDF supera el límite de 20 MB.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'description.max' => 'La descripción no puede superar los 2000 caracteres.',
            'linkable_type.in' => 'El tipo de entidad seleccionado no es válido.',
            'linkable_type.required_with' => 'Seleccioná el tipo de entidad para asociar.',
            'linkable_id.required_with' => 'Seleccioná una entidad para asociar el documento.',
            'linkable_id.exists' => 'La entidad seleccionada ya no existe.',
        ];

        $file = $this->file('file');

        if ($file instanceof UploadedFile && $file->getError() === UPLOAD_ERR_INI_SIZE) {
            $messages['file.uploaded'] = 'El servidor rechazó la carga por su tamaño; revisá upload_max_filesize y post_max_size.';
            $messages['file.required'] = $messages['file.uploaded'];
        }

        return $messages;
    }
}
