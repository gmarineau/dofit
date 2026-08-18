---
paths:
  - app/Models/Exercise.php
  - 'app/Models/**'
---

# Models

## Exercise search runs through Scout and Meilisearch
The library is English (free-exercise-db) while the UI is French. Rather than translating 873 names, the Meilisearch index carries French synonyms plus loosened typo tolerance, set by `php artisan dofit:sync-exercise-search` — run it after every `dofit:import-exercises`, which upserts in bulk and so never fires Scout's model events.
Scout's trait owns `Exercise::search()`; the Eloquent fallback scope is therefore named `matchingName()`. Never add a `scopeSearch` back.
Go through `ExerciseService::applyTerm()`, never `Exercise::search()` directly: it falls back to `matchingName()` when the engine is unreachable, and it re-applies the engine's ranking as an SQL `case` so a later `orderBy('name')` only breaks ties.
Tests pin `SCOUT_DRIVER=collection` in phpunit.xml so they never reach a real engine; that engine ranks newest-first, which order-sensitive tests must account for.

## One Exercise model, no activity types
`activity_types` is gone: an exercise is either a shared library entry (`user_id` null) or one a user added (`user_id` set), in the single `exercises` table. Activities and program items carry `exercise_id`.
Always scope reads with `availableTo($user)` — a library entry is everyone's, a custom one is its owner's alone. Anything per-user computed from a shared exercise (progression charts, "already logged") must filter through `activities.training.user_id`, since the exercise row itself is shared.
Go through `ExerciseService::resolve()` to turn a picked id or typed name into an exercise: it reuses an existing name before creating a custom duplicate, and `createCustom()` assigns a slug that does not collide with the library's.
