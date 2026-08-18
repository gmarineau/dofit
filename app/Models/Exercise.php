<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * An entry in the shared exercise library, seeded from free-exercise-db.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $category
 * @property string|null $level
 * @property string|null $force
 * @property string|null $mechanic
 * @property string|null $equipment
 * @property list<string> $primary_muscles
 * @property list<string> $secondary_muscles
 * @property list<string> $instructions
 * @property list<string>|null $image_paths
 * @property-read Collection<int, ActivityType> $activityTypes
 */
#[Fillable([
    'slug', 'name', 'category', 'level', 'force', 'mechanic', 'equipment',
    'primary_muscles', 'secondary_muscles', 'instructions', 'image_paths',
])]
#[WithoutTimestamps]
class Exercise extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * The collection holding the exercise illustrations.
     */
    public const string ILLUSTRATIONS = 'illustrations';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'primary_muscles' => 'array',
            'secondary_muscles' => 'array',
            'instructions' => 'array',
            'image_paths' => 'array',
        ];
    }

    /**
     * Illustrations are fetched on demand by `dofit:fetch-exercise-images`.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::ILLUSTRATIONS)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useDisk('public');
    }

    /**
     * One small conversion is enough: the picker shows a thumbnail and the
     * detail view shows the original.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        // nonQueued() belongs on the conversion; fit() hands back the image
        // driver, so nothing may be chained after it.
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->fit(Fit::Crop, 160, 160);
    }

    /**
     * Whether the illustrations have been fetched for this exercise.
     */
    public function hasIllustrations(): bool
    {
        return $this->getMedia(self::ILLUSTRATIONS)->isNotEmpty();
    }

    /**
     * Narrow the library to exercises whose name contains the given text.
     *
     * @param  Builder<Exercise>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $query->when(
            filled($term),
            fn (Builder $query) => $query->whereLike('name', '%'.$term.'%'),
        );
    }

    /**
     * Narrow the library to exercises working the given muscle, primarily or
     * as a secondary.
     *
     * @param  Builder<Exercise>  $query
     */
    public function scopeForMuscle(Builder $query, ?string $muscle): void
    {
        $query->when($muscle, fn (Builder $query) => $query->where(
            fn (Builder $query) => $query
                ->whereJsonContains('primary_muscles', $muscle)
                ->orWhereJsonContains('secondary_muscles', $muscle),
        ));
    }

    /**
     * Narrow the library to exercises using the given equipment.
     *
     * @param  Builder<Exercise>  $query
     */
    public function scopeWithEquipment(Builder $query, ?string $equipment): void
    {
        $query->when($equipment, fn (Builder $query) => $query->where('equipment', $equipment));
    }

    /**
     * @return HasMany<ActivityType, $this>
     */
    public function activityTypes(): HasMany
    {
        return $this->hasMany(ActivityType::class);
    }
}
