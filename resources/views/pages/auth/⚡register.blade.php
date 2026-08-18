<?php

use App\Models\User;
use App\Services\UserSetupService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::guest')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Get the validation rules that apply to the registration form.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Register the user, give them their starting activity types and settings,
     * then log them in.
     */
    public function register(UserSetupService $userSetup): void
    {
        $user = User::create($this->validate());

        $userSetup->setUp($user);

        event(new Registered($user));

        Auth::login($user);

        session()->regenerate();

        $this->redirect(route('dashboard'), navigate: true);
    }

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('Register'));
    }
};
?>

<div>
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-2xl bg-accent text-white dark:text-canvas">
            <x-heroicon-o-bolt class="size-6" />
        </div>

        <h1 class="text-xl font-semibold tracking-tight text-ink">{{ __('Create an account') }}</h1>
        <p class="mt-1 text-sm text-ink-soft">{{ __('Start logging your trainings.') }}</p>
    </div>

    <form wire:submit="register">
        <x-field :label="__('Name')" for="name" :error="$errors->first('name')">
            <x-input id="name" type="text" wire:model="name" :invalid="$errors->has('name')" autocomplete="name" autofocus required />
        </x-field>

        <x-field :label="__('E-mail')" for="email" :error="$errors->first('email')">
            <x-input id="email" type="email" wire:model="email" :invalid="$errors->has('email')" autocomplete="username" required />
        </x-field>

        <x-field :label="__('Password')" for="password" :error="$errors->first('password')">
            <x-input id="password" type="password" wire:model="password" :invalid="$errors->has('password')" autocomplete="new-password" required />
        </x-field>

        <x-field :label="__('Confirm password')" for="password_confirmation" :error="$errors->first('password_confirmation')">
            <x-input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" required />
        </x-field>

        <x-button type="submit" class="w-full">
            {{ __('Register') }}
            <x-heroicon-o-arrow-path class="size-4 animate-spin" wire:loading wire:target="register" />
        </x-button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-soft">
        {{ __('Already registered?') }}
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-accent hover:underline">{{ __('Log in') }}</a>
    </p>
</div>
