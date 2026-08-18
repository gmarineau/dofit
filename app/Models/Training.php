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
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $name
 * @property Carbon $date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $activities_count
 * @property-read string $name_formatted
 * @property-read string $activities_formatted
 * @property-read User $user
 * @property-read Collection<int, Activity> $activities
 */
#[Fillable(['name', 'date', 'user_id'])]
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
        ];
    }

    /**
     * The training date followed by its name, as shown in listings.
     *
     * @return Attribute<string, never>
     */
    protected function nameFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->date->format(config('dofit.date_format')).' - '.$this->name);
    }

    /**
     * A pluralized count of the training's activities.
     *
     * @return Attribute<string, never>
     */
    protected function activitiesFormatted(): Attribute
    {
        return Attribute::get(function (): string {
            $count = $this->activities_count ?? $this->activities->count();

            return $count.' '.Str::plural('activity', $count);
        });
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
