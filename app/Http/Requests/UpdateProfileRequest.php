<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:255'],
            'professional_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:2048'],
            'github_url' => ['nullable', 'url', 'max:2048'],
            'website_url' => ['nullable', 'url', 'max:2048'],
            'summary' => ['nullable', 'string'],
            'links' => ['nullable', 'array'],
            'links.*.label' => ['required', 'string', 'max:100'],
            'links.*.url' => ['required', 'url', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'El nombre completo es obligatorio.',
            'full_name.max' => 'El nombre completo no puede superar los 255 caracteres.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'linkedin_url.url' => 'El enlace de LinkedIn no tiene un formato válido.',
            'github_url.url' => 'El enlace de GitHub no tiene un formato válido.',
            'website_url.url' => 'El sitio web no tiene un formato válido.',
            'links.*.label.required' => 'Cada enlace adicional necesita una etiqueta.',
            'links.*.url.required' => 'Cada enlace adicional necesita una dirección.',
            'links.*.url.url' => 'La dirección del enlace no tiene un formato válido.',
        ];
    }
}
