<?php
/**
 * Intégration Contact Form 7 (inchangée fonctionnellement).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPGIT_CF7 {

	public static function init() {
		add_shortcode( 'github_repo_list', array( __CLASS__, 'repo_list_shortcode' ) );
		add_filter( 'wpcf7_form_elements', 'do_shortcode' );
		add_action( 'wpcf7_before_send_mail', array( __CLASS__, 'before_send_mail' ) );
	}

	public static function repo_list_shortcode() {
		$blacklist = get_option( 'wpgit_blacklist', array() );

		$all_repos = get_transient( 'wpgit_all_repos_cache' );
		if ( false === $all_repos ) {
			$all_repos = wpgit_get_repos_from_api();
			if ( ! is_wp_error( $all_repos ) ) {
				set_transient( 'wpgit_all_repos_cache', $all_repos, 6 * HOUR_IN_SECONDS );
			}
		}

		if ( is_wp_error( $all_repos ) ) {
			return 'Erreur technique.';
		}

		$output  = '<select name="nom-logiciel" class="wpcf7-form-control wpcf7-select" required>';
		$output .= '<option value="">--- Choisir un logiciel ---</option>';

		foreach ( $all_repos as $name ) {
			if ( in_array( $name, (array) $blacklist, true ) ) {
				$output .= '<option value="' . esc_attr( $name ) . '">' . esc_html( $name ) . '</option>';
			}
		}
		$output .= '</select>';
		return $output;
	}

	/**
	 * @param WPCF7_ContactForm $contact_form
	 */
	public static function before_send_mail( $contact_form ) {
		if ( ! class_exists( 'WPCF7_Submission' ) ) {
			return;
		}

		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return;
		}

		$data     = $submission->get_posted_data();
		$options  = get_option( 'wpgit_settings' );
		$log_file = ABSPATH . 'debug_git.log';

		$get_cf7_val = function ( $field ) use ( $data ) {
			$val = isset( $data[ $field ] ) ? $data[ $field ] : '';
			return is_array( $val ) ? ( $val[0] ?? '' ) : $val;
		};

		$type     = $get_cf7_val( 'type-demande' );
		$logiciel = $get_cf7_val( 'nom-logiciel' );
		$titre    = $get_cf7_val( 'ticket-title' );
		$desc     = $get_cf7_val( 'ticket-desc' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $log_file, '--- Nouvelle tentative : ' . date( 'Y-m-d H:i:s' ) . " ---\n", FILE_APPEND );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $log_file, "Type extrait : '$type' | Logiciel : '$logiciel'\n", FILE_APPEND );

		$clean_type = trim( strtolower( $type ) );
		$is_bug     = ( $clean_type === 'un bug' );
		$is_feature = ( $clean_type === 'une fonction souhaitée' );

		if ( ( $is_bug || $is_feature ) && ! empty( $logiciel ) ) {
			$org   = $options['github_org'] ?? '';
			$token = $options['github_token'] ?? '';
			$name  = ! empty( $data['your-name'] ) ? $data['your-name'] : 'Inconnu du site web';
			if ( is_array( $name ) ) {
				$name = $name[0] ?? 'Inconnu du site web';
			}

			$response = wp_remote_post(
				"https://api.github.com/repos/$org/$logiciel/issues",
				array(
					'headers' => array(
						'Authorization' => 'token ' . $token,
						'Accept'        => 'application/vnd.github.v3+json',
						'User-Agent'    => 'WP-Git-Bot',
					),
					'body'    => wp_json_encode(
						array(
							'title'  => '[' . $type . '] ' . $titre,
							'body'   => "Posté par : $name\n\n" . $desc,
							'labels' => array( $is_bug ? 'bug' : 'enhancement' ),
						)
					),
				)
			);

			$code = wp_remote_retrieve_response_code( $response );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $log_file, "Réponse GitHub (Code $code)\n", FILE_APPEND );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $log_file, "Condition non remplie. (Vérifiez l'orthographe de '$type')\n", FILE_APPEND );
		}
	}
}
