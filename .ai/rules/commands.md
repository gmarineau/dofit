---
paths:
  - 'app/Console/Commands/**'
---

# Commands

## Deux bibliothèques d'exos, départagées par exercises.source
`dofit:import-exercises` (free-exercise-db, domaine public, anglais) et `dofit:import-exercises-dataset` (exercises-dataset, MIT, 10 langues) écrivent tous deux dans `exercises`. `exercises.source` dit qui possède la ligne (`free-exercise-db` / `exercises-dataset`, null pour un exo custom).

`image_paths` contient des chemins **relatifs à la base URL de son import**. Tout parcours de médias doit donc filtrer sur `source`, sinon on télécharge les chemins d'un dataset depuis l'URL de l'autre et on ramasse des 404.

Aucun import ne met `instructions` dans son `update:` d'upsert : ça écraserait les langues ajoutées par l'autre. Le nouvel import fusionne à part, et la précédence dépend de la possession — sur sa propre ligne le texte frais gagne, sur une ligne d'un autre import il ne comble que les langues manquantes. Ne pas « simplifier » ça en un upsert unique.

Les 1324 entrées d'exercises-dataset recouvrent 131 slugs de free-exercise-db ; ces 131 lignes ne sont qu'enrichies en français, jamais réécrites.

Les médias (images + GIFs) sont © Gym visual : `--with-media` est opt-in, jamais en CI ni au build Docker, et destiné à une bibliothèque locale non diffusée. Le Dockerfile ne les embarque pas.

`category` reste `null` sur les lignes exercises-dataset (elles n'ont pas de type d'effort) ; leur partie du corps va dans `body_part`, qui n'est pour l'instant branché sur aucun filtre de l'UI.
