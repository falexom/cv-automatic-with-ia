<?php

namespace App\Models;

use App\Support\NameNormalizer;
use Database\Factories\CvProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CvProfile extends Model
{
    /** @use HasFactory<CvProfileFactory> */
    use HasFactory;

    public const RELEVANCES = ['alta', 'media', 'baja'];

    public const RELEVANCE_WEIGHTS = [
        'alta' => 3,
        'media' => 2,
        'baja' => 1,
    ];

    protected $table = 'cv_profiles';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CvProfile $profile): void {
            $profile->normalized_name = NameNormalizer::normalize($profile->name);
        });
    }

    /**
     * @return BelongsToMany<WorkExperience, $this>
     */
    public function workExperiences(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkExperience::class,
            'cv_profile_work_experience',
            'cv_profile_id',
            'work_experience_id',
        )
            ->withPivot('relevance')
            ->orderByRaw(self::relevanceOrder('cv_profile_work_experience'))
            ->orderByDesc('work_experiences.start_year')
            ->orderByRaw('COALESCE(work_experiences.start_month, 1) DESC')
            ->orderByDesc('work_experiences.id');
    }

    /**
     * @return BelongsToMany<EducationEntry, $this>
     */
    public function educationEntries(): BelongsToMany
    {
        return $this->belongsToMany(
            EducationEntry::class,
            'cv_profile_education_entry',
            'cv_profile_id',
            'education_entry_id',
        )
            ->withPivot('relevance')
            ->orderByRaw(self::relevanceOrder('cv_profile_education_entry'))
            ->orderByDesc('education_entries.start_year')
            ->orderByRaw('COALESCE(education_entries.start_month, 1) DESC')
            ->orderByDesc('education_entries.id');
    }

    /**
     * @return BelongsToMany<Certification, $this>
     */
    public function certifications(): BelongsToMany
    {
        return $this->belongsToMany(
            Certification::class,
            'cv_profile_certification',
            'cv_profile_id',
            'certification_id',
        )
            ->withPivot('relevance')
            ->orderByRaw(self::relevanceOrder('cv_profile_certification'))
            ->orderByDesc('certifications.date_year')
            ->orderByRaw('COALESCE(certifications.date_month, 1) DESC')
            ->orderByDesc('certifications.id');
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(
            Skill::class,
            'cv_profile_skill',
            'cv_profile_id',
            'skill_id',
        )
            ->withPivot('relevance')
            ->orderByRaw(self::relevanceOrder('cv_profile_skill'))
            ->orderByRaw("CASE skills.category
                WHEN 'tecnica' THEN 1
                WHEN 'funcional' THEN 2
                WHEN 'metodologica' THEN 3
                WHEN 'herramienta' THEN 4
                WHEN 'tecnologia' THEN 5
                ELSE 6 END")
            ->orderBy('skills.name');
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(
            Project::class,
            'cv_profile_project',
            'cv_profile_id',
            'project_id',
        )
            ->withPivot('relevance')
            ->orderByRaw(self::relevanceOrder('cv_profile_project'))
            ->orderByDesc('projects.start_year')
            ->orderByRaw('COALESCE(projects.start_month, 1) DESC')
            ->orderByDesc('projects.id');
    }

    /**
     * @return HasMany<CvAdaptation, $this>
     */
    public function adaptations(): HasMany
    {
        return $this->hasMany(CvAdaptation::class);
    }

    private static function relevanceOrder(string $pivot): string
    {
        return "CASE {$pivot}.relevance
            WHEN 'alta' THEN 3
            WHEN 'media' THEN 2
            WHEN 'baja' THEN 1
            ELSE 0 END DESC";
    }
}
