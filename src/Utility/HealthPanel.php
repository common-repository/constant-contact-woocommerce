<?php
/**
 * Constant Contact + WooCommerce Health Panel
 *
 * Adds debugging information to the Site Health panel.
 *
 * @since   2.2.0
 * @package cc-woo
 */

namespace WebDevStudios\CCForWoo\Utility;

use WebDevStudios\CCForWoo\Meta\ConnectionStatus;
use WebDevStudios\CCForWoo\Plugin;
use WebDevStudios\CCForWoo\AbandonedCheckouts\CheckoutsTable;

/**
 * Class HealthPanel
 *
 * @package WebDevStudios\CCForWoo\Utility
 * @since   2.2.0
 */
class HealthPanel {

	public function __construct() {
		add_filter( 'debug_information', [ $this, 'health_information' ], 1 );
	}

	/**
	 * Callback to add in our own cusotm site health information.
	 *
	 * @since 2.2.0
	 *
	 * @throws Exception
	 *
	 * @param array $debug_info Array of debug info panels.
	 * @return array
	 */
	public function health_information( $debug_info ) {

		$connection = new ConnectionStatus();
		$debug_info['constant-contact-woocommerce'] = [
			'label'       => esc_html__( 'Constant Contact + WooCommerce', 'constant-contact-woocommerce' ),
			'description' => esc_html__( 'Debugging and troubleshooting information for support purposes', 'constant-contact-woocommerce' ),
			'fields'      => [
				[
					'label' => esc_html__( 'Plugin version', 'constant-contact-woocommerce' ),
					'value' => Plugin::PLUGIN_VERSION,
				],
				[
					'label' => esc_html__( 'Connection status', 'constant-contact-woocommerce' ),
					'value' => ( $connection->is_connected() )
						? esc_html__( 'Connected', 'constant-contact-woocommerce' )
						: esc_html__( 'Disconnected', 'constant-contact-woocommerce' )
				],
				[
					'label' => esc_html__( 'Abandoned checkouts total pending items', 'constant-contact-woocommerce' ),
					'value' => $this->abandoned_checkouts_count(),
				],
				[
					'label' => esc_html__( 'Abandoned checkouts expiration cron status', 'constant-contact-woocommerce' ),
					'value' => ( wp_next_scheduled( 'cc_woo_check_expired_checkouts' ) )
						? esc_html__( 'Scheduled', 'constant-contact-woocommerce' )
						: esc_html__( 'Not scheduled', 'constant-contact-woocommerce' ),
				],
				[
					'label' => esc_html__( 'Current user has a CC key', 'constant-contact-woocommerce' ),
					'value' => ( $this->user_has_cc_key() )
						? esc_html__( 'True', 'constant-contact-woocommerce' )
						: esc_html__( 'False', 'constant-contact-woocommerce' ),
				],
			]
		];

		return $debug_info;
	}

	/**
	 * Check if the current user has a Constant Contact API key.
	 *
	 * @since 2.2.0
	 *
	 * @return bool
	 */
	private function user_has_cc_key(): bool {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$query = <<<SQL
SELECT
	key_id
FROM
{$GLOBALS['wpdb']->prefix}woocommerce_api_keys
WHERE
	user_id = %d
AND
	(
		description LIKE '%Constant Contact%'
	OR
		description LIKE '%ConstantContact%'
	)
SQL;

		return ! empty( $GLOBALS['wpdb']->get_col( $GLOBALS['wpdb']->prepare( $query, $user_id ) ) );
	}

	/**
	 * Returns a count of total abandoned checkouts.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	private function abandoned_checkouts_count() : string {
		$table = CheckoutsTable::get_table_name();
		$query = <<<SQL
SELECT
    count(*)
FROM
    {$table}
SQL;
		$count = (string) $GLOBALS['wpdb']->get_var( $query );
		return ( ! empty( $count ) ) ? $count : '0';
	}
}
