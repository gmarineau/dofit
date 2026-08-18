<?php

namespace App\Services;

use App\Models\Activity;

class SequenceService
{
    /**
     * Get the weight of the activity's most recent sequence, so a new sequence
     * can be pre-filled with it. Null when the activity has no sequence yet.
     */
    public function getLastWeight(Activity $activity): ?float
    {
        return $activity->sequences()
            ->latest('id')
            ->first()
            ?->weight;
    }
}
