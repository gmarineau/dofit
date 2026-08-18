<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    /**
     * Determine whether the user can view the setting.
     */
    public function view(User $user, Setting $setting): bool
    {
        return $user->id === $setting->user_id;
    }

    /**
     * Determine whether the user can update the setting.
     */
    public function update(User $user, Setting $setting): bool
    {
        return $user->id === $setting->user_id;
    }
}
