<?php

namespace App\Services;

use App\Models\User;

class UserSetupService
{
    /**
     * The activity types every user starts with.
     *
     * @var list<string>
     */
    public const array DEFAULT_ACTIVITY_TYPES = [
        'Chest Press',
        'Pull Down',
        'Biceps',
        'Delta Arriere',
    ];

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
     * Give a user the activity types and settings they need to start logging
     * trainings. Safe to run more than once for the same user.
     */
    public function setUp(User $user): void
    {
        $this->createDefaultActivityTypes($user);
        $this->createDefaultSettings($user);
    }

    /**
     * Create the default activity types for a user.
     */
    public function createDefaultActivityTypes(User $user): void
    {
        foreach (self::DEFAULT_ACTIVITY_TYPES as $type) {
            $user->activityTypes()->firstOrCreate(['type' => $type]);
        }
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
