<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\ProfileLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfileLink>
 */
class ProfileLinkFactory extends Factory
{
    protected $model = ProfileLink::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => fn () => Profile::current()->id,
            'label' => 'Blog personal',
            'url' => 'https://blog.example.com',
        ];
    }
}
