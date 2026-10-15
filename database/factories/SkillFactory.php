<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    protected $model = Skill::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => fn () => Profile::current()->id,
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'category' => fake()->randomElement(Skill::CATEGORIES),
        ];
    }
}
