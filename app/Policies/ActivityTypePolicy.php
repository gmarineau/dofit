<?php

namespace App\Policies;

use App\Models\ActivityType;
use App\Models\User;

class ActivityTypePolicy
{
    /**
     * Determine whether the user can view the activity type.
     */
    public function view(User $user, ActivityType $activityType): bool
    {
        return $user->id === $activityType->user_id;
    }

    /**
     * Determine whether the user can delete the activity type.
     */
    public function delete(User $user, ActivityType $activityType): bool
    {
        return $user->id === $activityType->user_id;
    }
}
