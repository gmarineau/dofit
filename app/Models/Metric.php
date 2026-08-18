<?php

namespace App\Models;

use Database\Factories\MetricFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $key
 * @property string $value
 * @property Carbon $date
 * @property-read string $value_formatted
 * @property-read User $user
 */
#[Fillable(['key', 'value', 'date', 'user_id'])]
#[WithoutTimestamps]
class Metric extends Model
{
    /** @use HasFactory<MetricFactory> */
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
     * The recorded value rounded to one decimal.
     *
     * @return Attribute<string, never>
     */
    protected function valueFormatted(): Attribute
    {
        return Attribute::get(fn (): string => number_format((float) $this->value, 1, '.', "'"));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
