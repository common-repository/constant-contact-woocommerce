<?php
/**
 * Adds a the Constant Contact menu item to the WooCommerce menu.
 *
 * @since 2019-04-16
 * @package ccforwoo-view-admin
 */

namespace WebDevStudios\CCForWoo\View\Admin;

use WebDevStudios\OopsWP\Structure\Service;

/**
 * MenuItem Class
 *
 * @since 2019-04-16
 * @version 2.0.0
 */
class MenuItem extends Service {
	/**
	 * Register WP hooks.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 */
	public function register_hooks() {
		add_action( 'admin_menu', [ $this, 'add_cc_woo_admin_submenu' ], 100 );
		add_action( 'admin_menu', [ $this, 'add_cc_woo_admin_menu' ], 100 );
		add_action( 'admin_init', [ $this, 'save_settings' ], 100 );
	}

	/**
	 * Add admin menu of CTCT Woo.
	 *
	 * @since 0.0.1
	 * @author Zach Owen <zach@webdevstudios>
	 */
	public function add_cc_woo_admin_menu() {
		add_menu_page(
			__( 'Constant Contact', 'constant-contact-woocommerce' ),
			__( 'Constant Contact', 'constant-contact-woocommerce' ),
			'manage_options',
			'ctct-woo-settings',
			[$this, 'cctct_standalone_settings_page_contents'],
			'dashicons-email',
			56
		);
	}

	/**
	 * Display settings page.
	 *
	 * @return void
	 * @author Biplav Subedi <biplav.subedi@webdevstudios.com>
	 * @since  2022-06-30
	 */
	public function cctct_standalone_settings_page_contents() {

		echo '<div class="wrap cc-wrap woocommerce"><form method="post" id="mainform" action="" enctype="multipart/form-data">';
			\WC_Admin_Settings::get_settings_pages();
			$woo = new \WebDevStudios\CCForWoo\View\Admin\WooTab();
			woocommerce_admin_fields( $woo->get_welcome_screen() );
			$woo->override_save_button();
		echo '</form></div>';
	}

	/**
	 * Save settings.
	 *
	 * @return void
	 * @author Biplav Subedi <biplav.subedi@webdevstudios.com>
	 * @since  2022-06-30
	 */
	public function save_settings() {
		if( ! isset( $_GET['page'] ) || 'ctct-woo-settings' !== $_GET['page'] ) {
			return;
		}

		if ( isset( $_POST['save'] ) && ( in_array( $_POST['save'], ['cc-woo-connect', 'cc-woo-save' ]) ) )  {
			\WC_Admin_Settings::get_settings_pages();
			$woo = new \WebDevStudios\CCForWoo\View\Admin\WooTab();
			$woo->save();
		}
	}


	/**
	 * Add the CC Woo Submenu Item.
	 *
	 * @since 0.0.1
	 * @author Zach Owen <zach@webdevstudios>
	 */
	public function add_cc_woo_admin_submenu() {
		add_submenu_page(
			'woocommerce',
			esc_html__( 'Constant Contact', 'constant-contact-woocommerce' ),
			esc_html__( 'Constant Contact', 'constant-contact-woocommerce' ),
			'manage_woocommerce',
			'cc-woo-settings',
			[ $this, 'redirect_to_cc_woo' ]
		);
	}

	/**
	 * Redirect the user to the CC-Woo options page.
	 *
	 * @since 0.0.1
	 * @author Zach Owen <zach@webdevstudios>
	 */
	public function redirect_to_cc_woo() {
		wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=cc_woo' ) );
		exit;
	}
}
