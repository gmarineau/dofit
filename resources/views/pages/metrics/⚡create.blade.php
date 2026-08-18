<?php

use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('New metric')] class extends Component
{
    #[Validate('required|numeric|min:0')]
    public ?float $value = null;

    /**
     * Record today's weight measurement.
     */
    public function save(): void
    {
        $this->validate();

        auth()->user()->metrics()->create([
            'key' => 'weight',
            'value' => (string) $this->value,
            'date' => now(),
        ]);

        $this->redirect(route('metrics.index'), navigate: true);
    }
};
?>

<div>
    <x-page-header :title="__('New metric')" :back="route('metrics.index')" />

    <div class="mt-6">
        <x-form-card>
            <form wire:submit="save">
                <x-field :label="__('Weight')" for="value" :error="$errors->first('value')">
                    <x-input id="value" type="number" step="0.1" inputmode="decimal" wire:model="value" :invalid="$errors->has('value')" autocomplete="off" autofocus />
                </x-field>

                <div class="flex items-center gap-3">
                    <x-button type="submit">
                        {{ __('Save') }}
                        <x-icons.spinner wire:loading wire:target="save" />
                    </x-button>

                    <a href="{{ route('metrics.index') }}" wire:navigate class="text-sm text-zinc-500 hover:underline dark:text-zinc-400">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </x-form-card>
    </div>
</div>
