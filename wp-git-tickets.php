<?php
/**
 * Plugin Name:       WP Git Tickets
 * Plugin URI:        https://github.com/Morglaf/wp-git-tickets
 * Description:       Create GitHub issues from Contact Form 7 and display repository feeds and READMEs with Gutenberg blocks.
 * Version:           2.5.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Morglaf
 * Author URI:        https://github.com/Morglaf
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-git-tickets
 * Domain Path:       /languages
 *
 * @package WPGIT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPGIT_VERSION', '2.5.0' );
define( 'WPGIT_PLUGIN_FILE', __FILE__ );
define( 'WPGIT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPGIT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WPGIT_PLUGIN_DIR . 'includes/class-wpgit-github.php';
require_once WPGIT_PLUGIN_DIR . 'includes/class-wpgit-markdown.php';
require_once WPGIT_PLUGIN_DIR . 'includes/class-wpgit-settings.php';
require_once WPGIT_PLUGIN_DIR . 'includes/class-wpgit-cf7.php';
require_once WPGIT_PLUGIN_DIR . 'includes/class-wpgit-blocks.php';

/**
 * Load translations.
 */
function wpgit_load_textdomain() {
	load_plugin_textdomain(
		'wp-git-tickets',
		false,
		dirname( plugin_basename( WPGIT_PLUGIN_FILE ) ) . '/languages'
	);
}
add_action( 'plugins_loaded', 'wpgit_load_textdomain' );

WPGIT_Settings::init();
WPGIT_CF7::init();
WPGIT_Blocks::init();

/**
 * Fetch organization repository names (shared helper).
 *
 * @return array|WP_Error
 */
function wpgit_get_repos_from_api() {
	return WPGIT_GitHub::get_org_repo_names();
}
