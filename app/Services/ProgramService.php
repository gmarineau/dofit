<?php

namespace App\Services;

use App\Enums\SequenceUnit;
use App\Models\Activity;
use App\Models\Program;
use App\Models\ProgramItem;
use App\Models\ProgramTarget;
use App\Models\Training;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProgramService
{
    /**
     * Turn a program into a training that is ready to be performed: the
     * session is dated today, holds one activity per exercise in the order the
     * program lists them, and every activity already carries the sequences the
     * program asks for. The user only has to tick the activities off, and
     * correct a sequence when the session did not go to plan.
     */
    public function start(Program $program): Training
    {
        $program->loadMissing('items.targets');

        return DB::transaction(function () use ($program): Training {
            $training = $program->user->trainings()->create([
                'name' => $program->name,
                'date' => now(),
            ]);

            $program->items->each(function (ProgramItem $item) use ($training): void {
                $activity = $training->activities()->create([
                    'exercise_id' => $item->exercise_id,
                    'program_item_id' => $item->id,
                ]);

                $this->fillSequences($activity, $item);
            });

            return $training;
        });
    }

    /**
     * Write one sequence per set the program asks for, so two sets at 60 kg
     * followed by two at 70 kg land as four sequences. A block that says how
     * many sets but not how many repetitions has nothing to record.
     */
    private function fillSequences(Activity $activity, ProgramItem $item): void
    {
        $item->targets
            ->filter(fn (ProgramTarget $target): bool => $target->repetition !== null)
            ->each(fn (ProgramTarget $target) => Collection::times(
                $target->sets,
                fn () => $activity->sequences()->create([
                    'repetition' => $target->repetition,
                    'weight' => $target->weight,
                    'unit' => SequenceUnit::Kg,
                ]),
            ));
    }
}
