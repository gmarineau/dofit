<?php

namespace App\Models;

use App\Enums\SequenceUnit;
use Database\Factories\SequenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $activity_id
 * @property int $repetition
 * @property float|null $weight
 * @property SequenceUnit $unit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $value
 * @property-read string $weight_formatted
 * @property-read Activity $activity
 */
#[Fillable(['repetition', 'weight', 'activity_id', 'unit'])]
class Sequence extends Model
{
    /** @use HasFactory<SequenceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'repetition' => 'integer',
            'weight' => 'float',
            'unit' => SequenceUnit::class,
        ];
    }

    /**
     * The sequence summarized as "10 x 42.5", omitting the weight when unset.
     *
     * @return Attribute<string, never>
     */
    protected function value(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatValue());
    }

    /**
     * Build the "repetition x weight" summary, dropping the weight when unset.
     */
    private function formatValue(): string
    {
        $parts = [(string) $this->repetition];

        if ($this->weight !== null) {
            $parts[] = $this->formatWeight();
        }

        return implode(' x ', $parts);
    }

    /**
     * The weight rounded to one decimal, empty when no weight was recorded.
     *
     * @return Attribute<string, never>
     */
    protected function weightFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatWeight());
    }

    /**
     * Round the weight to one decimal, or an empty string when no weight was
     * recorded.
     */
    private function formatWeight(): string
    {
        return $this->weight === null
            ? ''
            : number_format($this->weight, 1, '.', "'");
    }

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
