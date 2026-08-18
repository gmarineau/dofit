<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Account')] class extends Component
{
    /**
     * The signed-in user's settings, in a stable order.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Setting>
     */
    #[Computed]
    public function settings()
    {
        return auth()->user()->settings()->orderBy('key')->get();
    }
};
?>

<div>
    <x-page-header :title="__('Account')" />

    @php($user = auth()->user())

    <div class="mt-6 space-y-6">
        <x-card>
            <x-slot:header>
                <div class="flex items-center justify-between">
                    {{ __('Info') }}

                    <x-button :href="route('account.edit')" as="a" variant="secondary" size="sm" wire:navigate>
                        <x-icons.pencil class="size-3.5" />
                        {{ __('Edit') }}
                    </x-button>
                </div>
            </x-slot:header>

            <dl class="divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <div class="flex items-center justify-between gap-4 px-4 py-3">
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('E-mail') }}</dt>
                    <dd class="truncate font-medium">{{ $user->email }}</dd>
                </div>

                <div class="flex items-center justify-between gap-4 px-4 py-3">
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</dt>
                    <dd class="truncate font-medium">{{ $user->name }}</dd>
                </div>

                <div class="flex items-center justify-between gap-4 px-4 py-3">
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Birthdate') }}</dt>
                    <dd class="font-medium">{{ $user->birthdate_formatted ?: '—' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card :header="__('Settings')">
            <ul class="divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                @foreach ($this->settings as $setting)
                    <li wire:key="setting-{{ $setting->id }}" class="flex items-center justify-between gap-4 px-4 py-3">
                        <span class="text-zinc-500 dark:text-zinc-400">{{ $setting->key }}</span>

                        <span class="ml-auto font-medium">{{ $setting->value }}</span>

                        <x-button :href="route('settings.edit', $setting)" as="a" variant="ghost" size="icon" wire:navigate aria-label="{{ __('Edit setting') }}">
                            <x-icons.pencil class="size-4" />
                        </x-button>
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>
</div>
