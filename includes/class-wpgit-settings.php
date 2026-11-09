<?php
/**
 * Settings page.
 *
 * @package WPGIT
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
	 * Delete known plugin transients without raw SQL.
	 */
	public static function flush_github_cache() {
		$known = array(
			'wpgit_all_repos_cache',
		);

		foreach ( $known as $key ) {
			delete_transient( $key );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_wpgit_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_wpgit_' ) . '%'
			)
		);

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $option_name ) {
			if ( 0 === strpos( $option_name, '_transient_timeout_' ) ) {
				$key = substr( $option_name, strlen( '_transient_timeout_' ) );
				delete_transient( $key );
			} elseif ( 0 === strpos( $option_name, '_transient_' ) ) {
				$key = substr( $option_name, strlen( '_transient_' ) );
				delete_transient( $key );
			}
		}
	}

	public static function handle_flush_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'wp-git-tickets' ) );
		}
		check_admin_referer( 'wpgit_flush_cache' );
		self::flush_github_cache();
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'wp_git_tickets',
					'wpgit_flushed' => '1',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	public static function add_admin_menu() {
		add_options_page(
			__( 'WP Git Tickets', 'wp-git-tickets' ),
			__( 'WP Git Tickets', 'wp-git-tickets' ),
			'manage_options',
			'wp_git_tickets',
			array( __CLASS__, 'options_page' )
		);
	}

	/**
	 * @param mixed $value Raw settings.
	 * @return array
	 */
	public static function sanitize_settings( $value ) {
		$current = get_option( 'wpgit_settings', array() );
		if ( ! is_array( $current ) ) {
			$current = array();
		}
		if ( ! is_array( $value ) ) {
			$value = array();
		}

		$token = isset( $value['github_token'] ) ? trim( (string) $value['github_token'] ) : '';
		if ( $token === '' && ! empty( $current['github_token'] ) ) {
			$token = $current['github_token'];
		}

		return array(
			'github_token' => sanitize_text_field( $token ),
			'github_org'   => sanitize_text_field( $value['github_org'] ?? '' ),
			'debug_mode'   => empty( $value['debug_mode'] ) ? 0 : 1,
		);
	}

	public static function settings_init() {
		register_setting(
			'wpgit_plugin_page',
			'wpgit_settings',
			array(
				'type'              => 'array',
				'default'           => array(),
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);
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

		add_settings_section(
			'wpgit_main_section',
			__( 'GitHub configuration', 'wp-git-tickets' ),
			null,
			'wpgit_plugin_page'
		);
		add_settings_field(
			'github_token',
			__( 'Personal Access Token', 'wp-git-tickets' ),
			array( __CLASS__, 'token_render' ),
			'wpgit_plugin_page',
			'wpgit_main_section'
		);
		add_settings_field(
			'github_org',
			__( 'Organization / username', 'wp-git-tickets' ),
			array( __CLASS__, 'org_render' ),
			'wpgit_plugin_page',
			'wpgit_main_section'
		);
		add_settings_field(
			'debug_mode',
			__( 'Debug mode', 'wp-git-tickets' ),
			array( __CLASS__, 'debug_render' ),
			'wpgit_plugin_page',
			'wpgit_main_section'
		);
	}

	public static function token_render() {
		$options = get_option( 'wpgit_settings', array() );
		$has     = ! empty( $options['github_token'] );
		printf(
			'<input type="password" name="wpgit_settings[github_token]" value="" class="regular-text" autocomplete="new-password" placeholder="%s" />',
			$has
				? esc_attr__( 'Leave blank to keep the current token', 'wp-git-tickets' )
				: esc_attr__( 'GitHub personal access token', 'wp-git-tickets' )
		);
		if ( $has ) {
			echo '<p class="description">' . esc_html__( 'A token is already saved. Leave the field empty to keep it.', 'wp-git-tickets' ) . '</p>';
		}
	}

	public static function org_render() {
		$options = get_option( 'wpgit_settings', array() );
		printf(
			'<input type="text" name="wpgit_settings[github_org]" value="%s" class="regular-text" placeholder="%s" />',
			esc_attr( $options['github_org'] ?? '' ),
			esc_attr__( 'e.g. MyOrganization', 'wp-git-tickets' )
		);
	}

	public static function debug_render() {
		$options = get_option( 'wpgit_settings', array() );
		printf(
			'<label><input type="checkbox" name="wpgit_settings[debug_mode]" value="1" %s /> %s</label>',
			checked( 1, $options['debug_mode'] ?? 0, false ),
			esc_html__( 'Enable debug logging (PHP error_log)', 'wp-git-tickets' )
		);
	}

	public static function options_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$repos     = wpgit_get_repos_from_api();
		$blacklist = get_option( 'wpgit_blacklist', array() );
		$flushed   = isset( $_GET['wpgit_flushed'] ) ? sanitize_text_field( wp_unslash( $_GET['wpgit_flushed'] ) ) : '';
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'WP Git Tickets settings', 'wp-git-tickets' ); ?></h1>
			<?php if ( '1' === $flushed ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html__( 'GitHub cache cleared.', 'wp-git-tickets' ); ?></p></div>
			<?php endif; ?>
			<p>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wpgit_flush_cache' ), 'wpgit_flush_cache' ) ); ?>">
					<?php echo esc_html__( 'Clear GitHub cache (README / feeds)', 'wp-git-tickets' ); ?>
				</a>
			</p>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'wpgit_plugin_page' );
				do_settings_sections( 'wpgit_plugin_page' );
				?>

				<hr>
				<h2><?php echo esc_html__( 'Repository visibility', 'wp-git-tickets' ); ?></h2>
				<p><?php echo esc_html__( 'Uncheck repositories you do not want in the contact form (e.g. .github, website).', 'wp-git-tickets' ); ?></p>

				<?php if ( is_wp_error( $repos ) ) : ?>
					<div class="notice notice-error"><p><?php echo esc_html__( 'Unable to list repositories:', 'wp-git-tickets' ); ?> <?php echo esc_html( $repos->get_error_message() ); ?></p></div>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th style="width: 30px;"></th>
								<th><?php echo esc_html__( 'Repository name', 'wp-git-tickets' ); ?></th>
							</tr>
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
					<p class="description"><?php echo esc_html__( 'Note: unchecked repositories are ignored by the contact form.', 'wp-git-tickets' ); ?></p>
				<?php endif; ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
