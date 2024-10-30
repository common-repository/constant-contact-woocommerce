<?php
/**
 * Controller for wc/cc-woo/abandoned-checkouts endpoint.
 *
 * @package WebDevStudios\CCForWoo\Rest\AbandonedCheckouts
 * @since   2019-10-16
 */

namespace WebDevStudios\CCForWoo\Rest\AbandonedCheckouts;

use WebDevStudios\CCForWoo\Utility\DebugLogging;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Controller;
use WP_REST_Response;
use WP_Error;
use WC_Product;

use WebDevStudios\CCForWoo\AbandonedCheckouts\CheckoutsTable;
use WebDevStudios\CCForWoo\AbandonedCheckouts\CheckoutHandler;
use WebDevStudios\CCForWoo\Rest\Registrar;

/**
 * Class AbandonedCheckouts\Controller
 *
 * @package WebDevStudios\CCForWoo\Rest\AbandonedCheckouts
 * @since   2019-10-16
 */
class Controller extends WP_REST_Controller {

	/**
	 * This endpoint's rest base.
	 *
	 * @since 2019-10-16
	 *
	 * @var string
	 */
	protected $rest_base;

	/**
	 * Constructor.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-16
	 */
	public function __construct() {
		$this->rest_base = 'abandoned-checkouts';
	}

	/**
	 * Register the Abandoned Checkouts route.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-16
	 */
	public function register_routes() {
		register_rest_route(
			Registrar::$namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => Schema::get_collection_params(),
				],
				'schema' => [ '\WebDevStudios\CCForWoo\Rest\AbandonedCheckouts\Schema', 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Check whether a given request has permission to show abandoned checkouts.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-11-12
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {

			$ctct_logger = new DebugLogging(
				wc_get_logger(),
				'CTCT Woo: no permission to check abandoned checkout',
				'warning',
				[ 'cc-woo-rest-not-allowed' => $request ]
			);
			$ctct_logger->log();

			return new WP_Error( 'cc-woo-rest-not-allowed', esc_html__( 'Sorry, you cannot list resources.', 'constant-contact-woocommerce' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}

	/**
	 * Register the Abandoned Checkouts endpoint.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-16
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return array
	 */
	public function get_items( $request ) {
		global $wpdb;

		$params      = $request->get_query_params();
		$page        = $this->get_page_param( $params );
		$per_page    = $this->get_per_page_param( $params );
		$offset      = 1 === $page ? 0 : ( $page - 1 ) * $per_page;
		$date_min    = $this->get_date_min_param( $params );
		$date_max    = $this->get_date_max_param( $params );
		$dates_where = $this->get_dates_where( $date_min, $date_max );
		$select      = '*';
		$order_by    = 'checkout_updated_ts';
		$order       = 'DESC';
		$limit       = 'LIMIT %d OFFSET %d';
		$limit_args  = [ $per_page, $offset ];

		$response = [
			'checkouts'     => $this->prepare_checkout_data_for_api_response( CheckoutHandler::get_checkout_data( $select, $dates_where['predicates'] ?? '', $dates_where['args'] ?? [], $order_by, $order, $limit, $limit_args ) ),
			'currency_code' => $this->get_currency_code(),
			'page'          => $page,
		];

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Get the "page" request param.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-28
	 *
	 * @param array $params The request params.
	 * @return int
	 */
	private function get_page_param( array $params ) : int {
		return (int) isset( $params['page'] ) ? $params['page'] : 1;
	}

	/**
	 * Get the "per_page" request param.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-28
	 *
	 * @param array $params The request params.
	 * @return int
	 */
	private function get_per_page_param( array $params ) : int {
		return (int) isset( $params['per_page'] ) ? $params['per_page'] : 10;
	}

	/**
	 * Get the "date_min" request param.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-28
	 *
	 * @param array $params The request params.
	 * @return string
	 */
	private function get_date_min_param( array $params ) : string {
		return (string) isset( $params['date_min'] ) ? $params['date_min'] : '';
	}

	/**
	 * Get the "date_max" request param.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-28
	 *
	 * @param array $params The request params.
	 * @return string
	 */
	private function get_date_max_param( array $params ) : string {
		return (string) isset( $params['date_max'] ) ? $params['date_max'] : '';
	}

	/**
	 * Gets the WHERE clause for passing date_min and date_max values via SQL.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-28
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 * @since 1.3.0 - Updated to fit CheckoutHandler::get_checkout_data setup for WHERE clause.
	 *
	 * @param string $date_min The oldest created_at date to get results from.
	 * @param string $date_max The most recent created_at date to get results from.
	 * @return array           Array of WHERE clause predicates and arguments.
	 */
	private function get_dates_where( string $date_min, string $date_max ) : array {
		if ( empty( $date_min ) && empty( $date_max ) ) {
			return [];
		}

		$where = [
			'predicates' => [],
			'args'       => [],
		];

		if ( ! empty( $date_min ) ) {
			$where['predicates'][] = 'checkout_created >= %s';
			$where['args'][]       = $date_min;
		}

		if ( ! empty( $date_max ) ) {
			$where['predicates'][] = 'checkout_created <= %s';
			$where['args'][]       = $date_max;
		}

		return $where;
	}

	/**
	 * Adds and modifies fields in individual checkouts before displaying them in the API response.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-23
	 *
	 * @param array $data The checkouts whose fields need preparation.
	 * @return array
	 */
	private function prepare_checkout_data_for_api_response( array $data ) {
		foreach ( $data as $checkout ) {
			$checkout->checkout_contents     = maybe_unserialize( $checkout->checkout_contents );
			$checkout->checkout_contents     = $this->get_additional_product_fields( $checkout->checkout_contents );
			$checkout->checkout_subtotal     = $this->get_checkout_sum_for_product_field( $checkout->checkout_contents, 'line_subtotal' );
			$checkout->checkout_total        = $this->get_checkout_sum_for_product_field( $checkout->checkout_contents, 'line_total' );
			$checkout->checkout_subtotal_tax = $this->get_checkout_sum_for_product_field( $checkout->checkout_contents, 'line_subtotal_tax' );
			$checkout->checkout_total_tax    = $this->get_checkout_sum_for_product_field( $checkout->checkout_contents, 'line_tax' );
			$checkout->checkout_recovery_url = $this->get_checkout_recovery_url( $checkout->checkout_uuid );
		}

		return $data;
	}

	/**
	 * Get the currency code for the store's base currency.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-23
	 *
	 * @return string
	 */
	private function get_currency_code() : string {
		return get_woocommerce_currency();
	}

	/**
	 * Looks at the value of the specified field in each product in the checkout, and gets the sum of those values.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-23
	 *
	 * @param array  $checkout_contents The checkout contents, whose products have the line items we want for calculating the sum.
	 * @param string $field_name Name of the product field to get.
	 * @return string
	 */
	private function get_checkout_sum_for_product_field( array $checkout_contents, string $field_name ) : string {
		$line_items = wp_list_pluck( $checkout_contents['products'], $field_name );

		if ( empty( $line_items ) || ! is_array( $line_items ) ) {
			return html_entity_decode( wp_strip_all_tags( wc_price( 0 ) ) );
		}

		return html_entity_decode( wp_strip_all_tags( wc_price( array_sum( $line_items ) ) ) );
	}

	/**
	 * Get the account recovery URL.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-23
	 *
	 * @param string $checkout_uuid The checkout UUID.
	 * @return string
	 */
	private function get_checkout_recovery_url( string $checkout_uuid ) : string {
		return add_query_arg( 'recover-checkout', $checkout_uuid, home_url() );
	}

	/**
	 * Get additional product fields to display in the API response--SKU, title, thumbnail, and more.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-23
	 *
	 * @param array $checkout_contents The original checkout contents.
	 * @return array The modified checkout contents.
	 */
	private function get_additional_product_fields( array $checkout_contents ) : array {
		foreach ( $checkout_contents['products'] as $n => $product ) {
			$wc_product = wc_get_product( $product['product_id'] );

			$checkout_contents['products'][ $n ]['product_title']     = $wc_product->get_title();
			$checkout_contents['products'][ $n ]['product_sku']       = $wc_product->get_sku();
			$checkout_contents['products'][ $n ]['product_permalink'] = $wc_product->get_permalink();
			$checkout_contents['products'][ $n ]['product_image_url'] = $this->get_product_image_url( $wc_product );
		}

		return $checkout_contents;
	}

	/**
	 * Get attachment URL for the product's full-size image.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-25
	 *
	 * @param WC_Product $wc_product The product whose image to get.
	 * @return string
	 */
	private function get_product_image_url( WC_Product $wc_product ) : string {
		return wp_get_attachment_url( $wc_product->get_image_id() );
	}

}

