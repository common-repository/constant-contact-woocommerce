<?php
/**
 * Handle plugin uninstall processes.
 *
 * @author  Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
 * @package WebDevStudios\CCForWoo\Database
 * @since   2019-10-10
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

/**
 * Delete abandoned checkouts table.
 *
 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
 * @since  2019-10-10
 */
function cc_woo_delete_abandoned_checkouts_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'cc_abandoned_checkouts';
	$wpdb->query(
		//@codingStandardsIgnoreStart
		"DROP TABLE IF EXISTS {$table_name}"
		//@codingStandardsIgnoreEnd
	);

	delete_option( 'cc_abandoned_checkouts_db_version' );
}

cc_woo_delete_abandoned_checkouts_table();
