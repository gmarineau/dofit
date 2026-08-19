<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Length first, composition never: forcing a symbol and a digit buys
        // less than one more word does, and pushes people towards P@ssw0rd!.
        // The breach check stays — it is the rule that actually rejects the
        // passwords people lose. It passes when the API cannot be reached, so
        // an offline instance is not locked out.
        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(10)->uncompromised()
            : null,
        );
    }
}
