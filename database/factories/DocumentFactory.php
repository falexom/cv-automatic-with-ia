<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => fn () => Profile::current()->id,
            'original_filename' => 'certificado.pdf',
            'title' => null,
            'description' => null,
            'size_bytes' => 1024,
            'checksum' => hash('sha256', fake()->uuid()),
            'is_available' => true,
            'last_verified_at' => null,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (): array => [
            'is_available' => false,
        ]);
    }
}
