---
paths:
  - app/Models/Exercise.php
  - 'app/Models/**'
  - app/Models/User.php
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

## instructions est traduit, lire par instructionSteps()
`instructions` est une colonne JSON traduite par spatie/laravel-translatable, déclarée via `#[Translatable('instructions')]`. Forme stockée : `{"en": ["…"], "fr": ["…"]}`. Les langues offertes sont celles de `config('dofit.locales')`.

Ne pas remettre `'instructions' => 'array'` dans `casts()` : `initializeHasTranslations()` fait déjà `mergeCasts()` pour cet attribut.

Toujours lire via `instructionSteps(?string $locale = null): array`, jamais `$exercise->instructions` directement. Le trait renvoie une **chaîne vide**, pas un tableau, pour un exo sans aucune instruction (typiquement un exo custom, créé avec `instructions => []`) — un `@foreach` dessus casse la page. `instructionSteps()` garantit une `list<string>`.

Le fallback est implicite : `Translatable::$fallbackLocale` n'est pas initialisé, donc le package retombe sur `config('app.fallback_locale')` (`en`) via la sémantique `isset()` du `??`. Rien à configurer dans un service provider.

Assigner une **liste** (`['Étape 1']`) écrit la locale courante ; assigner une **map** (`['fr' => [...]]`) remplace l'ensemble des traductions. C'est pour ça que les anciennes fixtures de test ont survécu sans modification.

## Le disque des illustrations vient de la config, jamais en dur
La collection `illustrations` n'appelle pas `useDisk()` : elle suit `media-library.disk_name`, qui vaut déjà `env('MEDIA_DISK', 'public')` dans la config du paquet. Ne pas remettre `useDisk('public')` en dur, et ne pas introduire de clé maison en doublon — `MEDIA_DISK=s3` suffit à tout basculer.
Côté tests, faker le disque configuré : `Storage::fake(config('media-library.disk_name'))`, pas `Storage::fake('public')`.
Les vues passent par `getUrl()` / `getFirstMediaUrl()`, donc rien à changer pour S3 tant que le disque a bien une `url` configurée.
Pour partir sur S3 : `MEDIA_DISK=media`, pas `MEDIA_DISK=s3`. Le disque `media` est le même bucket sous le préfixe `AWS_MEDIA_PREFIX` mais en `'visibility' => 'public'` ; `s3` reste privé car c'est là qu'atterrirait tout le reste. Ne pas rendre `s3` public pour dépanner, ajouter le besoin au disque `media`.
Attention aux ACL : si le bucket est en Object Ownership « bucket owner enforced » (défaut des buckets récents, R2, Scaleway…), la visibilité publique fait échouer chaque upload en `AccessControlListNotSupported` — retirer alors `visibility` du disque `media` et ouvrir la lecture du préfixe par bucket policy.

## La taille vit sur le user, l'IMC est dérivé
`users.height` est en **centimètres** (unsignedSmallInteger nullable), saisie sur `account.edit`, bornée 50–280 pour attraper les fautes de frappe.
L'IMC n'est jamais stocké : `$user->bmi` (Attribute) le recalcule depuis `height` et `latestWeight()`, soit la dernière métrique `key = 'weight'`. Il vaut `null` tant que l'un des deux manque — toujours prévoir le `—` en vue.
Une pesée reste une `Metric`, pas une colonne du user : c'est l'historique qui alimente les courbes du dashboard.
