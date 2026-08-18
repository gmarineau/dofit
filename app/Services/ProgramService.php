<?php

namespace App\Services;

use App\Models\Program;
use App\Models\ProgramItem;
use App\Models\Training;
use Illuminate\Support\Facades\DB;

class ProgramService
{
    /**
     * Turn a program into a training that is ready to be filled in: the
     * session is dated today and already holds one activity per exercise, in
     * the order the program lists them.
     */
    public function start(Program $program): Training
    {
        return DB::transaction(function () use ($program): Training {
            $training = $program->user->trainings()->create([
                'name' => $program->name,
                'date' => now(),
            ]);

            $program->items->each(fn (ProgramItem $item) => $training->activities()->create([
                'activity_type_id' => $item->activity_type_id,
            ]));

            return $training;
        });
    }
}
