<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::guest')] #[Title('Log in')] class extends Component
{
    /**
     * How many failed attempts are allowed before the login is throttled.
     */
    protected const int MAX_ATTEMPTS = 5;

    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Authenticate the user and send them to the dashboard.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        session()->regenerate();

        $this->redirectIntended(route('dashboard'), navigate: true);
    }

    /**
     * Stop the login attempt when too many have already failed.
     *
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * The rate limiter key for this email and IP address pair.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
};
?>

<div>
    <x-form-card>
        <h1 class="mb-1 text-lg font-semibold">{{ __('Log in') }}</h1>
        <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Welcome back.') }}</p>

        @if (session('status'))
            <p class="mb-4 rounded-lg bg-brand-50 px-3 py-2 text-sm text-brand-800 dark:bg-brand-950 dark:text-brand-200">
                {{ session('status') }}
            </p>
        @endif

        <form wire:submit="login">
            <x-field :label="__('E-mail')" for="email" :error="$errors->first('email')">
                <x-input id="email" type="email" wire:model="email" :invalid="$errors->has('email')" autocomplete="username" autofocus required />
            </x-field>

            <x-field :label="__('Password')" for="password" :error="$errors->first('password')">
                <x-input id="password" type="password" wire:model="password" :invalid="$errors->has('password')" autocomplete="current-password" required />
            </x-field>

            <label class="mb-5 flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                <input type="checkbox" wire:model="remember" class="size-4 rounded border-zinc-300 text-brand-700 focus:ring-brand-600">
                {{ __('Remember me') }}
            </label>

            <x-button type="submit" class="w-full">
                {{ __('Log in') }}
                <x-icons.spinner wire:loading wire:target="login" />
            </x-button>
        </form>

        <div class="mt-5 flex justify-between text-sm">
            <a href="{{ route('password.request') }}" wire:navigate class="text-brand-700 hover:underline dark:text-brand-300">
                {{ __('Forgot password?') }}
            </a>

            <a href="{{ route('register') }}" wire:navigate class="text-brand-700 hover:underline dark:text-brand-300">
                {{ __('Create an account') }}
            </a>
        </div>
    </x-form-card>
</div>
