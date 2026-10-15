<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\WorkExperience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkExperience>
 */
class WorkExperienceFactory extends Factory
{
    protected $model = WorkExperience::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => fn () => Profile::current()->id,
            'company' => fake()->company(),
            'position' => fake()->jobTitle(),
            'start_year' => 2020,
            'start_month' => 3,
            'end_year' => 2022,
            'end_month' => 6,
            'is_current' => false,
            'description' => 'Desarrollo de sistemas internos.',
            'responsibilities' => 'Relevamiento de requerimientos y documentación.',
            'achievements' => 'Reducción de tiempos de proceso en un 30%.',
        ];
    }

    public function yearOnly(): static
    {
        return $this->state(fn (): array => [
            'start_month' => null,
            'end_year' => null,
            'end_month' => null,
            'is_current' => false,
        ]);
    }

    public function current(): static
    {
        return $this->state(fn (): array => [
            'end_year' => null,
            'end_month' => null,
            'is_current' => true,
        ]);
    }
}
