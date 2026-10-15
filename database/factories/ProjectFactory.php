<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => fn () => Profile::current()->id,
            'name' => 'Rediseño del portal interno',
            'description' => 'Migración del portal corporativo a una nueva plataforma.',
            'role' => 'Desarrolladora',
            'results' => 'Reducción del tiempo de carga en un 40%.',
            'url' => 'https://example.com/proyecto',
            'start_year' => 2021,
            'start_month' => 1,
            'end_year' => 2021,
            'end_month' => 9,
        ];
    }
}
