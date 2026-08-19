<?php

use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    public string $email = '';

    public ?string $birthdate = null;

    public ?int $height = null;

    public string $locale = '';

    /**
     * Prefill the form with the signed-in user's details.
     */
    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->birthdate = $user->birthdate?->format('Y-m-d');
        $this->height = $user->height;
        $this->locale = $user->locale ?? config('app.locale');
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
            // Centimetres, wide enough for anyone without accepting a typo.
            'height' => ['nullable', 'integer', 'min:50', 'max:280'],
            'locale' => ['required', 'string', Rule::in(array_keys(config('dofit.locales')))],
        ];
    }

    /**
     * Save the account details.
     */
    public function save(): void
    {
        auth()->user()->update($this->validate());

        // The redirect renders with the middleware's locale, so the new
        // language is in place on the very next page.
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

        <div class="mb-5">
            <span class="mb-2 block text-sm font-bold text-ink-soft">{{ __('Language') }}</span>

            <div class="flex flex-wrap gap-1.5">
                @foreach (config('dofit.locales') as $code => $label)
                    <button
                        type="button"
                        wire:key="locale-{{ $code }}"
                        wire:click="$set('locale', @js($code))"
                        @class([
                            'rounded-full px-3 py-1.5 text-xs font-bold transition',
                            'bg-accent text-accent-ink' => $locale === $code,
                            'bg-raised text-ink-soft hover:text-ink' => $locale !== $code,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <x-error :messages="$errors->first('locale')" />
        </div>

        <x-field :label="__('Birthdate')" for="birthdate" :error="$errors->first('birthdate')">
            <x-input id="birthdate" type="date" wire:model="birthdate" :invalid="$errors->has('birthdate')" />
        </x-field>

        <x-field :label="__('Height (cm)')" for="height" :error="$errors->first('height')">
            <x-input id="height" type="number" inputmode="numeric" wire:model="height" :invalid="$errors->has('height')" autocomplete="off" />
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
