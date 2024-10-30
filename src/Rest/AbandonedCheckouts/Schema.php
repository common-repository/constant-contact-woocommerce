<?php
/**
 * Schema for wc/cc-woo/abandoned-checkouts endpoint.
 *
 * @package WebDevStudios\CCForWoo\Rest\AbandonedCheckouts
 * @since   1.2.0
 */

namespace WebDevStudios\CCForWoo\Rest\AbandonedCheckouts;

/**
 * Class AbandonedCheckouts\Schema
 *
 * @package WebDevStudios\CCForWoo\Rest\AbandonedCheckouts
 * @since   1.2.0
 */
class Schema {

	/**
	 * Get the query params for Abandoned Checkouts.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @return array
	 */
	public static function get_collection_params() {
		return [
			'page'     => [
				'description' => esc_html__( 'Current page of paginated results.', 'constant-contact-woocommerce' ),
				'required'    => false,
				'type'        => 'integer',
			],
			'per_page' => [
				'description' => esc_html__( 'How many abandoned checkouts to show per page.', 'constant-contact-woocommerce' ),
				'required'    => false,
				'type'        => 'integer',
				'default'     => 10,
			],
			'date_min' => [
				'description' => esc_html__( 'Filters results to only show abandoned checkouts created after this date. Accepts dates in any format acceptable for comparison of MySQL DATETIME column values.', 'constant-contact-woocommerce' ),
				'required'    => false,
				'type'        => 'string',
			],
			'date_max' => [
				'description' => esc_html__( 'Filters results to only show abandoned checkouts created before this date. Accepts dates in any format acceptable for comparison of MySQL DATETIME column values.', 'constant-contact-woocommerce' ),
				'required'    => false,
				'type'        => 'string',
			],
		];
	}

	/**
	 * Get the Abandoned Checkout's schema for public consumption.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @return array
	 */
	public static function get_public_item_schema() {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'cc_woo_abandoned_checkout',
			'type'       => 'object',
			'properties' => [
				'checkout_id'           => [
					'description' => esc_html__( 'Database ID for the abandoned checkout.', 'constant-contact-woocommerce' ),
					'type'        => 'integer',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'user_id'               => [
					'description' => esc_html__( 'WordPress user ID of the user the checkout belongs to; defaults to 0 if a guest or non-logged-in user.', 'constant-contact-woocommerce' ),
					'type'        => 'integer',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'user_email'            => [
					'description' => esc_html__( 'The billing email the user entered at checkout before abandoning it. Note that this may be different than the email address the user has in their WordPress user profile.', 'constant-contact-woocommerce' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'checkout_contents'     => [
					'description' => esc_html__( 'Object representation of the checkout that was abandoned, and its contents, coupon codes, and billing data.', 'constant-contact-woocommerce' ),
					'type'        => 'object',
					'context'     => [ 'view' ],
					'readonly'    => true,
					'properties'  => [
						'products' => [
							'description' => esc_html__( 'Key-value listing of products in the cart. Keys are unique WooCommerce-generated keys identifying the cart in the database; values are objects representing the items in the cart.', 'constant-contact-woocommerce' ),
							'type'        => 'array',
							'context'     => [ 'view' ],
							'readonly'    => true,
							'properties'  => self::get_products_properties(),
						],
						'coupons'  => [
							'description' => esc_html__( 'Array of coupon code strings used in the checkout.', 'constant-contact-woocommerce' ),
							'type'        => 'array',
							'context'     => [ 'view' ],
							'readonly'    => true,
						],
					],
				],
				'checkout_updated'      => [
					'description' => esc_html__( 'The MySQL-format datetime of when the checkout was last updated, in GMT+0 time zone.', 'constant-contact-woocommerce' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'checkout_updated_ts'   => [
					'description' => esc_html__( 'Unix timestamp of when the checkout was last updated, in GMT+0 time zone.', 'constant-contact-woocommerce' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'checkout_created'      => [
					'description' => esc_html__( 'The MySQL-format datetime of when the checkout was first created, in GMT+0 time zone.', 'constant-contact-woocommerce' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'checkout_created_ts'   => [
					'description' => esc_html__( 'Unix timestamp of when the checkout was first created, in GMT+0 time zone.', 'constant-contact-woocommerce' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'checkout_hash'         => [
					'description' => esc_html__( 'MD5 hash of checkout\'s user ID and email address.', 'constant-contact-woocommerce' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'cart_subtotal'         => [
					'description' => esc_html__( 'Cart subtotal.', 'constant-contact-woocommerce' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'cart_total'            => [
					'description' => esc_html__( 'Cart total.', 'constant-contact-woocommerce' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'cart_subtotal_tax'     => [
					'description' => esc_html__( 'Cart subtotal tax.', 'constant-contact-woocommerce' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'cart_total_tax'        => [
					'description' => esc_html__( 'Cart total tax.', 'constant-contact-woocommerce' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'checkout_recovery_url' => [
					'description' => esc_html__( 'Recovery URL that recreates the cart for checkout when visited.', 'constant-contact-woocommerce' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
			],
		];
	}

	/**
	 * Get properties for individual Products definition in Schema.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @return array
	 */
	public static function get_products_properties() {
		return [
			'key'               => [
				'description' => esc_html__( 'Unique WooCommerce-generated key identifying the cart in the database. This differs from the parent-level cart_hash property.', 'constant-contact-woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'product_id'        => [
				'description' => esc_html__( 'The WooCommerce product ID.', 'constant-contact-woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'variation_id'      => [
				'description' => esc_html__( 'The WooCommerce product variation ID, if applicable.', 'constant-contact-woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'variation'         => [
				'description' => esc_html__( 'Object representation of any applicable variations, where keys are variation names and values are the actual variation selection.', 'constant-contact-woocommerce' ),
				'type'        => 'object',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'quantity'          => [
				'description' => esc_html__( 'Item quantity.', 'constant-contact-woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'data_hash'         => [
				'description' => esc_html__( 'MD5 hash of cart items to determine if contents are modified.', 'constant-contact-woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'line_tax_data'     => [
				'description' => esc_html__( 'Line subtotal tax and total tax data.', 'constant-contact-woocommerce' ),
				'type'        => 'object',
				'context'     => [ 'view' ],
				'readonly'    => true,
				'properties'  => [
					'subtotal' => [
						'description' => esc_html__( 'Line subtotal tax data.', 'constant-contact-woocommerce' ),
						'type'        => 'string',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
					'total'    => [
						'description' => esc_html__( 'Line total tax data.', 'constant-contact-woocommerce' ),
						'type'        => 'string',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
				],
			],
			'line_subtotal'     => [
				'description' => esc_html__( 'Line subtotal.', 'constant-contact-woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'line_subtotal_tax' => [
				'description' => esc_html__( 'Line subtotal tax.', 'constant-contact-woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'line_total'        => [
				'description' => esc_html__( 'Line total.', 'constant-contact-woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'line_tax'          => [
				'description' => esc_html__( 'Line total tax.', 'constant-contact-woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'data'              => [
				'description' => esc_html__( 'Misc. product data in key-value pairs.', 'constant-contact-woocommerce' ),
				'type'        => 'object',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'product_title'     => [
				'description' => esc_html__( 'The product title.', 'constant-contact-woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'product_sku'       => [
				'description' => esc_html__( 'The product SKU.', 'constant-contact-woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'product_permalink' => [
				'description' => esc_html__( 'Permalink to the product page.', 'constant-contact-woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'product_image_url' => [
				'description' => esc_html__( 'URL to the full-size featured image for the product if one exists.', 'constant-contact-woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
		];
	}

}
