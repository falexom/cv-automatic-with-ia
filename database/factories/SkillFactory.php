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
        $faker = $this->faker->unique();

        return [
            'profile_id' => fn () => Profile::current()->id,
            'name' => ucfirst($faker->word().' '.$faker->word()),
            'category' => $this->faker->randomElement(Skill::CATEGORIES),
        ];
    }
}
