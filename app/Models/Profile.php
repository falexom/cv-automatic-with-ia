<?php

namespace App\Models;

use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * Internal photo paths never leave the server.
     *
     * @var list<string>
     */
    protected $hidden = ['photo_path', 'photo_mime'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'photo_focus_x' => 'integer',
            'photo_focus_y' => 'integer',
            'photo_zoom' => 'integer',
        ];
    }

    /**
     * The single master profile (FR-001). Created on first access.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    /**
     * @return HasMany<ProfileLink, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(ProfileLink::class);
    }

    /**
     * @return HasMany<WorkExperience, $this>
     */
    public function workExperiences(): HasMany
    {
        return $this->hasMany(WorkExperience::class);
    }

    /**
     * @return HasMany<EducationEntry, $this>
     */
    public function educationEntries(): HasMany
    {
        return $this->hasMany(EducationEntry::class);
    }

    /**
     * @return HasMany<Certification, $this>
     */
    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class);
    }

    /**
     * @return HasMany<Skill, $this>
     */
    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
