<?php

namespace Database\Seeders;

use App\Models\Metric;
use App\Models\User;
use Illuminate\Database\Seeder;

class MetricSeeder extends Seeder
{
    /**
     * Give every user a series of weight measurements.
     */
    public function run(): void
    {
        User::each(fn (User $user) => Metric::factory()
            ->count(20)
            ->for($user)
            ->create());
    }
}
