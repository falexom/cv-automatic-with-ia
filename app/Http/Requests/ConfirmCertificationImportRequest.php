<?php

namespace App\Http\Requests;

use App\Support\PdfFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class ConfirmCertificationImportRequest extends StoreCertificationRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'file' => ['required', 'file', 'mimetypes:application/pdf', 'max:'.PdfFile::MAX_KILOBYTES],
            'confirmation_id' => ['required_without:proposal_token', 'nullable', 'uuid'],
            'proposal_token' => ['nullable', 'string', 'max:200000'],
            'accepted_category' => ['sometimes', 'boolean'],
            'accepted_skill_ids' => ['nullable', 'array'],
            'accepted_skill_ids.*' => ['integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('file');

            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                return;
            }

            $contents = (string) file_get_contents($file->getRealPath() ?: '');

            if (! PdfFile::looksLikePdf($contents)) {
                $validator->errors()->add('file', 'El archivo debe ser un PDF válido y completo.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [
            ...parent::messages(),
            'file.required' => 'Seleccioná el PDF original antes de confirmar.',
            'file.mimetypes' => 'El archivo debe ser un PDF.',
            'file.max' => 'El PDF supera el límite de 20 MB.',
            'confirmation_id.required_without' => 'La confirmación no es válida. Volvé a intentar la operación.',
            'confirmation_id.uuid' => 'La confirmación no es válida. Volvé a intentar la operación.',
            'proposal_token.max' => 'La propuesta ya no es válida. Volvé a analizar el PDF.',
            'accepted_skill_ids.array' => 'La aceptación de habilidades no es válida.',
        ];

        $file = $this->file('file');

        if ($file instanceof UploadedFile && $file->getError() === UPLOAD_ERR_INI_SIZE) {
            $messages['file.uploaded'] = 'El servidor rechazó la carga por su tamaño; revisá upload_max_filesize y post_max_size.';
            $messages['file.required'] = $messages['file.uploaded'];
        }

        return $messages;
    }
}
