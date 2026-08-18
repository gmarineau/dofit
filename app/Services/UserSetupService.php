<?php

namespace App\Services;

use App\Models\User;

class UserSetupService
{
    /**
     * The settings every user starts with.
     *
     * @var list<array{key: string, value: string, type: string}>
     */
    public const array DEFAULT_SETTINGS = [
        ['key' => 'repetition', 'value' => '10', 'type' => 'number'],
        ['key' => 'weight', 'value' => 'kg', 'type' => 'text'],
    ];

    /**
     * Give a user the settings they need to start logging trainings. Exercises
     * come from the shared library, so there is nothing to seed there. Safe to
     * run more than once for the same user.
     */
    public function setUp(User $user): void
    {
        $this->createDefaultSettings($user);
    }

    /**
     * Create the default settings for a user.
     */
    public function createDefaultSettings(User $user): void
    {
        foreach (self::DEFAULT_SETTINGS as $setting) {
            $user->settings()->firstOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'type' => $setting['type']],
            );
        }
    }
}
