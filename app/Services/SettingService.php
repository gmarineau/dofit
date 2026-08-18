<?php

namespace App\Services;

use App\Models\User;

class SettingService
{
    /**
     * Get the value of one of the user's settings, or the given default when
     * the user has no such setting.
     */
    public function get(User $user, string $key, string $default = ''): string
    {
        return $user->settings()
            ->where('key', $key)
            ->value('value') ?? $default;
    }
}
