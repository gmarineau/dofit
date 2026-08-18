<?php

use App\Models\ActivityType;
use App\Models\Program;
use App\Models\Training;
use App\Models\User;
use App\Services\ProgramService;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lists only the signed-in user’s programs', function () {
    $mine = Program::factory()->for($this->user)->create(['name' => 'Upper body']);
    $someoneElses = Program::factory()->create(['name' => 'Hidden program']);

    Livewire::actingAs($this->user)
        ->test('pages::programs.index')
        ->assertSee($mine->name)
        ->assertDontSee($someoneElses->name);
});

it('creates a program and opens it', function () {
    Livewire::actingAs($this->user)
        ->test('pages::programs.create')
        ->set('name', 'Leg day')
        ->call('save')
        ->assertHasNoErrors();

    $program = Program::where('name', 'Leg day')->firstOrFail();

    expect($program->user_id)->toBe($this->user->id);
});

it('adds an exercise, reusing an existing activity type', function () {
    $program = Program::factory()->for($this->user)->create();
    $existing = ActivityType::factory()->for($this->user)->create(['type' => 'Bench Press']);

    Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->set('type', 'Bench Press')
        ->set('targetSets', 4)
        ->set('targetReps', 10)
        ->call('addItem')
        ->assertHasNoErrors();

    $item = $program->items()->firstOrFail();

    expect($item->activity_type_id)->toBe($existing->id)
        ->and($item->target_sets)->toBe(4)
        ->and($item->target_formatted)->toBe('4 × 10');
});

it('appends each exercise after the previous one', function () {
    $program = Program::factory()->for($this->user)->create();

    $component = Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program]);

    foreach (['Squat', 'Deadlift', 'Row'] as $type) {
        $component->set('type', $type)->call('addItem');
    }

    expect($program->items()->pluck('activity_type_id')->count())->toBe(3)
        ->and($program->items->pluck('activityType.type')->all())->toBe(['Squat', 'Deadlift', 'Row']);
});

it('moves an exercise up and down', function () {
    $program = Program::factory()->for($this->user)->create();

    $component = Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program]);

    foreach (['Squat', 'Deadlift'] as $type) {
        $component->set('type', $type)->call('addItem');
    }

    $second = $program->items()->get()->last();

    $component->call('move', $second->id, 'up');

    expect($program->items()->get()->pluck('activityType.type')->all())->toBe(['Deadlift', 'Squat']);

    $component->call('move', $second->id, 'down');

    expect($program->items()->get()->pluck('activityType.type')->all())->toBe(['Squat', 'Deadlift']);
});

it('ignores a move that would fall off either end', function () {
    $program = Program::factory()->for($this->user)->create();

    $component = Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->set('type', 'Squat')
        ->call('addItem');

    $only = $program->items()->firstOrFail();

    $component->call('move', $only->id, 'up')->call('move', $only->id, 'down');

    expect($program->items()->count())->toBe(1);
});

it('removes an exercise', function () {
    $program = Program::factory()->for($this->user)->create();

    Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->set('type', 'Squat')
        ->call('addItem');

    $item = $program->items()->firstOrFail();

    Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->call('removeItem', $item->id);

    expect($program->items()->count())->toBe(0);
});

it('starts a training holding one activity per exercise, in order', function () {
    $program = Program::factory()->for($this->user)->create(['name' => 'Upper body']);

    foreach (['Bench Press', 'Row', 'Curl'] as $position => $type) {
        $program->items()->create([
            'activity_type_id' => ActivityType::factory()->for($this->user)->create(['type' => $type])->id,
            'position' => $position,
        ]);
    }

    $training = app(ProgramService::class)->start($program->load('items'));

    expect($training->user_id)->toBe($this->user->id)
        ->and($training->name)->toBe('Upper body')
        ->and($training->date->isToday())->toBeTrue()
        ->and($training->activities()->with('activityType')->get()->pluck('activityType.type')->all())
        ->toBe(['Bench Press', 'Row', 'Curl']);
});

it('starts a program from the list and redirects to the training', function () {
    $program = Program::factory()->for($this->user)->create();

    $program->items()->create([
        'activity_type_id' => ActivityType::factory()->for($this->user)->create()->id,
        'position' => 0,
    ]);

    Livewire::actingAs($this->user)
        ->test('pages::programs.index')
        ->call('start', $program->id);

    expect(Training::count())->toBe(1);
});

it('refuses to start someone else’s program', function () {
    $someoneElses = Program::factory()->create();

    Livewire::actingAs($this->user)
        ->test('pages::programs.index')
        ->call('start', $someoneElses->id)
        ->assertForbidden();

    expect(Training::count())->toBe(0);
});

it('refuses to edit someone else’s program', function () {
    $this->actingAs($this->user)
        ->get(route('programs.edit', Program::factory()->create()))
        ->assertForbidden();
});

it('refuses to delete someone else’s program', function () {
    $someoneElses = Program::factory()->create();

    Livewire::actingAs($this->user)
        ->test('pages::programs.index')
        ->call('confirmDelete', $someoneElses->id)
        ->call('delete')
        ->assertForbidden();

    expect(Program::find($someoneElses->id))->not->toBeNull();
});

it('renames a program', function () {
    $program = Program::factory()->for($this->user)->create(['name' => 'Old name']);

    Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->set('name', 'New name')
        ->call('rename')
        ->assertHasNoErrors();

    expect($program->fresh()->name)->toBe('New name');
});

it('refuses to start a program that holds no exercise', function () {
    $program = Program::factory()->for($this->user)->create();

    Livewire::actingAs($this->user)
        ->test('pages::programs.index')
        ->call('start', $program->id)
        ->assertNoRedirect();

    Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->call('start')
        ->assertNoRedirect();

    expect(Training::count())->toBe(0);
});

/**
 * Whether the rendered markup disables the button that fires the given action.
 */
function startButtonIsDisabled(string $html): bool
{
    preg_match_all('/<button\b[^>]*>/', $html, $tags);

    foreach ($tags[0] as $tag) {
        if (str_contains($tag, 'wire:click="start"')) {
            // Match the attribute, not the disabled: Tailwind variants.
            return (bool) preg_match('/<button\s+disabled[\s>]/', $tag);
        }
    }

    return false;
}

it('disables the start button until the program holds an exercise', function () {
    $program = Program::factory()->for($this->user)->create();

    $component = Livewire::actingAs($this->user)->test('pages::programs.edit', ['program' => $program]);

    expect(startButtonIsDisabled($component->html()))->toBeTrue();

    $component->set('type', 'Squat')->call('addItem');

    expect(startButtonIsDisabled($component->html()))->toBeFalse();
});
