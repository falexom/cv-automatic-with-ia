<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => 'Ada Lovelace',
            'professional_title' => 'Analista de Sistemas',
            'phone' => '+54 11 5555 1234',
            'email' => 'ada@example.com',
            'location' => 'Buenos Aires, Argentina',
            'linkedin_url' => 'https://www.linkedin.com/in/ada',
            'github_url' => 'https://github.com/ada',
            'website_url' => 'https://ada.example.com',
            'summary' => 'Analista de sistemas con experiencia en procesos y datos.',
        ];
    }
}
