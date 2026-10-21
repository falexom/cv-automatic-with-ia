<?php

namespace App\Models;

use Database\Factories\EducationEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EducationEntry extends Model
{
    /** @use HasFactory<EducationEntryFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_in_progress' => 'boolean',
            'start_year' => 'integer',
            'start_month' => 'integer',
            'end_year' => 'integer',
            'end_month' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * @return BelongsToMany<Document, $this>
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_education_entry');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeChronological(Builder $query): Builder
    {
        return $query
            ->orderByDesc('start_year')
            ->orderByRaw('COALESCE(start_month, 1) DESC')
            ->orderByDesc('id');
    }
}
