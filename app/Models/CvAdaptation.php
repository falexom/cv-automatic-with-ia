<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CvAdaptation extends Model
{
    public const OPERATIONS = ['reuse', 'duplicate', 'create'];

    public const OBSERVATION_TYPES = ['gap', 'recommendation', 'question'];

    public const OBSERVATION_STATUSES = ['pending', 'resolved', 'discarded'];

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'analyzed_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<CvProfile, $this>
     */
    public function cvProfile(): BelongsTo
    {
        return $this->belongsTo(CvProfile::class);
    }

    /**
     * @return HasMany<CvAdaptationObservation, $this>
     */
    public function observations(): HasMany
    {
        return $this->hasMany(CvAdaptationObservation::class);
    }
}
