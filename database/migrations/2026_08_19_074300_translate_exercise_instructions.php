<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            // Which upstream library the entry came from, so a re-import can
            // tell its own rows apart. Null for an exercise a user added.
            $table->string('source')->nullable()->after('user_id');
            // The body part exercises-dataset groups by. Distinct from
            // `category`, which holds free-exercise-db's kind of effort.
            $table->string('body_part')->nullable()->after('category');
        });

        // `instructions` now holds one list of steps per locale. Everything
        // already stored came from free-exercise-db, so it is all English.
        $this->rewriteInstructions(fn (array $steps): array => ['en' => $steps]);

        DB::table('exercises')->whereNull('user_id')->update(['source' => 'free-exercise-db']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only one language survives the trip back; the reader's fallback is
        // the sensible one to keep.
        $fallback = (string) config('app.fallback_locale');

        $this->rewriteInstructions(
            fn (array $translations): array => $translations[$fallback] ?? (reset($translations) ?: []),
            expectList: false,
        );

        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn(['source', 'body_part']);
        });
    }

    /**
     * Walk every exercise and rewrite its instructions with the given callback.
     * Rows already in the target shape are left alone, and an exercise without
     * instructions keeps its empty value.
     *
     * @param  callable(array<array-key, mixed>): array<array-key, mixed>  $rewrite
     */
    protected function rewriteInstructions(callable $rewrite, bool $expectList = true): void
    {
        DB::table('exercises')
            ->select('id', 'instructions')
            ->orderBy('id')
            ->chunkById(200, function (Collection $rows) use ($rewrite, $expectList): void {
                foreach ($rows as $row) {
                    $steps = json_decode((string) $row->instructions, associative: true);

                    if (! is_array($steps) || $steps === [] || array_is_list($steps) !== $expectList) {
                        continue;
                    }

                    DB::table('exercises')
                        ->where('id', $row->id)
                        ->update(['instructions' => json_encode($rewrite($steps))]);
                }
            });
    }
};
