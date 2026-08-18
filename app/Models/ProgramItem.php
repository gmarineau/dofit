<?php

namespace App\Models;

use Database\Factories\ProgramItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One exercise inside a program, with the targets to aim for.
 *
 * @property int $id
 * @property int $program_id
 * @property int $activity_type_id
 * @property int $position
 * @property int|null $target_sets
 * @property int|null $target_reps
 * @property float|null $target_weight
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $target_formatted
 * @property-read Program $program
 * @property-read ActivityType $activityType
 */
#[Fillable(['program_id', 'activity_type_id', 'position', 'target_sets', 'target_reps', 'target_weight'])]
class ProgramItem extends Model
{
    /** @use HasFactory<ProgramItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'target_sets' => 'integer',
            'target_reps' => 'integer',
            'target_weight' => 'float',
        ];
    }

    /**
     * The targets summarised as "3 x 10 @ 40", dropping whatever is unset.
     *
     * @return Attribute<string, never>
     */
    protected function targetFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatTarget());
    }

    /**
     * Build the target summary, leaving out the parts that were not set.
     */
    private function formatTarget(): string
    {
        $target = array_filter([$this->target_sets, $this->target_reps]);

        $summary = $target === [] ? '' : implode(' × ', $target);

        if ($this->target_weight !== null) {
            $weight = number_format($this->target_weight, 1, '.', "'").' kg';

            return $summary === '' ? $weight : $summary.' @ '.$weight;
        }

        return $summary;
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<ActivityType, $this>
     */
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }
}
