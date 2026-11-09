<?php
/**
 * Page de réglages WP Git Tickets.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPGIT_Settings {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'settings_init' ) );
		add_action( 'update_option_wpgit_settings', array( __CLASS__, 'flush_github_cache' ) );
		add_action( 'admin_post_wpgit_flush_cache', array( __CLASS__, 'handle_flush_cache' ) );
	}

	/**
	 * Vide les transients GitHub du plugin.
	 */
	public static function flush_github_cache() {
		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpgit_%' OR option_name LIKE '_transient_timeout_wpgit_%'"
		);
	}

	public static function handle_flush_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'wp-git-tickets' ) );
		}
		check_admin_referer( 'wpgit_flush_cache' );
		self::flush_github_cache();
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => 'wp_git_tickets',
					'wpgit_flushed'=> '1',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	public static function add_admin_menu() {
		add_options_page( 'WP Git Tickets', 'WP Git Tickets', 'manage_options', 'wp_git_tickets', array( __CLASS__, 'options_page' ) );
	}

	public static function settings_init() {
		register_setting( 'wpgit_plugin_page', 'wpgit_settings' );
		register_setting(
			'wpgit_plugin_page',
			'wpgit_blacklist',
			array(
				'type'              => 'array',
				'default'           => array(),
				'sanitize_callback' => function ( $value ) {
					if ( ! is_array( $value ) ) {
						return array();
					}
					return array_values( array_map( 'sanitize_text_field', $value ) );
				},
			)
		);

		add_settings_section( 'wpgit_main_section', 'Configuration GitHub', null, 'wpgit_plugin_page' );
		add_settings_field( 'github_token', 'Personal Access Token', array( __CLASS__, 'token_render' ), 'wpgit_plugin_page', 'wpgit_main_section' );
		add_settings_field( 'github_org', "Nom de l'Organisation / Pseudo", array( __CLASS__, 'org_render' ), 'wpgit_plugin_page', 'wpgit_main_section' );
		add_settings_field( 'debug_mode', 'Mode Debug (Console JS)', array( __CLASS__, 'debug_render' ), 'wpgit_plugin_page', 'wpgit_main_section' );
	}

	public static function token_render() {
		$options = get_option( 'wpgit_settings' );
		echo "<input type='password' name='wpgit_settings[github_token]' value='" . esc_attr( $options['github_token'] ?? '' ) . "' class='regular-text'>";
	}

	public static function org_render() {
		$options = get_option( 'wpgit_settings' );
		echo "<input type='text' name='wpgit_settings[github_org]' value='" . esc_attr( $options['github_org'] ?? '' ) . "' class='regular-text' placeholder='ex: Myorganisation'>";
	}

	public static function debug_render() {
		$options = get_option( 'wpgit_settings' );
		echo "<input type='checkbox' name='wpgit_settings[debug_mode]' value='1' " . checked( 1, $options['debug_mode'] ?? 0, false ) . '> Activer les logs console';
	}

	public static function options_page() {
		$repos     = wpgit_get_repos_from_api();
		$blacklist = get_option( 'wpgit_blacklist', array() );
		?>
		<div class="wrap">
			<h1>Réglages WP Git Tickets</h1>
			<?php if ( ! empty( $_GET['wpgit_flushed'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Cache GitHub vidé.</p></div>
			<?php endif; ?>
			<p>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wpgit_flush_cache' ), 'wpgit_flush_cache' ) ); ?>">
					Vider le cache GitHub (README / flux)
				</a>
			</p>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'wpgit_plugin_page' );
				do_settings_sections( 'wpgit_plugin_page' );
				?>

				<hr>
				<h2>Visibilité des dépôts</h2>
				<p>Décochez les dépôts que vous ne souhaitez pas voir apparaître dans le formulaire (ex: .github, site web, etc.) :</p>

				<?php if ( is_wp_error( $repos ) ) : ?>
					<div class="notice notice-error"><p>Impossible de lister les dépôts : <?php echo esc_html( $repos->get_error_message() ); ?></p></div>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr><th style="width: 30px;"></th><th>Nom du dépôt</th></tr>
						</thead>
						<tbody>
							<?php foreach ( $repos as $repo ) : ?>
								<tr>
									<td>
										<input type="checkbox" name="wpgit_blacklist[]" value="<?php echo esc_attr( $repo ); ?>"
										<?php checked( in_array( $repo, (array) $blacklist, true ) ); ?>>
									</td>
									<td><strong><?php echo esc_html( $repo ); ?></strong></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<p class="description">Note : En décochant un dépôt, il sera ignoré par le formulaire de contact.</p>
				<?php endif; ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
