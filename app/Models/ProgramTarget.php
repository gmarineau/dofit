<?php

namespace App\Models;

use App\Enums\SequenceUnit;
use Database\Factories\ProgramTargetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One block of identical sets inside a program item, so an exercise can ask
 * for two sets at 60 kg followed by two more at 70 kg.
 *
 * @property int $id
 * @property int $program_item_id
 * @property int $position
 * @property int $sets
 * @property int|null $repetition
 * @property float|null $weight
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $label
 * @property-read ProgramItem $programItem
 */
#[Fillable(['program_item_id', 'position', 'sets', 'repetition', 'weight'])]
class ProgramTarget extends Model
{
    /** @use HasFactory<ProgramTargetFactory> */
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
            'sets' => 'integer',
            'repetition' => 'integer',
            'weight' => 'float',
        ];
    }

    /**
     * The block summarised as "2 × 10 @ 60.0 kg", dropping whatever is unset.
     *
     * @return Attribute<string, never>
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatLabel());
    }

    /**
     * Build the block summary, leaving out the parts that were not set.
     */
    private function formatLabel(): string
    {
        $summary = implode(' × ', array_filter([$this->sets, $this->repetition]));

        if ($this->weight === null) {
            return $summary;
        }

        $weight = number_format($this->weight, 1, '.', "'").' '.SequenceUnit::Kg->label();

        return $summary === '' ? $weight : $summary.' @ '.$weight;
    }

    /**
     * @return BelongsTo<ProgramItem, $this>
     */
    public function programItem(): BelongsTo
    {
        return $this->belongsTo(ProgramItem::class);
    }
}
