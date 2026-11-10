<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvAdaptationObservation extends Model
{
    protected $guarded = [];

    /**
     * @return BelongsTo<CvAdaptation, $this>
     */
    public function cvAdaptation(): BelongsTo
    {
        return $this->belongsTo(CvAdaptation::class);
    }
}
