<?php
/**
 * Contact Form 7 integration.
 *
 * @package WPGIT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPGIT_CF7 {

	public static function init() {
		add_shortcode( 'wpgit_repo_list', array( __CLASS__, 'repo_list_shortcode' ) );
		add_shortcode( 'github_repo_list', array( __CLASS__, 'repo_list_shortcode' ) ); // Legacy alias.

		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_hook_cf7' ), 20 );
	}

	/**
	 * Register CF7 hooks only when Contact Form 7 is available.
	 */
	public static function maybe_hook_cf7() {
		if ( ! defined( 'WPCF7_VERSION' ) && ! class_exists( 'WPCF7' ) ) {
			return;
		}
		add_filter( 'wpcf7_form_elements', 'do_shortcode' );
		add_action( 'wpcf7_before_send_mail', array( __CLASS__, 'before_send_mail' ) );
	}

	/**
	 * @return string
	 */
	public static function repo_list_shortcode() {
		$allowed = get_option( 'wpgit_blacklist', array() );

		$all_repos = get_transient( 'wpgit_all_repos_cache' );
		if ( false === $all_repos ) {
			$all_repos = wpgit_get_repos_from_api();
			if ( ! is_wp_error( $all_repos ) ) {
				set_transient( 'wpgit_all_repos_cache', $all_repos, 6 * HOUR_IN_SECONDS );
			}
		}

		if ( is_wp_error( $all_repos ) ) {
			return esc_html__( 'Technical error.', 'wp-git-tickets' );
		}

		$output  = '<select name="nom-logiciel" class="wpcf7-form-control wpcf7-select" required>';
		$output .= '<option value="">' . esc_html__( '--- Choose a software ---', 'wp-git-tickets' ) . '</option>';

		foreach ( $all_repos as $name ) {
			if ( in_array( $name, (array) $allowed, true ) ) {
				$output .= '<option value="' . esc_attr( $name ) . '">' . esc_html( $name ) . '</option>';
			}
		}
		$output .= '</select>';
		return $output;
	}

	/**
	 * @param mixed $contact_form Contact form instance.
	 */
	public static function before_send_mail( $contact_form ) {
		if ( ! class_exists( 'WPCF7_Submission' ) ) {
			return;
		}

		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return;
		}

		$data    = $submission->get_posted_data();
		$options = get_option( 'wpgit_settings', array() );
		$debug   = ! empty( $options['debug_mode'] );

		$get_cf7_val = function ( $field ) use ( $data ) {
			$val = isset( $data[ $field ] ) ? $data[ $field ] : '';
			$val = is_array( $val ) ? ( $val[0] ?? '' ) : $val;
			return is_string( $val ) ? $val : '';
		};

		$type     = sanitize_text_field( $get_cf7_val( 'type-demande' ) );
		$logiciel = sanitize_text_field( $get_cf7_val( 'nom-logiciel' ) );
		$titre    = sanitize_text_field( $get_cf7_val( 'ticket-title' ) );
		$desc     = sanitize_textarea_field( $get_cf7_val( 'ticket-desc' ) );
		$name     = sanitize_text_field( $get_cf7_val( 'your-name' ) );
		if ( $name === '' ) {
			$name = __( 'Unknown website visitor', 'wp-git-tickets' );
		}

		if ( $debug ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( '[WP Git Tickets] Attempt type="%s" repo="%s"', $type, $logiciel ) );
		}

		$clean_type = trim( strtolower( $type ) );
		$is_bug     = ( $clean_type === 'un bug' || $clean_type === 'a bug' || $clean_type === 'bug' );
		$is_feature = (
			$clean_type === 'une fonction souhaitée'
			|| $clean_type === 'une fonction souhaitee'
			|| $clean_type === 'a feature request'
			|| $clean_type === 'feature'
		);

		if ( ! ( $is_bug || $is_feature ) || $logiciel === '' ) {
			if ( $debug ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[WP Git Tickets] Condition not met for issue creation.' );
			}
			return;
		}

		$allowed = get_option( 'wpgit_blacklist', array() );
		if ( ! is_array( $allowed ) || ! in_array( $logiciel, $allowed, true ) ) {
			if ( $debug ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[WP Git Tickets] Repository not in allowed list: ' . $logiciel );
			}
			return;
		}

		$org   = sanitize_text_field( $options['github_org'] ?? '' );
		$token = (string) ( $options['github_token'] ?? '' );
		if ( $org === '' || $token === '' ) {
			return;
		}

		$url = sprintf(
			'https://api.github.com/repos/%s/%s/issues',
			rawurlencode( $org ),
			rawurlencode( $logiciel )
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'token ' . $token,
					'Accept'        => 'application/vnd.github.v3+json',
					'User-Agent'    => 'WP-Git-Tickets',
				),
				'body'    => wp_json_encode(
					array(
						'title'  => '[' . $type . '] ' . $titre,
						'body'   => sprintf(
							/* translators: %s: submitter name */
							__( 'Submitted by: %s', 'wp-git-tickets' ),
							$name
						) . "\n\n" . $desc,
						'labels' => array( $is_bug ? 'bug' : 'enhancement' ),
					)
				),
				'timeout' => 20,
			)
		);

		if ( $debug ) {
			$code = is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_response_code( $response );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[WP Git Tickets] GitHub response: ' . $code );
		}
	}
}
