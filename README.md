# WP Git Tickets Advanced

Plugin WordPress pour relier un site à GitHub : création de tickets via Contact Form 7, et blocs Gutenberg pour afficher le flux d’activité et les README des dépôts.

**Auteur :** Morglaf  
**Version :** 2.4

## Fonctionnalités

### Contact Form 7
- Shortcode `[github_repo_list]` : liste déroulante des dépôts (filtrable dans les réglages)
- À l’envoi du formulaire, création d’une issue GitHub (bug / enhancement) selon le type de demande

### Blocs Gutenberg
| Bloc | Description |
|------|-------------|
| **Flux dépôt GitHub** | Commits ou activité d’un dépôt (URL manuelle ou champ ACF/SCF) |
| **Flux organisation GitHub** | Commits fusionnés ou events de toute l’organisation |
| **README dépôt GitHub** | Affiche le(s) README en Markdown/HTML, onglets multilingues |

## Prérequis

- WordPress 5.8+
- Un [Personal Access Token](https://github.com/settings/tokens) GitHub
- Contact Form 7 (pour les tickets)
- ACF ou SCF (optionnel, pour lier l’URL d’un dépôt à un champ)

## Installation

1. Téléverser le dossier `wp-git-tickets` dans `wp-content/plugins/`
2. Activer le plugin
3. Aller dans **Réglages → WP Git Tickets**

Ou utiliser le zip généré localement :

```powershell
.\build-zip.ps1
```

Puis importer `dist/wp-git-tickets-x.x.zip` via **Extensions → Ajouter**.

## Configuration

Dans **Réglages → WP Git Tickets** :

1. **Personal Access Token** — token GitHub avec accès aux dépôts / issues
2. **Organisation / Pseudo** — ex. `MYorganisation`
3. **Visibilité des dépôts** — cocher ceux visibles dans le formulaire CF7
4. Bouton **Vider le cache GitHub** si besoin (README / flux)

## Usage Contact Form 7

Dans le formulaire, utiliser notamment :

- `[github_repo_list]` → champ `nom-logiciel`
- Un champ type de demande (`type-demande`) avec les valeurs :
  - `Un bug`
  - `Une fonction souhaitée`
- `ticket-title`, `ticket-desc`, `your-name`

Le plugin crée l’issue sur `org/nom-logiciel` avec le label `bug` ou `enhancement`.

## Usage des blocs

### Flux dépôt / README
- **URL manuelle** : `https://github.com/owner/repo`
- **ou champ ACF/SCF** : sélection dans la liste des champs détectés

### README — options utiles
- Branche (`main` par défaut, fallback `master`)
- Langue par défaut (`en` → `README.en.md`)
- Nom du premier onglet (à la place de « README »)
- Masquer l’en-tête (`div[align="center"]`)
- Sélecteurs CSS additionnels à masquer
- Largeur (%, px, etc.)

Les chemins d’images relatifs du README sont réécrits vers `raw.githubusercontent.com`.

## Structure

```
wp-git-tickets.php
includes/          # Settings, GitHub, CF7, Markdown, Blocs
blocks/            # block.json des 3 blocs
assets/            # JS / CSS éditeur & front
vendor/Parsedown.php
```

## Licence

Usage interne / projet associé — adapter selon vos besoins.
