<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $birthdate
 * @property int|null $height
 * @property string $email
 * @property string|null $locale
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $birthdate_formatted
 * @property-read Collection<int, Training> $trainings
 * @property-read Collection<int, Exercise> $exercises
 * @property-read Collection<int, Exercise> $favoriteExercises
 * @property-read Collection<int, Metric> $metrics
 * @property-read Collection<int, Setting> $settings
 */
#[Fillable(['name', 'birthdate', 'height', 'email', 'password', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The bounds of the healthy body mass index band, per the World Health
     * Organization.
     */
    public const float BMI_HEALTHY_MIN = 18.5;

    public const float BMI_HEALTHY_MAX = 25.0;

    /**
     * The memoised body mass index; `false` until it has been worked out.
     */
    private float|false|null $bmi = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthdate' => 'datetime',
            'height' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * The birthdate in the application's date format, or an empty string when unset.
     *
     * @return Attribute<string, never>
     */
    protected function birthdateFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->birthdate?->format(config('dofit.date_format')) ?? '');
    }

    /**
     * The most recent weight measurement in kilograms, or null when the user
     * has never recorded one.
     */
    public function latestWeight(): ?float
    {
        $value = $this->metrics()
            ->where('key', 'weight')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->value('value');

        return $value === null ? null : (float) $value;
    }

    /**
     * The body mass index, from the height on file and the latest weight, to
     * one decimal. Null until both are known — the height is optional and a
     * user may not have weighed themselves yet.
     *
     * A method rather than an accessor: it runs a query, and a page reads it
     * twice, so the result is memoised for the life of the instance. `false`
     * is the "not worked out yet" marker, since null is a real answer.
     */
    public function bmi(): ?float
    {
        if ($this->bmi !== false) {
            return $this->bmi;
        }

        $weight = $this->latestWeight();

        if ($this->height === null || $this->height <= 0 || $weight === null) {
            return $this->bmi = null;
        }

        return $this->bmi = round($weight / (($this->height / 100) ** 2), 1);
    }

    /**
     * Whether the body mass index sits in the band the World Health
     * Organization calls healthy. Null when the index is unknown, so the view
     * can tell "outside the band" from "nothing to say".
     */
    public function hasHealthyBmi(): ?bool
    {
        $bmi = $this->bmi();

        if ($bmi === null) {
            return null;
        }

        return $bmi >= self::BMI_HEALTHY_MIN && $bmi < self::BMI_HEALTHY_MAX;
    }

    /**
     * @return HasMany<Program, $this>
     */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /**
     * @return HasMany<Training, $this>
     */
    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class);
    }

    /**
     * The exercises the user added themselves, on top of the shared library.
     *
     * @return HasMany<Exercise, $this>
     */
    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    /**
     * The library exercises the user pinned, so the ones they actually train
     * are one tap away.
     *
     * @return BelongsToMany<Exercise, $this>
     */
    public function favoriteExercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class)->withTimestamps();
    }

    /**
     * @return HasMany<Metric, $this>
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    /**
     * @return HasMany<Setting, $this>
     */
    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }
}
