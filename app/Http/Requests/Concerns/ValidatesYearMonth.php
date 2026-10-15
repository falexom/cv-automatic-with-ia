<?php

namespace App\Http\Requests\Concerns;

use App\Support\YearMonth;
use Illuminate\Validation\Validator;

trait ValidatesYearMonth
{
    /**
     * Year rules: optional, but required whenever its month is present.
     *
     * @return array<int, string>
     */
    protected function yearRules(string $monthField): array
    {
        return [
            'nullable',
            'integer',
            'between:1900,'.YearMonth::maxYear(),
            'required_with:'.$monthField,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function monthRules(): array
    {
        return ['nullable', 'integer', 'between:1,12'];
    }

    /**
     * Enforce FR-034/FR-009: the end date cannot be earlier than the start.
     */
    protected function ensureEndIsNotBeforeStart(Validator $validator, string $endYearField = 'end_year'): void
    {
        $isBefore = YearMonth::endIsBeforeStart(
            $this->intOrNull($this->input('start_year')),
            $this->intOrNull($this->input('start_month')),
            $this->intOrNull($this->input('end_year')),
            $this->intOrNull($this->input('end_month')),
        );

        if ($isBefore) {
            $validator->errors()->add(
                $endYearField,
                'La fecha de fin no puede ser anterior a la fecha de inicio.',
            );
        }
    }

    /**
     * @return array<string, string>
     */
    protected function yearMonthMessages(): array
    {
        return [
            'start_year.required_with' => 'El año es obligatorio cuando se indica un mes.',
            'end_year.required_with' => 'El año es obligatorio cuando se indica un mes.',
            'date_year.required_with' => 'El año es obligatorio cuando se indica un mes.',
            'start_year.between' => 'El año debe estar entre 1900 y :max.',
            'end_year.between' => 'El año debe estar entre 1900 y :max.',
            'date_year.between' => 'El año debe estar entre 1900 y :max.',
            'start_month.between' => 'El mes debe estar entre 1 y 12.',
            'end_month.between' => 'El mes debe estar entre 1 y 12.',
            'date_month.between' => 'El mes debe estar entre 1 y 12.',
        ];
    }

    protected function intOrNull(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
