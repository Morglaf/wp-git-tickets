# WP Git Tickets

WordPress plugin to connect a site to GitHub: create issues from Contact Form 7, and display repository activity feeds and READMEs with Gutenberg blocks.

**Author:** Morglaf  
**Version:** 2.5.0  
**License:** [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html)  
**Languages:** English (source), French, Spanish, German

## Features

### Contact Form 7
- Shortcode `[wpgit_repo_list]` (legacy alias `[github_repo_list]`)
- Creates a GitHub issue (bug / enhancement) on form submit

### Gutenberg blocks
| Block | Description |
|-------|-------------|
| **GitHub repository feed** | Commits or activity for one repo (manual URL or ACF/SCF field) |
| **GitHub organization feed** | Merged commits or org events |
| **GitHub repository README** | Markdown/HTML README with multilingual tabs |

## Requirements

- WordPress 5.8+
- PHP 7.4+
- GitHub [Personal Access Token](https://github.com/settings/tokens)
- Contact Form 7 (optional, for tickets)
- ACF or SCF (optional, for dynamic repo URLs)

## Installation

1. Upload `wp-git-tickets` to `wp-content/plugins/`
2. Activate the plugin
3. Open **Settings → WP Git Tickets**

Local zip build:

```powershell
.\build-zip.ps1
```

## Configuration

1. Personal Access Token
2. Organization / username
3. Repository visibility (checked repos appear in the CF7 select)
4. Clear GitHub cache when needed

### External services

The plugin calls GitHub (`api.github.com`, `raw.githubusercontent.com`). CF7 issue creation sends the visitor name, title and description to GitHub. See `readme.txt` for details.

## WordPress.org assets

PNG icons and banner for the plugin directory live in `.wordpress-org/` (not included in the installable zip). After approval, upload them to the wordpress.org SVN `assets/` folder (`icon-128x128.png`, `icon-256x256.png`, `banner-772x250.png`).

## Third-party

- [Parsedown](https://github.com/erusev/parsedown) (MIT) — see `vendor/LICENSE`

## Structure

```
wp-git-tickets.php
includes/
blocks/
assets/
languages/
vendor/Parsedown.php
license.txt
readme.txt
```
