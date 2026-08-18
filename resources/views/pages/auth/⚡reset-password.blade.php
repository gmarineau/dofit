<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::guest')] #[Title('Reset password')] class extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Prefill the form from the signed reset link.
     */
    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->string('email');
    }

    /**
     * Get the validation rules that apply to the reset form.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ];
    }

    /**
     * Store the new password and send the user back to the login page.
     */
    public function resetPassword(): void
    {
        $this->validate();

        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        session()->flash('status', __($status));

        $this->redirect(route('login'), navigate: true);
    }
};
?>

<div>
    <x-form-card>
        <h1 class="mb-1 text-lg font-semibold">{{ __('Reset password') }}</h1>
        <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Choose a new password.') }}</p>

        <form wire:submit="resetPassword">
            <x-field :label="__('E-mail')" for="email" :error="$errors->first('email')">
                <x-input id="email" type="email" wire:model="email" :invalid="$errors->has('email')" autocomplete="username" required />
            </x-field>

            <x-field :label="__('Password')" for="password" :error="$errors->first('password')">
                <x-input id="password" type="password" wire:model="password" :invalid="$errors->has('password')" autocomplete="new-password" autofocus required />
            </x-field>

            <x-field :label="__('Confirm password')" for="password_confirmation" :error="$errors->first('password_confirmation')">
                <x-input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" required />
            </x-field>

            <x-button type="submit" class="w-full">
                {{ __('Reset password') }}
                <x-icons.spinner wire:loading wire:target="resetPassword" />
            </x-button>
        </form>
    </x-form-card>
</div>
