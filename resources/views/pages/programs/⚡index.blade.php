<?php

use App\Models\Program;
use App\Services\ProgramService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public ?int $deletingId = null;

    /**
     * The user's programs, with how many exercises each holds.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Program>
     */
    #[Computed]
    public function programs()
    {
        return auth()->user()->programs()
            ->withCount('items')
            ->orderBy('name')
            ->get();
    }

    /**
     * Turn a program into a fresh training and open it.
     */
    public function start(int $id, ProgramService $programs): void
    {
        $program = Program::with('items')->findOrFail($id);

        $this->authorize('view', $program);

        // The button is disabled for an empty program, but the action has to
        // hold that line too.
        if ($program->items->isEmpty()) {
            return;
        }

        $training = $programs->start($program);

        $this->redirect(route('trainings.show', $training), navigate: true);
    }

    /**
     * Ask the user to confirm deleting a program.
     */
    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
    }

    /**
     * Dismiss the delete confirmation.
     */
    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    /**
     * Delete the program the user confirmed.
     */
    public function delete(): void
    {
        $program = Program::findOrFail($this->deletingId);

        $this->authorize('delete', $program);

        $program->delete();

        $this->deletingId = null;

        unset($this->programs);
    }

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('Programs'));
    }
};
?>

<div>
    <x-page-header :title="__('Programs')" :subtitle="__('Session templates you can start in one tap.')">
        <x-slot:actions>
            <x-button :href="route('programs.create')" as="a" wire:navigate class="max-sm:hidden">
                <x-heroicon-o-plus class="size-4" />
                {{ __('New program') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @forelse ($this->programs as $program)
        @if ($loop->first)
            <ul>
        @endif

        <li wire:key="program-{{ $program->id }}" class="group flex items-center gap-3 border-b border-line last:border-0">
            <a href="{{ route('programs.edit', $program) }}" wire:navigate class="min-w-0 flex-1 py-4">
                <div class="truncate font-bold text-ink">{{ $program->name }}</div>
                <div class="mt-0.5 text-sm font-semibold text-ink-soft">{{ $program->items_formatted }}</div>
            </a>

            <x-button type="button" size="sm" wire:click="start({{ $program->id }})" :disabled="$program->items_count === 0">
                <x-heroicon-o-play class="size-4" />
                {{ __('Start') }}
            </x-button>

            <x-button
                type="button"
                variant="quiet-danger"
                size="icon-sm"
                class="opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100 max-sm:opacity-100"
                wire:click="confirmDelete({{ $program->id }})"
                aria-label="{{ __('Delete program') }}"
            >
                <x-heroicon-o-x-mark class="size-4" />
            </x-button>
        </li>

        @if ($loop->last)
            </ul>
        @endif
    @empty
        <x-empty-state icon="o-rectangle-stack">
            {{ __('No program yet. Build one to start a session without typing everything again.') }}

            <x-slot:action>
                <x-button :href="route('programs.create')" as="a" wire:navigate>
                    <x-heroicon-o-plus class="size-4" />
                    {{ __('New program') }}
                </x-button>
            </x-slot:action>
        </x-empty-state>
    @endforelse

    <x-fab :href="route('programs.create')" wire:navigate :label="__('New program')" />

    <x-confirm-delete
        :show="$deletingId !== null"
        :title="__('Delete this program?')"
        :message="__('The trainings you already started from it are kept.')"
    />
</div>
