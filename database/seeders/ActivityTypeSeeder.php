<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\UserSetupService;
use Illuminate\Database\Seeder;

class ActivityTypeSeeder extends Seeder
{
    public function __construct(protected UserSetupService $userSetup) {}

    /**
     * Give every user the default set of activity types.
     */
    public function run(): void
    {
        User::each(fn (User $user) => $this->userSetup->createDefaultActivityTypes($user));
    }
}
