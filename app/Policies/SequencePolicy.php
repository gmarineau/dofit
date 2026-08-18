<?php

namespace App\Policies;

use App\Models\Sequence;
use App\Models\User;

class SequencePolicy
{
    /**
     * Determine whether the user can view the sequence.
     */
    public function view(User $user, Sequence $sequence): bool
    {
        return $user->id === $sequence->activity->training->user_id;
    }

    /**
     * Determine whether the user can delete the sequence.
     */
    public function delete(User $user, Sequence $sequence): bool
    {
        return $user->id === $sequence->activity->training->user_id;
    }
}
