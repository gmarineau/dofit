---
paths:
  - '.github/workflows/**'
---

# Workflows

## GHCR: ne jamais publier l'image avec un PAT
Le build publie `ghcr.io/gmarineau/dofit` avec `secrets.GITHUB_TOKEN`. Garder ce token : c'est lui qui fait que GHCR crée le package déjà lié au repo (combiné au label `org.opencontainers.image.source` présent dans .docker/Dockerfile et généré par docker/metadata-action).

Piège : si le package est publié une fois avec un PAT (`GHCR_TOKEN`), GHCR le crée dans le namespace utilisateur **sans lien vers le repo**, et un package non lié refuse ensuite l'écriture au `GITHUB_TOKEN`. Le symptôme est un `denied: permission_denied: write_package` au step de push, alors que le login réussit. Revenir à `GITHUB_TOKEN` ne relie pas le package rétroactivement.

Correctif : supprimer le package puis relancer le build sur `main` (il est recréé lié). Sinon, Package settings -> Manage Actions access -> ajouter le repo en Write.

## Releases : tags posés à la main, build.yml publie
Il n'y a pas d'automatisation de version : release-please a été retiré (avec `release.yml`, `release-please-config.json`, `.release-please-manifest.json` et `version.txt`) parce que GitHub refusait à Actions la création de la PR de release. Ne pas le réintroduire sans avoir d'abord coché « Allow GitHub Actions to create and approve pull requests » dans Settings -> Actions -> General.

Publier une version, c'est donc : `git tag vX.Y.Z && git push origin vX.Y.Z`. `build.yml` écoute `on: push: tags: ['v*']`. C'est un tag poussé par un humain, donc il déclenche bien le workflow — la restriction ne vaut que pour un tag poussé par Actions avec `GITHUB_TOKEN`, ce qui était toute la raison d'être de l'ancien `workflow_call`.

Les tags d'image découlent du ref : push sur `main` -> `:main` + `:sha-xxxx` ; tag `v1.2.3` -> `:1.2.3`, `:1.2` et `:latest`. `flavor: latest=false` est volontaire pour que `latest` suive la dernière release et pas la tête de main.
