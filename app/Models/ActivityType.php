<?php

namespace App\Models;

use Database\Factories\ActivityTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property int|null $activities_count
 * @property-read string $activities_formatted
 * @property-read User $user
 * @property-read Collection<int, Activity> $activities
 */
#[Fillable(['type', 'user_id'])]
#[WithoutTimestamps]
class ActivityType extends Model
{
    /** @use HasFactory<ActivityTypeFactory> */
    use HasFactory;

    /**
     * A pluralized count of the activities recorded for this type.
     *
     * @return Attribute<string, never>
     */
    protected function activitiesFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatActivitiesCount());
    }

    /**
     * Build the pluralized activity count, preferring an eager-loaded count.
     */
    private function formatActivitiesCount(): string
    {
        $count = $this->activities_count ?? $this->activities->count();

        return $count.' '.Str::plural('activity', $count);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
