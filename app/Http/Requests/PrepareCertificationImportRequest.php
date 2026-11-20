<?php

namespace App\Http\Requests;

use App\Support\PdfFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class PrepareCertificationImportRequest extends FormRequest
{
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
            'file' => ['required', 'file', 'mimetypes:application/pdf', 'max:'.PdfFile::MAX_KILOBYTES],
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
            'file.required' => 'Seleccioná un único archivo PDF.',
            'file.mimetypes' => 'El archivo debe ser un PDF.',
            'file.max' => 'El PDF supera el límite de 20 MB.',
        ];

        $file = $this->file('file');

        if ($file instanceof UploadedFile && $file->getError() === UPLOAD_ERR_INI_SIZE) {
            $messages['file.uploaded'] = 'El servidor rechazó la carga por su tamaño; revisá upload_max_filesize y post_max_size.';
            $messages['file.required'] = $messages['file.uploaded'];
        }

        return $messages;
    }
}
