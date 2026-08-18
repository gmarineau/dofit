<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $birthdate
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $birthdate_formatted
 * @property-read Collection<int, Training> $trainings
 * @property-read Collection<int, ActivityType> $activityTypes
 * @property-read Collection<int, Metric> $metrics
 * @property-read Collection<int, Setting> $settings
 */
#[Fillable(['name', 'birthdate', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthdate' => 'datetime',
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
     * @return HasMany<ActivityType, $this>
     */
    public function activityTypes(): HasMany
    {
        return $this->hasMany(ActivityType::class);
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
