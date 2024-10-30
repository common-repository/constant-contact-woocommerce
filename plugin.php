<?php
/**
 * Constant Contact + WooCommerce
 * @since 2019-02-15
 * @author Constant Contact <https://www.constantcontact.com/>
 * @package cc-woo
 * @wordpress-plugin
 * Plugin Name: Constant Contact + WooCommerce
 * Description: Add products to your emails and sync your contacts.
 * Plugin URI: https://github.com/WebDevStudios/constant-contact-woocommerce
 * Version: 2.3.1
 * Author: Constant Contact
 * Author URI: https://www.constantcontact.com/
 * Text Domain: constant-contact-woocommerce
 * WC requires at least: 3.6.0
 * WC tested up to: 9.0.1
 * Requires PHP: 7.2
 * License: GPL-3.0+
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 */

// Autoload things.
$cc_woo_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';

if ( ! is_readable( $cc_woo_autoloader ) ) {
	/* Translators: Placeholder is the current directory. */
	throw new \Exception( sprintf( __( 'Please run `composer install` in the plugin folder "%s" and try activating this plugin again.', 'constant-contact-woocommerce' ), dirname( __FILE__ ) ) );
}

require_once $cc_woo_autoloader;

$cc_woo_plugin = new \WebDevStudios\CCForWoo\Plugin( __FILE__ );
$cc_woo_plugin->run();


// Declare compatibility with custom order tables for WooCommerce.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
