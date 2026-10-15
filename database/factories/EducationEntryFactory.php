<?php

namespace Database\Factories;

use App\Models\EducationEntry;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EducationEntry>
 */
class EducationEntryFactory extends Factory
{
    protected $model = EducationEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => fn () => Profile::current()->id,
            'institution' => 'Universidad Nacional',
            'program' => 'Ingeniería en Sistemas',
            'degree_level' => 'Licenciatura',
            'start_year' => 2015,
            'start_month' => 2,
            'end_year' => 2020,
            'end_month' => 12,
            'is_in_progress' => false,
            'description' => 'Orientación en sistemas de información.',
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'end_year' => null,
            'end_month' => null,
            'is_in_progress' => true,
        ]);
    }
}
