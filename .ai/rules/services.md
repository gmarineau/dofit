---
paths:
  - app/Services/MediaPathGenerator.php
---

# Services

## Les médias sont rangés sous leur modèle, pas sous l'id du media
`config/media-library.php` est volontairement **partiel** : il ne porte que `path_generator` => `App\Services\MediaPathGenerator`, tout le reste (`disk_name`, `prefix`…) continue de venir de la config du paquet via `mergeConfigFrom`. Ne pas publier le fichier complet pour ajouter une clé.

Le chemin est `exercises/{exercise_id}/{media_id}/fichier.jpg`, conversions sous `.../{media_id}/conversions/`. Le segment `{media_id}` final est obligatoire (exigence spatie) : deux fichiers d'un même exo peuvent porter le même nom, et supprimer un media efface son dossier.

Le dossier de tête est dérivé du modèle (`class_basename` → kebab → pluriel), donc un nouveau modèle avec des médias se range tout seul. Les alias de morph map sont résolus avant, pour que l'ajout d'une map ne renomme pas les dossiers existants.

Attention en déploiement : changer ce générateur ne déplace pas les fichiers déjà stockés à l'ancien emplacement (`{media_id}/fichier.jpg`) — il faut les déplacer ou relancer l'import avec `--force`.
