<?php

use App\Models\Exercise;
use App\Models\Program;
use App\Models\ProgramTarget;
use App\Models\Training;
use App\Models\User;
use App\Services\ProgramService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

it('adds an exercise, reusing one that already exists', function () {
    $program = Program::factory()->for($this->user)->create();
    $existing = Exercise::factory()->create(['name' => 'Bench Press']);

    Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->call('addItem', null, 'Bench Press')
        ->set('targetSets', 4)
        ->set('targetReps', 10)
        ->call('addTarget')
        ->assertHasNoErrors();

    $item = $program->items()->firstOrFail();

    expect($item->exercise_id)->toBe($existing->id)
        ->and($item->targets()->count())->toBe(1)
        ->and($item->target_formatted)->toBe('4 × 10');
});

it('holds several blocks of sets for the same exercise', function () {
    $program = Program::factory()->for($this->user)->create();

    $component = Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->call('addItem', null, 'Chest Press')
        ->set('targetSets', 2)
        ->set('targetReps', 10)
        ->set('targetWeight', 60)
        ->call('addTarget')
        ->assertHasNoErrors();

    $item = $program->items()->firstOrFail();

    $component->set('targetWeight', 70)
        ->call('addTarget')
        ->assertHasNoErrors();

    expect($item->targets()->count())->toBe(2)
        ->and($item->fresh()->target_formatted)->toBe('2 × 10 @ 60.0 kg · 2 × 10 @ 70.0 kg');
});

it('adds an exercise without any target', function () {
    $program = Program::factory()->for($this->user)->create();

    Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->call('addItem', null, 'Plank')
        ->assertHasNoErrors();

    $item = $program->items()->firstOrFail();

    expect($item->targets()->count())->toBe(0)
        ->and($item->target_formatted)->toBe('');
});

it('removes one block of sets, keeping the others', function () {
    $program = Program::factory()->for($this->user)->create();

    $component = Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->call('addItem', null, 'Chest Press')
        ->set('targetSets', 2)
        ->set('targetReps', 10)
        ->set('targetWeight', 60)
        ->call('addTarget');

    $item = $program->items()->firstOrFail();

    $component->set('targetWeight', 70)->call('addTarget');

    $component->call('removeTarget', $item->targets()->firstOrFail()->id);

    expect($item->targets()->count())->toBe(1)
        ->and($item->fresh()->target_formatted)->toBe('2 × 10 @ 70.0 kg');
});

it('refuses to remove a block of sets from someone else’s program', function () {
    $program = Program::factory()->for($this->user)->create();
    $someoneElses = ProgramTarget::factory()->create();

    $remove = fn () => Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->call('removeTarget', $someoneElses->id);

    expect($remove)->toThrow(ModelNotFoundException::class)
        ->and(ProgramTarget::find($someoneElses->id))->not->toBeNull();
});

it('deletes the blocks of sets along with the exercise', function () {
    $program = Program::factory()->for($this->user)->create();

    $component = Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->call('addItem', null, 'Chest Press')
        ->set('targetSets', 3)
        ->call('addTarget');

    $item = $program->items()->firstOrFail();

    $component->call('removeItem', $item->id);

    expect(ProgramTarget::where('program_item_id', $item->id)->count())->toBe(0);
});

it('appends each exercise after the previous one', function () {
    $program = Program::factory()->for($this->user)->create();

    $component = Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program]);

    foreach (['Squat', 'Deadlift', 'Row'] as $type) {
        $component->call('addItem', null, $type);
    }

    expect($program->items()->pluck('exercise_id')->count())->toBe(3)
        ->and($program->items->pluck('exercise.name')->all())->toBe(['Squat', 'Deadlift', 'Row']);
});

it('moves an exercise up and down', function () {
    $program = Program::factory()->for($this->user)->create();

    $component = Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program]);

    foreach (['Squat', 'Deadlift'] as $type) {
        $component->call('addItem', null, $type);
    }

    $second = $program->items()->get()->last();

    $component->call('move', $second->id, 'up');

    expect($program->items()->get()->pluck('exercise.name')->all())->toBe(['Deadlift', 'Squat']);

    $component->call('move', $second->id, 'down');

    expect($program->items()->get()->pluck('exercise.name')->all())->toBe(['Squat', 'Deadlift']);
});

it('ignores a move that would fall off either end', function () {
    $program = Program::factory()->for($this->user)->create();

    $component = Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->call('addItem', null, 'Squat');

    $only = $program->items()->firstOrFail();

    $component->call('move', $only->id, 'up')->call('move', $only->id, 'down');

    expect($program->items()->count())->toBe(1);
});

it('removes an exercise', function () {
    $program = Program::factory()->for($this->user)->create();

    Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->call('addItem', null, 'Squat');

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
            'exercise_id' => Exercise::factory()->create(['name' => $type])->id,
            'position' => $position,
        ]);
    }

    $training = app(ProgramService::class)->start($program->load('items'));

    expect($training->user_id)->toBe($this->user->id)
        ->and($training->name)->toBe('Upper body')
        ->and($training->date->isToday())->toBeTrue()
        ->and($training->completed_at)->toBeNull()
        ->and($training->activities()->with('exercise')->get()->pluck('exercise.name')->all())
        ->toBe(['Bench Press', 'Row', 'Curl']);
});

it('fills each activity with the sequences the program asks for', function () {
    $program = Program::factory()->for($this->user)->create();

    $item = $program->items()->create([
        'exercise_id' => Exercise::factory()->create()->id,
        'position' => 0,
    ]);

    $item->targets()->create(['position' => 0, 'sets' => 2, 'repetition' => 10, 'weight' => 60]);
    $item->targets()->create(['position' => 1, 'sets' => 2, 'repetition' => 8, 'weight' => 70]);

    $training = app(ProgramService::class)->start($program->load('items'));

    $activity = $training->activities()->firstOrFail();

    expect($activity->program_item_id)->toBe($item->id)
        ->and($activity->sequences()->orderBy('id')->get()->pluck('value')->all())
        ->toBe(['10 x 60.0', '10 x 60.0', '8 x 70.0', '8 x 70.0'])
        ->and($activity->isCompleted())->toBeFalse();
});

it('records a bodyweight exercise as sequences without a load', function () {
    $program = Program::factory()->for($this->user)->create();

    $item = $program->items()->create([
        'exercise_id' => Exercise::factory()->create()->id,
        'position' => 0,
    ]);

    $item->targets()->create(['position' => 0, 'sets' => 3, 'repetition' => 12]);

    $training = app(ProgramService::class)->start($program->load('items'));

    $sequences = $training->activities()->firstOrFail()->sequences()->get();

    expect($sequences)->toHaveCount(3)
        ->and($sequences->pluck('weight')->unique()->all())->toBe([null]);
});

it('leaves an exercise empty when the program never says how many repetitions', function () {
    $program = Program::factory()->for($this->user)->create();

    $item = $program->items()->create([
        'exercise_id' => Exercise::factory()->create()->id,
        'position' => 0,
    ]);

    $item->targets()->create(['position' => 0, 'sets' => 3, 'weight' => 60]);

    $training = app(ProgramService::class)->start($program->load('items'));

    expect($training->activities()->firstOrFail()->sequences()->count())->toBe(0);
});

it('starts a program from the list and redirects to the training', function () {
    $program = Program::factory()->for($this->user)->create();

    $program->items()->create([
        'exercise_id' => Exercise::factory()->create()->id,
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

    $component->call('addItem', null, 'Squat');

    expect(startButtonIsDisabled($component->html()))->toBeFalse();
});

it('opens the exercise search over the program and closes it', function () {
    $program = Program::factory()->for($this->user)->create();

    Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->assertSet('picking', false)
        ->call('pick')
        ->assertSet('picking', true)
        ->call('closeModal')
        ->assertSet('picking', false);
});

it('opens the sets form on the exercise it just added', function () {
    $program = Program::factory()->for($this->user)->create();

    $component = Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->call('addItem', null, 'Squat');

    // The sets are the point of a program, so the form is already waiting.
    $component->assertSet('targetItemId', $program->items()->firstOrFail()->id);
});
