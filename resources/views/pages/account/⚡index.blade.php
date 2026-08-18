<?php

use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
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

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('Account'));
    }
};
?>

<div>
    @php($user = auth()->user())

    <x-page-header :title="__('Account')" />

    <div class="mb-8 flex items-center gap-4">
        <div class="numeric flex size-14 shrink-0 items-center justify-center rounded-full bg-accent-soft text-lg font-extrabold text-accent">
            {{ $user->initials() }}
        </div>

        <div class="min-w-0 flex-1">
            <div class="truncate font-bold text-ink">{{ $user->name }}</div>
            <div class="truncate text-sm font-semibold text-ink-soft">{{ $user->email }}</div>
        </div>

        <x-button :href="route('account.edit')" as="a" variant="secondary" size="sm" wire:navigate>
            {{ __('Edit') }}
        </x-button>
    </div>

    <section class="mb-8">
        <x-section-heading>{{ __('Info') }}</x-section-heading>

        <div class="flex items-center justify-between gap-4 border-b border-line py-4">
            <span class="text-sm font-semibold text-ink-soft">{{ __('Birthdate') }}</span>
            <span class="numeric text-sm font-bold text-ink">{{ $user->birthdate_formatted ?: '—' }}</span>
        </div>
    </section>

    <section class="mb-8">
        <x-section-heading>{{ __('Settings') }}</x-section-heading>

        <ul>
            @foreach ($this->settings as $setting)
                <li wire:key="setting-{{ $setting->id }}" class="border-b border-line last:border-0">
                    <a href="{{ route('settings.edit', $setting) }}" wire:navigate class="flex items-center justify-between gap-4 py-4">
                        <span class="text-sm font-semibold text-ink-soft">{{ $setting->key }}</span>

                        <span class="flex items-center gap-2">
                            <span class="numeric text-sm font-bold text-ink">{{ $setting->value }}</span>
                            <x-heroicon-o-chevron-right class="size-4 text-ink-muted" />
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    <div class="flex items-center justify-between gap-4">
        <div class="sm:hidden">
            <x-theme-toggle class="-ml-3" />
        </div>

        <form method="POST" action="{{ route('logout') }}" class="ml-auto">
            @csrf

            <x-button type="submit" variant="ghost">
                <x-heroicon-o-arrow-right-start-on-rectangle class="size-4" />
                {{ __('Log out') }}
            </x-button>
        </form>
    </div>
</div>
