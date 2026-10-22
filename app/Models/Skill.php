<?php

namespace App\Models;

use App\Support\NameNormalizer;
use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory;

    /**
     * Fixed category set for the MVP (FR-019). Technologies used
     * professionally are registered here with the "tecnologia" category.
     */
    public const CATEGORIES = [
        'tecnica',
        'funcional',
        'metodologica',
        'herramienta',
        'tecnologia',
    ];

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (Skill $skill): void {
            $skill->normalized_name = NameNormalizer::normalize($skill->name);
        });
    }

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * @return BelongsToMany<WorkExperience, $this>
     */
    public function workExperiences(): BelongsToMany
    {
        return $this->belongsToMany(WorkExperience::class, 'skill_work_experience');
    }

    /**
     * @return BelongsToMany<Certification, $this>
     */
    public function certifications(): BelongsToMany
    {
        return $this->belongsToMany(Certification::class, 'skill_certification');
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'skill_project');
    }

    /**
     * Master order for skills: category order, then name ascending.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeMasterOrder(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CASE category
                WHEN 'tecnica' THEN 1
                WHEN 'funcional' THEN 2
                WHEN 'metodologica' THEN 3
                WHEN 'herramienta' THEN 4
                WHEN 'tecnologia' THEN 5
                ELSE 6 END")
            ->orderBy('name');
    }
}
