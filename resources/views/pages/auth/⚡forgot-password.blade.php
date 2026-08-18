<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::guest')] #[Title('Forgot password')] class extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    /**
     * Send a password reset link to the given email address.
     */
    public function sendResetLink(): void
    {
        $this->validate();

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        session()->flash('status', __($status));

        $this->reset('email');
    }
};
?>

<div>
    <x-form-card>
        <h1 class="mb-1 text-lg font-semibold">{{ __('Forgot password') }}</h1>
        <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('We will email you a link to choose a new one.') }}
        </p>

        @if (session('status'))
            <p class="mb-4 rounded-lg bg-brand-50 px-3 py-2 text-sm text-brand-800 dark:bg-brand-950 dark:text-brand-200">
                {{ session('status') }}
            </p>
        @endif

        <form wire:submit="sendResetLink">
            <x-field :label="__('E-mail')" for="email" :error="$errors->first('email')">
                <x-input id="email" type="email" wire:model="email" :invalid="$errors->has('email')" autocomplete="username" autofocus required />
            </x-field>

            <x-button type="submit" class="w-full">
                {{ __('Email password reset link') }}
                <x-icons.spinner wire:loading wire:target="sendResetLink" />
            </x-button>
        </form>

        <p class="mt-5 text-center text-sm">
            <a href="{{ route('login') }}" wire:navigate class="text-brand-700 hover:underline dark:text-brand-300">
                {{ __('Back to log in') }}
            </a>
        </p>
    </x-form-card>
</div>
