<?php

namespace App\Models;

use Database\Factories\ActivityFactory;
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
 * @property int $training_id
 * @property int $activity_type_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $sequences_count
 * @property-read string $sequences_formatted
 * @property-read ActivityType $activityType
 * @property-read Training $training
 * @property-read Collection<int, Sequence> $sequences
 */
#[Fillable(['training_id', 'activity_type_id'])]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    /**
     * A pluralized count of the activity's sequences.
     *
     * @return Attribute<string, never>
     */
    protected function sequencesFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatSequencesCount());
    }

    /**
     * Build the pluralized sequence count, preferring an eager-loaded count.
     */
    private function formatSequencesCount(): string
    {
        $count = $this->sequences_count ?? $this->sequences->count();

        return $count.' '.Str::plural('sequence', $count);
    }

    /**
     * @return BelongsTo<ActivityType, $this>
     */
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    /**
     * @return BelongsTo<Training, $this>
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * @return HasMany<Sequence, $this>
     */
    public function sequences(): HasMany
    {
        return $this->hasMany(Sequence::class);
    }
}
