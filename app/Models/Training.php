<?php

namespace App\Models;

use Database\Factories\TrainingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $name
 * @property Carbon $date
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $activities_count
 * @property int|null $completed_activities_count
 * @property-read string $name_formatted
 * @property-read string $activities_formatted
 * @property-read string $progress_formatted
 * @property-read User $user
 * @property-read Collection<int, Activity> $activities
 */
#[Fillable(['name', 'date', 'user_id', 'completed_at'])]
class Training extends Model
{
    /** @use HasFactory<TrainingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Whether the user closed the session.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * The training date followed by its name, as shown in listings.
     *
     * @return Attribute<string, never>
     */
    protected function nameFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatName());
    }

    /**
     * Build the "date - name" label shown in listings.
     */
    private function formatName(): string
    {
        return $this->date->format(config('dofit.date_format')).' - '.$this->name;
    }

    /**
     * A pluralized count of the training's activities.
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

        return trans_choice(':count activity|:count activities', $count, ['count' => $count]);
    }

    /**
     * How far through the session the user is, as in "2/5".
     *
     * @return Attribute<string, never>
     */
    protected function progressFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatProgress());
    }

    /**
     * Build the "done/total" progress, preferring eager-loaded counts.
     */
    private function formatProgress(): string
    {
        $done = $this->completed_activities_count
            ?? $this->activities->filter(fn (Activity $activity): bool => $activity->isCompleted())->count();

        return $done.'/'.($this->activities_count ?? $this->activities->count());
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
