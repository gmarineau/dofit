<?php

use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    public string $email = '';

    public ?string $birthdate = null;

    /**
     * Prefill the form with the signed-in user's details.
     */
    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->birthdate = $user->birthdate?->format('Y-m-d');
    }

    /**
     * Get the validation rules that apply to the account form.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore(auth()->id())],
            'birthdate' => ['nullable', 'date'],
        ];
    }

    /**
     * Save the account details.
     */
    public function save(): void
    {
        auth()->user()->update($this->validate());

        $this->redirect(route('account'), navigate: true);
    }

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('Edit account'));
    }
};
?>

<div>
    <x-page-header :title="__('Edit account')" :back="route('account')" />

    <form wire:submit="save">
        <x-field :label="__('Name')" for="name" :error="$errors->first('name')">
            <x-input id="name" type="text" wire:model="name" :invalid="$errors->has('name')" autocomplete="name" autofocus />
        </x-field>

        <x-field :label="__('E-mail')" for="email" :error="$errors->first('email')">
            <x-input id="email" type="email" wire:model="email" :invalid="$errors->has('email')" autocomplete="email" />
        </x-field>

        <x-field :label="__('Birthdate')" for="birthdate" :error="$errors->first('birthdate')">
            <x-input id="birthdate" type="date" wire:model="birthdate" :invalid="$errors->has('birthdate')" />
        </x-field>

        <div class="flex items-center gap-3 pt-2">
            <x-button type="submit" class="flex-1 sm:flex-none">
                {{ __('Save') }}
                <x-heroicon-o-arrow-path class="size-4 animate-spin" wire:loading wire:target="save" />
            </x-button>

            <x-button :href="route('account')" as="a" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </x-button>
        </div>
    </form>
</div>
