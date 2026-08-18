<?php

use App\Models\Setting;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Edit setting')] class extends Component
{
    public Setting $setting;

    #[Validate('required|string|max:255')]
    public string $value = '';

    /**
     * Load the setting the route points at.
     */
    public function mount(Setting $setting): void
    {
        $this->authorize('update', $setting);

        $this->setting = $setting;
        $this->value = $setting->value;
    }

    /**
     * Save the new value.
     */
    public function save(): void
    {
        $this->setting->update($this->validate());

        $this->redirect(route('account'), navigate: true);
    }
};
?>

<div>
    <x-page-header :title="__('Edit :setting', ['setting' => $setting->key])" :back="route('account')" />

    <div class="mt-6">
        <x-form-card>
            <form wire:submit="save">
                <x-field :label="__('Value')" for="value" :error="$errors->first('value')">
                    <x-input id="value" :type="$setting->type" wire:model="value" :invalid="$errors->has('value')" autocomplete="off" autofocus />
                </x-field>

                <div class="flex items-center gap-3">
                    <x-button type="submit">
                        {{ __('Save') }}
                        <x-icons.spinner wire:loading wire:target="save" />
                    </x-button>

                    <a href="{{ route('account') }}" wire:navigate class="text-sm text-zinc-500 hover:underline dark:text-zinc-400">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </x-form-card>
    </div>
</div>
