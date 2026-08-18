<?php

namespace App\Models;

use Database\Factories\ProgramItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One exercise inside a program, with the blocks of sets to aim for.
 *
 * @property int $id
 * @property int $program_id
 * @property int $exercise_id
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $target_formatted
 * @property-read Program $program
 * @property-read Exercise $exercise
 * @property-read Collection<int, ProgramTarget> $targets
 */
#[Fillable(['program_id', 'exercise_id', 'position'])]
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
        ];
    }

    /**
     * Every block of sets read as one line, as in "2 × 10 @ 60.0 kg · 2 × 10 @ 70.0 kg".
     *
     * @return Attribute<string, never>
     */
    protected function targetFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatTarget());
    }

    /**
     * Join the blocks of sets, leaving out the ones that hold nothing.
     */
    private function formatTarget(): string
    {
        return $this->targets
            ->map(fn (ProgramTarget $target): string => $target->label)
            ->filter()
            ->implode(' · ');
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<Exercise, $this>
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * @return HasMany<ProgramTarget, $this>
     */
    public function targets(): HasMany
    {
        return $this->hasMany(ProgramTarget::class)->orderBy('position');
    }
}
