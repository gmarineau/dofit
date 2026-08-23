---
paths:
  - 'resources/views/pages/exercises/**'
---

# Exercises

## Les filtres de la bibliothèque vivent en session
`term`, `muscle`, `equipment` et `source` de `pages::exercises.index` portent `#[Session]`, pas `#[Url]`. Raison : on revient d'une fiche d'exo par le chevron du `x-page-header`, qui est un lien vers `route('exercises.index')` — une navigation *avant*, pas un retour d'historique. Une query string ne serait donc pas rejouée, alors que la session l'est quel que soit le chemin de retour.
Conséquence : les filtres sont collants d'une visite à l'autre. Chaque chip se détoggle d'un second tap, c'est la seule sortie — ne pas ajouter de `mount()` qui les réinitialise, ça reviendrait à annuler la fonctionnalité.
`shown` n'est volontairement pas persisté : la liste repart à 24 entrées.

## Précédent/suivant suit la liste filtrée, via ExerciseService
La fiche d'exo porte en bas un pager précédent/suivant qui marche dans **la liste telle que l'index est filtré**, pas dans la bibliothèque brute.

Ça tient à un couplage explicite : les `#[Session]` de l'index portent une clé nommée (`ExerciseService::FILTER_KEY.'term'`, `…'muscle'`, `…'equipment'`, `…'source'`) que `ExerciseService::sessionFilters()` relit. Ne pas revenir aux clés auto de Livewire (`lw` + crc32) — la fiche ne saurait plus où lire.

`ExerciseService::library()` est la seule construction de la requête, **tri inclus** (`orderBy('name')` après le rang du moteur). L'index et `neighbours()` passent tous les deux par là : ajouter un filtre ou changer le tri à un seul endroit ferait diverger la liste et les flèches. Ne pas remettre un `orderBy` dans le composant index.

`neighbours()` charge la colonne d'ids entière plutôt qu'un `where name >` : avec un terme de recherche l'ordre est le rang Meilisearch, non reproductible en SQL comparatif. Si l'exo n'est pas dans la liste filtrée (lien direct, filtre dont il est sorti), on retombe sur la bibliothèque complète au lieu de masquer les liens.
