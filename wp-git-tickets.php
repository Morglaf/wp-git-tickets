<?php
/*
Plugin Name: WP Git Tickets Advanced
Description: Tickets GitHub via CF7, blocs Gutenberg pour flux de dépôts et README.
Version: 2.4
Author: Morglaf
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPGIT_VERSION', '2.4' );
define( 'WPGIT_PLUGIN_FILE', __FILE__ );
define( 'WPGIT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPGIT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WPGIT_PLUGIN_DIR . 'includes/class-wpgit-github.php';
require_once WPGIT_PLUGIN_DIR . 'includes/class-wpgit-markdown.php';
require_once WPGIT_PLUGIN_DIR . 'includes/class-wpgit-settings.php';
require_once WPGIT_PLUGIN_DIR . 'includes/class-wpgit-cf7.php';
require_once WPGIT_PLUGIN_DIR . 'includes/class-wpgit-blocks.php';

WPGIT_Settings::init();
WPGIT_CF7::init();
WPGIT_Blocks::init();

/**
 * Récupère la liste des dépôts (compatibilité avec l'ancien code / réglages).
 *
 * @return array|WP_Error
 */
function wpgit_get_repos_from_api() {
	return WPGIT_GitHub::get_org_repo_names();
}
