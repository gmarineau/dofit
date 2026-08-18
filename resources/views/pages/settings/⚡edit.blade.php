<?php

use App\Models\Setting;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
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

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('Edit setting'));
    }
};
?>

<div>
    <x-page-header :title="__('Edit :setting', ['setting' => $setting->key])" :back="route('account')" />

    <form wire:submit="save">
        <x-field :label="__('Value')" for="value" :error="$errors->first('value')">
            <x-input id="value" :type="$setting->type" wire:model="value" :invalid="$errors->has('value')" autocomplete="off" autofocus />
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
