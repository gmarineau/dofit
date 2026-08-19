---
paths:
  - '.github/workflows/**'
---

# Workflows

## GHCR: ne jamais publier l'image avec un PAT
Le build publie `ghcr.io/gmarineau/dofit` avec `secrets.GITHUB_TOKEN`. Garder ce token : c'est lui qui fait que GHCR crée le package déjà lié au repo (combiné au label `org.opencontainers.image.source` présent dans .docker/Dockerfile et généré par docker/metadata-action).

Piège : si le package est publié une fois avec un PAT (`GHCR_TOKEN`), GHCR le crée dans le namespace utilisateur **sans lien vers le repo**, et un package non lié refuse ensuite l'écriture au `GITHUB_TOKEN`. Le symptôme est un `denied: permission_denied: write_package` au step de push, alors que le login réussit. Revenir à `GITHUB_TOKEN` ne relie pas le package rétroactivement.

Correctif : supprimer le package puis relancer le build sur `main` (il est recréé lié). Sinon, Package settings -> Manage Actions access -> ajouter le repo en Write.

## Releases : release-please tague, build.yml publie
Le versioning est automatique via `release.yml` (release-please, `release-type: simple`, conventional commits). Il maintient une PR de release ; la merger bump `version.txt`, écrit `CHANGELOG.md`, crée le tag `vX.Y.Z` et la GitHub Release.

Un tag poussé par `GITHUB_TOKEN` ne déclenche aucun workflow. C'est pourquoi `build.yml` expose `on: workflow_call` avec les inputs `ref` et `version`, et que `release.yml` l'appelle en `needs` conditionné sur `release_created`. Ne pas remplacer ça par un trigger `on: push: tags`, il ne partirait jamais.

Les tags d'image sont pilotés par `inputs.version` : vide (push sur main) -> `:main` + `:sha-xxxx` ; renseigné (release) -> `:X.Y.Z`, `:X.Y`, `:latest`. `flavor: latest=false` est volontaire pour que `latest` suive la dernière release et pas la tête de main.

À FAIRE une fois la v0.1.0 sortie : retirer `"release-as": "0.1.0"` de `release-please-config.json`, sinon toutes les releases suivantes resteront bloquées en 0.1.0.
