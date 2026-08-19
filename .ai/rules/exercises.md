---
paths:
  - 'resources/views/pages/exercises/**'
---

# Exercises

## Les filtres de la bibliothèque vivent en session
`term`, `muscle`, `equipment` et `source` de `pages::exercises.index` portent `#[Session]`, pas `#[Url]`. Raison : on revient d'une fiche d'exo par le chevron du `x-page-header`, qui est un lien vers `route('exercises.index')` — une navigation *avant*, pas un retour d'historique. Une query string ne serait donc pas rejouée, alors que la session l'est quel que soit le chemin de retour.
Conséquence : les filtres sont collants d'une visite à l'autre. Chaque chip se détoggle d'un second tap, c'est la seule sortie — ne pas ajouter de `mount()` qui les réinitialise, ça reviendrait à annuler la fonctionnalité.
`shown` n'est volontairement pas persisté : la liste repart à 24 entrées.
