<?php

namespace App\Http\Requests;

use App\Models\Profile;
use App\Support\ProfilePhoto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class UpdateProfilePhotoRequest extends FormRequest
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
        $profile = Profile::current();
        $hasPhoto = is_string($profile->photo_path) && $profile->photo_path !== '';

        return [
            'photo' => [
                $hasPhoto ? 'nullable' : 'required',
                'file',
                'mimetypes:image/jpeg,image/png',
                'max:'.ProfilePhoto::MAX_KILOBYTES,
            ],
            'photo_focus_x' => ['required', 'integer', 'between:0,100'],
            'photo_focus_y' => ['required', 'integer', 'between:0,100'],
            'photo_zoom' => [
                'required',
                'integer',
                'between:'.ProfilePhoto::MIN_ZOOM.','.ProfilePhoto::MAX_ZOOM,
            ],
        ];
    }

    /**
     * Validate the real image content, not only the declared MIME type.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('photo');

            if (! $file instanceof UploadedFile || $validator->errors()->has('photo')) {
                return;
            }

            $error = ProfilePhoto::validationError($file);

            if ($error !== null) {
                $validator->errors()->add('photo', $error);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [
            'photo.required' => 'Seleccioná una fotografía JPG, JPEG o PNG.',
            'photo.file' => 'No se pudo cargar la fotografía.',
            'photo.mimetypes' => 'La fotografía debe ser JPG, JPEG o PNG.',
            'photo.max' => 'La fotografía supera el límite de 5 MB.',
            'photo_focus_x.required' => 'Indicá la posición horizontal del encuadre.',
            'photo_focus_x.between' => 'La posición horizontal no es válida.',
            'photo_focus_y.required' => 'Indicá la posición vertical del encuadre.',
            'photo_focus_y.between' => 'La posición vertical no es válida.',
            'photo_zoom.required' => 'Indicá el zoom del encuadre.',
            'photo_zoom.between' => 'El zoom del encuadre no es válido.',
        ];

        $file = $this->file('photo');

        if ($file instanceof UploadedFile && $file->getError() === UPLOAD_ERR_INI_SIZE) {
            $messages['photo.uploaded'] = 'El servidor rechazó la carga por su tamaño; revisá upload_max_filesize y post_max_size.';
            $messages['photo.required'] = $messages['photo.uploaded'];
        }

        return $messages;
    }
}
