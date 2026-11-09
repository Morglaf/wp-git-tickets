<?php
/**
 * Uninstall cleanup.
 *
 * @package WPGIT
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wpgit_settings' );
delete_option( 'wpgit_blacklist' );

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$rows = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_wpgit_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_wpgit_' ) . '%'
	)
);

if ( is_array( $rows ) ) {
	foreach ( $rows as $option_name ) {
		if ( 0 === strpos( $option_name, '_transient_timeout_' ) ) {
			delete_transient( substr( $option_name, strlen( '_transient_timeout_' ) ) );
		} elseif ( 0 === strpos( $option_name, '_transient_' ) ) {
			delete_transient( substr( $option_name, strlen( '_transient_' ) ) );
		}
	}
}
