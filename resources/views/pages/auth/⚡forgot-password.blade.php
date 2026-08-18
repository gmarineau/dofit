<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::guest')] class extends Component
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

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('Forgot password'));
    }
};
?>

<div>
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-2xl bg-accent text-white dark:text-canvas">
            <x-heroicon-o-bolt class="size-6" />
        </div>

        <h1 class="text-xl font-semibold tracking-tight text-ink">{{ __('Forgot password') }}</h1>
        <p class="mt-1 text-sm text-ink-soft">{{ __('We will email you a link to choose a new one.') }}</p>
    </div>

    @if (session('status'))
        <p class="mb-5 rounded-xl bg-accent-soft px-3.5 py-2.5 text-sm text-accent">
            {{ session('status') }}
        </p>
    @endif

    <form wire:submit="sendResetLink">
        <x-field :label="__('E-mail')" for="email" :error="$errors->first('email')">
            <x-input id="email" type="email" wire:model="email" :invalid="$errors->has('email')" autocomplete="username" autofocus required />
        </x-field>

        <x-button type="submit" class="w-full">
            {{ __('Email password reset link') }}
            <x-heroicon-o-arrow-path class="size-4 animate-spin" wire:loading wire:target="sendResetLink" />
        </x-button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" wire:navigate class="text-ink-soft hover:text-ink">
            {{ __('Back to log in') }}
        </a>
    </p>
</div>
