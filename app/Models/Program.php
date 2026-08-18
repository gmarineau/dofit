<?php

namespace App\Models;

use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A reusable session template: the exercises to perform, in order, with the
 * sets and reps to aim for.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $items_count
 * @property-read string $items_formatted
 * @property-read User $user
 * @property-read Collection<int, ProgramItem> $items
 */
#[Fillable(['name', 'user_id'])]
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    /**
     * A pluralized count of the exercises in the program.
     *
     * @return Attribute<string, never>
     */
    protected function itemsFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatItemsCount());
    }

    /**
     * Build the pluralized exercise count, preferring an eager-loaded count.
     */
    private function formatItemsCount(): string
    {
        $count = $this->items_count ?? $this->items->count();

        return trans_choice(':count exercise|:count exercises', $count, ['count' => $count]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ProgramItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProgramItem::class)->orderBy('position');
    }
}
