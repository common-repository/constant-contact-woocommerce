<?php
/**
 * WooCommerce Checkout Block Newsletter
 * @since   NEXT
 * @author  Michael Beckwith <michael@webdevstudios.com>
 * @package cc-woo
 */

namespace WebDevStudios\CCForWoo\Utility;

use WebDevStudios\CCForWoo\Meta\ConnectionStatus;

/**
 * CheckoutBlockNewsletter.
 *
 * @since 2.3.0
 */
class CheckoutBlockNewsletter {

	/**
	 * Namespace for block.
	 *
	 * @var string
	 */
	public static $namespace = 'wc/cc-woo';

	/**
	 * Meta field
	 *
	 * @var string
	 */
	const CUSTOMER_PREFERENCE_META_FIELD = 'cc_woo_customer_agrees_to_marketing';

	/**
	 * Default option value.
	 *
	 * @var string
	 */
	const STORE_NEWSLETTER_DEFAULT_OPTION = 'cc_woo_customer_data_email_opt_in_default';

	/**
	 * Register our hooks.
	 *
	 * @since 2.3.0
	 */
	public function register_hooks() {
		add_action( 'woocommerce_set_additional_field_value', [ $this, 'set_agreement_value_on_object' ], 10, 4 );
		add_action( 'woocommerce_sanitize_additional_field', [ $this, 'sanitize_agreement_value' ], 10, 2 );
		add_filter( 'woocommerce_get_default_value_for_' . self::$namespace . '/newsletter-signup', [ $this, 'set_default_value' ], 10, 3 );
	}

	/**
	 * Registers a new field to be output with the checkout block.
	 *
	 * @since 2.3.0
	 * @throws \Exception
	 */
	public function add_newsletter_to_checkout_block() {

		$connection = new ConnectionStatus();
		if ( ! $connection->is_connected() ) {
			return;
		}

		$block_args = $this->get_newsletter_checkout_block_args();
		if ( function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			woocommerce_register_additional_checkout_field(
				$block_args
			);
		}
	}

	/**
	 * Returns the arguments to be used for our checkout block field.
	 *
	 * @since 2.3.0
	 *
	 * @return string[]
	 */
	private function get_newsletter_checkout_block_args() {
		$checkbox_location = get_option( 'cc_woo_store_information_checkbox_location', 'woocommerce_after_checkout_billing_form' );
		$location          = 'order';
		if ( $checkbox_location === 'woocommerce_after_checkout_billing_form' ) {
			$location = 'address';
		}

		return [
			'id'       => self::$namespace . '/newsletter-signup',
			'label'    => 'Do you want to subscribe to our newsletter?',
			'location' => $location,
			'type'     => 'checkbox',
		];
	}

	/**
	 * Sanitizes and validates our agreement value.
	 *
	 * @since 2.3.0
	 *
	 * @param string $value Value being sanitied.
	 * @param string $key   Key associated with the value.
	 *
	 * @return bool|mixed
	 */
	public function sanitize_agreement_value( $value, $key ) {
		if ( self::$namespace . '/newsletter-signup' !== $key ) {
			return $value;
		}

		return (bool) $value;
	}

	/**
	 * Save the agreement status for the order and user.
	 *
	 * @since 2.3.0
	 *
	 * @param string $key       Key to check.
	 * @param string $value     Whether or not they agreed to be signed up.
	 * @param string $group     Group that the field belongs to.
	 * @param object $wc_object WooCommerce object being acted on.
	 */
	public function set_agreement_value_on_object( $key, $value, $group, $wc_object ) {
		if ( self::$namespace . '/newsletter-signup' !== $key ) {
			return;
		}

		if ( ! in_array( $group, [ 'billing', 'shipping', 'other' ] ) ) {
			return;
		}

		$wc_object->update_meta_data( self::CUSTOMER_PREFERENCE_META_FIELD, $value, true );

		// This filter is from WooCommerce Core.
		$customer_id = apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() );
		if ( ! $customer_id ) {
			return;
		}

		// No user created from customer. Nothing to save.
		update_user_meta( $customer_id, self::CUSTOMER_PREFERENCE_META_FIELD, $value );
	}

	/**
	 * Return the default option state.
	 *
	 * @since 2.3.0
	 *
	 * @return bool
	 */
	private function get_store_default_checked_state(): bool {
		return 'true' === get_option( self::STORE_NEWSLETTER_DEFAULT_OPTION );
	}

	/**
	 * Set the default option state.
	 *
	 * @since 2.3.0
	 *
	 * @param string $value     Value to use for default value.
	 * @param string $group     Group that the value belongs to
	 * @param object $wc_object WooCommerce object.
	 *
	 * @return bool
	 */
	public function set_default_value( $value, $group, $wc_object ) {
		return $this->get_user_default_checked_state();
	}

	/**
	 * Return the default checked state for current user.
	 *
	 * @since 2.3.0
	 *
	 * @return bool
	 */
	private function get_user_default_checked_state(): bool {
		$user_preference = get_user_meta( get_current_user_id(), self::CUSTOMER_PREFERENCE_META_FIELD, true );

		return ! empty( $user_preference ) ? 'true' === $user_preference : $this->get_store_default_checked_state();
	}
}
