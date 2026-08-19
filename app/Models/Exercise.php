<?php

namespace App\Models;

use Database\Factories\ExerciseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * An exercise: either an entry from the shared library seeded from
 * free-exercise-db, or one the user added themselves. The two live in the same
 * table so everything downstream — activities, programs, favourites — only
 * ever deals with an exercise.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $slug
 * @property string $name
 * @property string|null $category
 * @property string|null $level
 * @property string|null $force
 * @property string|null $mechanic
 * @property string|null $equipment
 * @property list<string> $primary_muscles
 * @property list<string> $secondary_muscles
 * @property list<string> $instructions The steps in the reader's language; go through instructionSteps().
 * @property list<string>|null $image_paths
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, User> $favoritedBy
 * @property-read User|null $user
 */
#[Fillable([
    'user_id', 'slug', 'name', 'category', 'level', 'force', 'mechanic', 'equipment',
    'primary_muscles', 'secondary_muscles', 'instructions', 'image_paths',
])]
#[Translatable('instructions')]
#[WithoutTimestamps]
class Exercise extends Model implements HasMedia
{
    /** @use HasFactory<ExerciseFactory> */
    use HasFactory;

    use HasTranslations;
    use InteractsWithMedia;
    use Searchable;

    /**
     * The collection holding the exercise illustrations.
     */
    public const string ILLUSTRATIONS = 'illustrations';

    /**
     * The muscles worth filtering on, most covered first.
     *
     * @var list<string>
     */
    public const array MUSCLES = [
        'chest', 'shoulders', 'triceps', 'biceps', 'forearms', 'lats',
        'middle back', 'lower back', 'traps', 'abdominals', 'quadriceps',
        'hamstrings', 'glutes', 'calves',
    ];

    /**
     * The equipment worth filtering on.
     *
     * @var list<string>
     */
    public const array EQUIPMENTS = ['barbell', 'dumbbell', 'machine', 'cable', 'kettlebells', 'body only', 'bands'];

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
            'image_paths' => 'array',
        ];
    }

    /**
     * Illustrations are fetched on demand by the import commands. GIFs are
     * accepted because exercises-dataset ships animations alongside its
     * thumbnails.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::ILLUSTRATIONS)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
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
     * The instruction steps in the reader's language, falling back to the
     * application locale when the exercise has not been translated.
     *
     * Always go through this rather than reading `instructions` straight:
     * the trait hands back an empty string, not an empty list, for an
     * exercise carrying no instructions at all — a custom one, typically.
     *
     * @return list<string>
     */
    public function instructionSteps(?string $locale = null): array
    {
        $steps = $locale === null
            ? $this->instructions
            : $this->getTranslation('instructions', $locale);

        return is_array($steps) ? array_values($steps) : [];
    }

    /**
     * Narrow the library to exercises whose name contains the given text. This
     * is the fallback used when the search engine is unreachable; the engine
     * itself is reached through Scout's own `search()`.
     *
     * @param  Builder<Exercise>  $query
     */
    public function scopeMatchingName(Builder $query, ?string $term): void
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
     * What the search engine indexes: the name carries the search, the rest
     * lets a query like "pectoraux haltères" land on the right movements.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'category' => $this->category,
            'equipment' => $this->equipment,
            'primary_muscles' => $this->primary_muscles,
            'secondary_muscles' => $this->secondary_muscles,
        ];
    }

    /**
     * Whether the user added this exercise themselves.
     */
    public function isCustom(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Narrow the list to what this user may use: the shared library plus their
     * own exercises, never someone else's.
     *
     * @param  Builder<Exercise>  $query
     */
    public function scopeAvailableTo(Builder $query, User $user): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereNull('user_id')
            ->orWhere('user_id', $user->id));
    }

    /**
     * Narrow the list to the exercises the user added themselves.
     *
     * @param  Builder<Exercise>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    /**
     * Narrow the list to the shared library, imported from free-exercise-db.
     *
     * @param  Builder<Exercise>  $query
     */
    public function scopeImported(Builder $query): void
    {
        $query->whereNull('user_id');
    }

    /**
     * Narrow the list to the exercises somebody added themselves. Combined
     * with `availableTo()`, that is the signed-in user's own.
     *
     * @param  Builder<Exercise>  $query
     */
    public function scopeCustom(Builder $query): void
    {
        $query->whereNotNull('user_id');
    }

    /**
     * Narrow the list to the exercises the user has actually logged.
     *
     * @param  Builder<Exercise>  $query
     */
    public function scopeLoggedBy(Builder $query, User $user): void
    {
        $query->whereHas(
            'activities.training',
            fn (Builder $query) => $query->where('user_id', $user->id),
        );
    }

    /**
     * Narrow the library to the exercises the given user pinned.
     *
     * @param  Builder<Exercise>  $query
     */
    public function scopeFavoritedBy(Builder $query, User $user): void
    {
        $query->whereHas('favoritedBy', fn (Builder $query) => $query->whereKey($user->id));
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
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

    /**
     * @return HasMany<ProgramItem, $this>
     */
    public function programItems(): HasMany
    {
        return $this->hasMany(ProgramItem::class);
    }
}
