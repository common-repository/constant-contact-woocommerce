<?php
/**
 * Class to handle creation of abandoned checkouts table.
 *
 * @author  Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
 * @package WebDevStudios\CCForWoo\AbandonedCheckouts
 * @since   1.2.0
 */

namespace WebDevStudios\CCForWoo\AbandonedCheckouts;

use WebDevStudios\OopsWP\Structure\Service;

/**
 * Class CheckoutsTable
 *
 * @author  Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
 * @package WebDevStudios\CCForWoo\AbandonedCheckouts
 * @since   1.2.0
 */
class CheckoutsTable extends Service {

	/**
	 * Current version of abandoned checkouts table.
	 *
	 * @since 1.2.0
	 */
	const DB_VERSION = '2.1';

	/**
	 * Option name for abandoned checkouts db version.
	 *
	 * @since 1.2.0
	 */
	const DB_VERSION_OPTION_NAME = 'cc_abandoned_checkouts_db_version';

	/**
	 * Abandoned checkouts table name.
	 *
	 * @since 1.2.0
	 */
	const TABLE_NAME = 'cc_abandoned_checkouts';

	/**
	 * Register hooks with WordPress.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 * @since  1.2.0
	 */
	public function register_hooks() {
		add_action( 'admin_init', [ $this, 'update_db_check' ] );
	}

	/**
	 * Create abandoned checkouts table.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 * @since  1.2.0
	 */
	public function create_table() {
		global $wpdb;

		$table_name = self::get_table_name();

		$sql = "CREATE TABLE {$table_name} (
			checkout_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_email varchar(200) NOT NULL DEFAULT '',
			checkout_contents longtext NOT NULL,
			checkout_updated datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			checkout_updated_ts int(11) unsigned NOT NULL DEFAULT 0,
			checkout_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			checkout_created_ts int(11) unsigned NOT NULL DEFAULT 0,
			checkout_uuid varchar(36) NOT NULL DEFAULT '',
			PRIMARY KEY (checkout_id),
			UNIQUE KEY checkout_uuid (checkout_uuid)
		) {$wpdb->get_charset_collate()}";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		add_option( self::DB_VERSION_OPTION_NAME, self::DB_VERSION );
	}

	/**
	 * Update abandoned checkouts table.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 * @since  1.2.0
	 */
	protected function update_table() {
		global $wpdb;

		$table_name     = self::get_table_name();
		$old_table_name = "{$wpdb->prefix}cc_abandoned_carts";

		// phpcs:disable WordPress.DB.PreparedSQL -- Okay use of unprepared variable for table name in SQL.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$old_table_name}'" ) ) {

			// Clear old "abandoned carts" data prior to 2.0 switch to "checkouts".
			$wpdb->query(
				"DROP TABLE {$old_table_name}"
			);
			$this->create_table();

			delete_option( 'cc_abandoned_carts_db_version' );
		}
		// phpcs:enable

		// phpcs:disable WordPress.DB.PreparedSQL -- Okay use of unprepared variable for table name in SQL.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) ) {

			// Any data updates would be performed here.
			update_option( self::DB_VERSION_OPTION_NAME, self::DB_VERSION );
		}
		// phpcs:enable
	}

	/**
	 * Check if table exists and is up-to-date.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 * @since  1.2.0
	 */
	public function update_db_check() {
		if ( ! get_site_option( self::DB_VERSION_OPTION_NAME ) ) {

			// Fresh install: create table.
			$this->create_table();
		} elseif ( self::DB_VERSION !== get_site_option( self::DB_VERSION_OPTION_NAME ) ) {

			// Updated install: update table.
			$this->update_table();
		}
	}

	/**
	 * A simple utility for grabbing the full table name, including the WPDB table prefix.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @return string
	 */
	public static function get_table_name() : string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}
}
