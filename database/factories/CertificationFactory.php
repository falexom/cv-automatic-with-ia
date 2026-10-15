<?php

namespace Database\Factories;

use App\Models\Certification;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certification>
 */
class CertificationFactory extends Factory
{
    protected $model = Certification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => fn () => Profile::current()->id,
            'name' => 'Curso de Laravel',
            'institution' => 'Laravel Academy',
            'date_year' => 2023,
            'date_month' => 6,
            'duration' => '40 horas',
            'code' => null,
            'validation_url' => 'https://laravel.example.com/validar/123',
            'category' => 'Programación',
        ];
    }

    public function withoutDate(): static
    {
        return $this->state(fn (): array => [
            'date_year' => null,
            'date_month' => null,
        ]);
    }
}
