<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::guest')] class extends Component
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

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('Log in'));
    }
};
?>

<div>
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-2xl bg-accent text-white dark:text-canvas">
            <x-heroicon-o-bolt class="size-6" />
        </div>

        <h1 class="text-xl font-semibold tracking-tight text-ink">{{ __('Log in') }}</h1>
        <p class="mt-1 text-sm text-ink-soft">{{ __('Welcome back.') }}</p>
    </div>

    @if (session('status'))
        <p class="mb-5 rounded-xl bg-accent-soft px-3.5 py-2.5 text-sm text-accent">
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

        <label class="mb-6 flex items-center gap-2.5 text-sm text-ink-soft">
            <input type="checkbox" wire:model="remember" class="size-4 rounded border-line-strong text-accent focus:ring-accent">
            {{ __('Remember me') }}
        </label>

        <x-button type="submit" class="w-full">
            {{ __('Log in') }}
            <x-heroicon-o-arrow-path class="size-4 animate-spin" wire:loading wire:target="login" />
        </x-button>
    </form>

    <div class="mt-6 flex justify-between text-sm">
        <a href="{{ route('password.request') }}" wire:navigate class="text-ink-soft hover:text-ink">
            {{ __('Forgot password?') }}
        </a>

        <a href="{{ route('register') }}" wire:navigate class="font-medium text-accent hover:underline">
            {{ __('Create an account') }}
        </a>
    </div>
</div>
