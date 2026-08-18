<?php

use App\Models\User;
use App\Services\UserSetupService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::guest')] #[Title('Register')] class extends Component
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
};
?>

<div>
    <x-form-card>
        <h1 class="mb-1 text-lg font-semibold">{{ __('Create an account') }}</h1>
        <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Start logging your trainings.') }}</p>

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
                <x-icons.spinner wire:loading wire:target="register" />
            </x-button>
        </form>

        <p class="mt-5 text-center text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Already registered?') }}
            <a href="{{ route('login') }}" wire:navigate class="text-brand-700 hover:underline dark:text-brand-300">{{ __('Log in') }}</a>
        </p>
    </x-form-card>
</div>
