<?php
/**
 * Class to listen to WooCommerce checkouts and possibly store checkouts that are "abandoned".
 *
 * @author  Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
 * @package WebDevStudios\CCForWoo\AbandonedCheckouts
 * @since   1.2.0
 */

namespace WebDevStudios\CCForWoo\AbandonedCheckouts;

use WebDevStudios\OopsWP\Structure\Service;
use WC_Customer;
use WC_Order;
use DateTime;
use DateInterval;

/**
 * Class CheckoutHandler
 *
 * @author  Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
 * @package WebDevStudios\CCForWoo\AbandonedCheckouts
 * @since   1.2.0
 */
class CheckoutHandler extends Service {

	/**
	 * Register hooks with WordPress.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 * @since  1.2.0
	 */
	public function register_hooks() {
		add_action( 'woocommerce_before_checkout_form', [ $this, 'enqueue_scripts' ] );


		add_action( 'woocommerce_after_template_part', [ $this, 'save_or_clear_checkout_data' ], 10, 4 );
		add_action( 'woocommerce_cart_updated', [ $this, 'update_checkout_data' ] );
		add_action( 'woocommerce_set_cart_cookies', [ $this, 'update_checkout_data' ] );

		add_action( 'cc_woo_check_expired_checkouts', [ $this, 'delete_expired_checkouts' ] );

		add_action( 'wp_ajax_cc_woo_abandoned_checkouts_capture_guest_checkout', [ $this, 'maybe_capture_guest_checkout' ] );
		add_action( 'wp_ajax_nopriv_cc_woo_abandoned_checkouts_capture_guest_checkout', [ $this, 'maybe_capture_guest_checkout' ] );

		add_action( 'woocommerce_checkout_create_order', [ $this, 'clear_purchased_data' ], 10, 2 );
	}


	/**
	 * Load front-end scripts.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( is_user_logged_in() ) {
			return;
		}

		wp_enqueue_script( 'cc-woo-public' );
	}

	/**
	 * AJAX handler for attempting to capture guest checkouts.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since  1.2.0
	 */
	public function maybe_capture_guest_checkout() {
		$data = filter_input_array( INPUT_POST, [
			'nonce' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
			'email' => FILTER_SANITIZE_EMAIL,
		] );

		if ( empty( $data['nonce'] ) || ! wp_verify_nonce( $data['nonce'], 'woocommerce-process_checkout' ) ) {
			wp_send_json_error( esc_html__( 'Invalid nonce.', 'constant-contact-woocommerce' ) );
		}

		$email = filter_var( $data['email'], FILTER_VALIDATE_EMAIL );

		if ( ! $email ) {
			wp_send_json_error( esc_html__( 'Invalid email.', 'constant-contact-woocommerce' ) );
		}

		WC()->session->set( 'billing_email', $email );
		$this->save_checkout_data( $email, true );

		wp_send_json_success();
	}

	/**
	 * Either call an update of checkout data which will be saved or remove checkout data based on what template we arrive at.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 *
	 * @since  1.2.0
	 * @param  string $template_name Current template file name.
	 * @param  string $template_path Current template path.
	 * @param  string $located       Full local path to current template file.
	 * @param  array  $args          Template args.
	 */
	public function save_or_clear_checkout_data( $template_name, $template_path, $located, $args ) {

		// If checkout page displayed, save checkout data.
		if ( 'checkout/form-checkout.php' === $template_name ) {
			$this->save_checkout_data();
		}
	}

	/**
	 * Helper function to update current checkout session data in db.
	 *
	 * Used to strip unneeded params from callbacks.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 * @since  1.2.0
	 */
	public function update_checkout_data() {
		if ( is_cart() || is_checkout() ) {
			$this->save_checkout_data();
		}
	}

	/**
	 * Retrieve specific user's checkout data.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @param  string $select     Field to return.
	 * @param  mixed  $where      String or array of WHERE clause predicates, using placeholders for values.
	 * @param  array  $where_args Array of WHERE clause arguments.
	 * @param  string $order_by   Order by column.
	 * @param  string $order      Order (ASC/DESC).
	 * @param  string $limit      LIMIT clause.
	 * @param  array  $limit_args Array of LIMIT clause arguments.
	 * @return mixed              Checkout data if exists, else null.
	 */
	public static function get_checkout_data( string $select, $where, array $where_args, string $order_by = 'checkout_updated_ts', string $order = 'DESC', string $limit = '', array $limit_args = [] ) {
		global $wpdb;

		$table_name = CheckoutsTable::get_table_name();
		$where      = is_array( $where ) ? implode( ' AND ', $where ) : $where;
		$where      = empty( $where ) ? 1 : $where;

		// Construct query to return checkout data.
		// phpcs:disable -- Disabling a number of sniffs that erroneously flag following block of code.
		// $where often includes placeholders for replacement via $wpdb->prepare(). $where_values provides those values.
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$select}
				FROM {$table_name}
				WHERE {$where}
				ORDER BY {$order_by} {$order}
				{$limit}",
				array_merge( $where_args, $limit_args )
			)
		);
		// phpcs:enable
	}

	/**
	 * Helper function to retrieve checkout contents based on checkout UUID.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 *
	 * @since  1.2.0
	 * @param  string $checkout_uuid Checkout UUID.
	 * @return array                 Checkout contents.
	 */
	public static function get_checkout_contents( $checkout_uuid ) {
		$checkout = self::get_checkout_data( 'checkout_contents', 'checkout_uuid = %s', [ $checkout_uuid ] );

		if ( empty( $checkout ) ) {
			return [];
		}

		return maybe_unserialize( array_shift( $checkout )->checkout_contents );
	}

	/**
	 * Helper function to retrieve checkout UUID for current user.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 *
	 * @since  1.3.0
	 * @return string Checkout UUID if exists, else empty string.
	 */
	public static function get_checkout_uuid_by_user() {
		$checkout = self::get_checkout_data( 'checkout_uuid', 'user_id = %d', [ get_current_user_id() ] );

		return ( empty( $checkout ) ? '' : array_shift( $checkout )->checkout_uuid );
	}

	/**
	 * Save current checkout data to db.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @param  string  $billing_email Manually set customer billing email if provided.
	 * @param  boolean $is_checkout   Manually mark current page as checkout if necessary (e.g., coming from ajax callback).
	 * @return void
	 */
	protected function save_checkout_data( string $billing_email = '', bool $is_checkout = false ) {
		global $wpdb;

		// Get current user email.
		$session_customer      = WC()->session->get( 'customer' );
		$session_billing_email = is_array( $session_customer ) && key_exists( 'email', $session_customer ) ? $session_customer['email'] : '';
		$billing_email         = $billing_email ?: $session_billing_email ?: WC()->checkout->get_value( 'billing_email' ) ?: WC()->session->get( 'billing_email' );
		$is_checkout           = $is_checkout ?: is_checkout();
		$checkout_uuid         = WC()->session->get( 'checkout_uuid' );

		$addresses = [];

		foreach( $this->get_the_address_fields() as $field ) {
			$value = '';

			$value_session  = WC()->session->get( $field );
			if ( ! empty( $value_session ) ) {
				$value = $value_session;
			} else {
				$value_checkout = WC()->checkout->get_value( $field );
				if ( ! empty( $value_checkout ) ) {
					$value = $value_checkout;
				}
			}

			$addresses[ $field ] = $value;
		}

		if ( empty( $billing_email ) ) {
			return;
		}

		// Check for existing checkout session.
		if ( ! $checkout_uuid ) {

			// Only create session if cart is not empty.
			// This is to avoid re-creating checkout UUID during checkout process.
			if ( $is_checkout && empty( WC()->cart->get_cart() ) ) {
				return;
			}

			// Retrieve existing checkout UUID for registered users only.
			if ( is_user_logged_in() ) {
				$existing_uuid = $this->get_checkout_uuid_by_user();
			}

			// Only create session if currently on checkout page or if current user has an existing session saved.
			if ( ! $is_checkout && empty( $existing_uuid ) ) {
				return;
			}

			$checkout_uuid = isset( $existing_uuid ) && ! empty( $existing_uuid ) ? $existing_uuid : wp_generate_uuid4();

			WC()->session->set( 'checkout_uuid', $checkout_uuid );
		}

		$current_time = current_time( 'mysql', 1 );
		$table_name   = CheckoutsTable::get_table_name();

		// phpcs:disable WordPress.DB.PreparedSQL -- Okay use of unprepared variable for table name in SQL.
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table_name} (
					`user_id`,
					`user_email`,
					`checkout_contents`,
					`checkout_updated`,
					`checkout_updated_ts`,
					`checkout_created`,
					`checkout_created_ts`,
					`checkout_uuid`
				) VALUES (
					%d,
					%s,
					%s,
					%s,
					%d,
					%s,
					%d,
					%s
				) ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`), `user_email` = VALUES(`user_email`), `checkout_updated` = VALUES(`checkout_updated`), `checkout_updated_ts` = VALUES(`checkout_updated_ts`), `checkout_contents` = VALUES(`checkout_contents`)",
				get_current_user_id(),
				$billing_email,
				maybe_serialize( [
					'products' => array_values( WC()->cart->get_cart() ),
					'coupons'  => WC()->cart->get_applied_coupons(),
					'addresses' => $addresses,
				] ),
				$current_time,
				strtotime( $current_time ),
				$current_time,
				strtotime( $current_time ),
				$checkout_uuid
			)
		);
		// phpcs:enable
	}

	/**
	 * Remove current checkout session data from db upon successful order submission.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @param  WC_Order $order    Newly created order object.
	 * @param  array    $data     Posted data.
	 * @return void
	 */
	public function clear_purchased_data( WC_Order $order, array $data ) {
		if ( empty( $order ) ) {
			return;
		}

		$order->update_meta_data( 'cc_woo_checkout_uuid', WC()->session->get( 'checkout_uuid' ) );
		$this->remove_checkout_data();
		WC()->session->__unset( 'checkout_uuid' );
	}

	/**
	 * Helper function to remove checkout session data from db.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 * @since  1.2.0
	 */
	protected function remove_checkout_data() {
		global $wpdb;

		// Delete current checkout data.
		$wpdb->delete(
			CheckoutsTable::get_table_name(),
			[
				'checkout_uuid' => WC()->session->get( 'checkout_uuid' ),
			],
			[
				'%s',
			]
		);
	}

	/**
	 * Delete expired checkouts.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 * @since  1.2.0
	 */
	public function delete_expired_checkouts() {
		global $wpdb;

		// Delete all checkouts at least 30 days old.
		$table_name = CheckoutsTable::get_table_name();

		$wpdb->query(
			$wpdb->prepare(
				// phpcs:disable WordPress.DB.PreparedSQL -- Okay use of unprepared variable for table name in SQL.
				"DELETE FROM {$table_name}
				WHERE `checkout_updated_ts` <= %s",
				// phpcs:enable
				( new DateTime() )->sub( new DateInterval( 'P30D' ) )->format( 'U' )
			)
		);
	}

	/**
	 * Return array of address fields we want.
	 *
	 * @author Michael Beckwith <michael@webdevstudios.com>
	 * @since  2.1.0
	 */
	protected function get_the_address_fields() {
		return [
			'shipping_address_1',
			'shipping_address_2',
			'shipping_city',
			'shipping_country',
			'shipping_postcode',
			'shipping_state',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_country',
			'billing_postcode',
			'billing_state',
			'billing_phone',
			'billing_email',
		];
	}
}
