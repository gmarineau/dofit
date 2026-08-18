<?php

namespace App\Policies;

use App\Models\Exercise;
use App\Models\User;

class ExercisePolicy
{
    /**
     * Determine whether the user can view the exercise. The shared library is
     * open to everyone; an exercise someone added is theirs alone.
     */
    public function view(User $user, Exercise $exercise): bool
    {
        return ! $exercise->isCustom() || $exercise->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the exercise.
     */
    public function update(User $user, Exercise $exercise): bool
    {
        return $exercise->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the exercise.
     */
    public function delete(User $user, Exercise $exercise): bool
    {
        return $exercise->user_id === $user->id;
    }
}
