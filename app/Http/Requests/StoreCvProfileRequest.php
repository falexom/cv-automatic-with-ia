<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCvProfile;
use Illuminate\Foundation\Http\FormRequest;

class StoreCvProfileRequest extends FormRequest
{
    use ValidatesCvProfile;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->cvProfileRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->cvProfileMessages();
    }

    protected function prepareForValidation(): void
    {
        $this->prepareCvProfileForValidation();
    }
}
