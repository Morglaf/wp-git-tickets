=== WP Git Tickets ===
Contributors: morglaf
Tags: github, contact form 7, gutenberg, readme, commits
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create GitHub issues from Contact Form 7 and display repository feeds and READMEs with Gutenberg blocks.

== Description ==

WP Git Tickets connects WordPress to GitHub:

* Create GitHub issues from Contact Form 7 submissions (bug / enhancement)
* Gutenberg block: repository commit or activity feed
* Gutenberg block: organization-wide feed
* Gutenberg block: repository README (Markdown/HTML, multilingual tabs)

Available languages: English (source), French, Spanish, German.

= External services =

This plugin connects to GitHub:

* API: `https://api.github.com` — list repositories, commits, events, and create issues
* Raw content: `https://raw.githubusercontent.com` — fetch README files

A GitHub Personal Access Token configured in the settings is sent as an Authorization header.
When creating issues from Contact Form 7, the visitor name, ticket title and description are sent to GitHub.

GitHub terms: https://docs.github.com/en/site-policy/github-terms/github-terms-of-service
GitHub privacy: https://docs.github.com/en/site-policy/privacy-policies/github-privacy-statement

= Third-party libraries =

* Parsedown (MIT) — Markdown to HTML rendering

== Installation ==

1. Upload the `wp-git-tickets` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins screen
3. Go to Settings → WP Git Tickets and enter your GitHub token and organization
4. Optionally install Contact Form 7 for issue creation

== Frequently Asked Questions ==

= Does Contact Form 7 require a specific form structure? =

Yes. Use the shortcode `[wpgit_repo_list]` (legacy alias `[github_repo_list]` still works), plus fields such as `type-demande`, `ticket-title`, `ticket-desc`, and `your-name`.

= Can I load a repository URL from ACF/SCF? =

Yes. In the Gutenberg blocks, choose “ACF / SCF field” and select the field containing the GitHub repository URL.

== Changelog ==

= 2.5.0 =
* WordPress.org compliance (GPL, readme.txt, uninstall, sanitization)
* Multilingual: EN / FR / ES / DE
* CF7 security hardening and shortcode alias

= 2.4 =
* Custom first tab label and README width fixes

= 2.0 =
* Gutenberg blocks for feeds and README

== Upgrade Notice ==

= 2.5.0 =
Recommended update for security hardening and WordPress.org readiness.
