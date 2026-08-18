<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\UserSetupService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function __construct(protected UserSetupService $userSetup) {}

    /**
     * Give every user the default set of settings.
     */
    public function run(): void
    {
        User::each(fn (User $user) => $this->userSetup->createDefaultSettings($user));
    }
}
