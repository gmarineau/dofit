<?php

namespace App\Policies;

use App\Models\Metric;
use App\Models\User;

class MetricPolicy
{
    /**
     * Determine whether the user can view the metric.
     */
    public function view(User $user, Metric $metric): bool
    {
        return $user->id === $metric->user_id;
    }

    /**
     * Determine whether the user can delete the metric.
     */
    public function delete(User $user, Metric $metric): bool
    {
        return $user->id === $metric->user_id;
    }
}
