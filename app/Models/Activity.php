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

/**
 * @property int $id
 * @property int $training_id
 * @property int $exercise_id
 * @property int|null $program_item_id
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $sequences_count
 * @property-read string $sequences_formatted
 * @property-read Exercise $exercise
 * @property-read ProgramItem|null $programItem
 * @property-read Training $training
 * @property-read Collection<int, Sequence> $sequences
 */
#[Fillable(['training_id', 'exercise_id', 'program_item_id', 'completed_at'])]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Whether the user marked the activity as done.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Mark the activity as done, keeping the time it was ticked off.
     */
    public function complete(): void
    {
        $this->update(['completed_at' => now()]);
    }

    /**
     * Put the activity back on the to-do list.
     */
    public function reopen(): void
    {
        $this->update(['completed_at' => null]);
    }

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

        return trans_choice(':count sequence|:count sequences', $count, ['count' => $count]);
    }

    /**
     * @return BelongsTo<Exercise, $this>
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * @return BelongsTo<ProgramItem, $this>
     */
    public function programItem(): BelongsTo
    {
        return $this->belongsTo(ProgramItem::class);
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
