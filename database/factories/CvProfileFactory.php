<?php

namespace Database\Factories;

use App\Models\CvProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CvProfile>
 */
class CvProfileFactory extends Factory
{
    protected $model = CvProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = $this->faker->unique();
        $words = $faker->words(2, true);
        $name = is_array($words) ? implode(' ', $words) : $words;

        return [
            'name' => ucfirst($name),
            'professional_title' => $faker->jobTitle(),
            'summary' => $faker->sentence(),
            'is_active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
