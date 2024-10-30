<?php
// Handles the plugin disconnection.

namespace WebDevStudios\CCForWoo\View\Admin;
use WebDevStudios\CCForWoo\Utility\DebugLogging;
use WebDevStudios\OopsWP\Structure\Service;
use WebDevStudios\CCForWoo\Meta\ConnectionStatus;
use WebDevStudios\CCForWoo\AbandonedCheckouts\CheckoutsTable;
/**
 * Disconnects the plugin from Constant Contact WOO.
 *
 * @since 2.0.0
 * @return void
 */
class Disconnect extends Service {

    /**
    * Constructor.
    *
    * @since 2.0.0
    * @return void
    */
    public function register_hooks() {
        add_action( 'admin_init', array( $this, 'disconnect' ) );
    }

    /**
    * Disconnects the plugin from Constant Contact WOO.
    *
    * @since 2.0.0
    * @return void
    */
    public function disconnect() {
        if ( ! isset( $_GET['cc-connect'] ) || 'disconnect' !== $_GET['cc-connect'] ) {
            return;
        }

	    $ctct_logger = new DebugLogging(
		    wc_get_logger(),
		    'CTCT Woo: Plugin disconnected from Constant Contact',
		    'info'
	    );
	    $ctct_logger->log();

        $this->disconnect_plugin();
        $this->redirect();
    }

    /**
    * Disconnects the plugin from Constant Contact WOO.
    *
    * @since 2.0.0
    * @return void
    */
    public function disconnect_plugin() {

        /**
		 * Fires when plugin is deactivated.
		 *
		 * @author Zach Owen <zach@webdevstudios>
		 * @since  1.3.2
		 */
		do_action( 'wc_ctct_disconnect' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intentional improperly-prefixed hookname, used in webhooks.

        wp_clear_scheduled_hook( 'cc_woo_check_expired_checkouts' );


		delete_option( CheckoutsTable::DB_VERSION_OPTION_NAME );
		delete_option( ConnectionStatus::CC_CONNECTION_USER_ID );
		delete_option( ConnectionStatus::CC_FIRST_CONNECTION );
		delete_option( ConnectionStatus::CC_CONNECTION_ESTABLISHED_KEY );
		delete_option( ConnectionStatus::CC_CONNECTED_TIME );


		// WooCommerce Options
		delete_option( 'cc_woo_store_information_first_name' );
		delete_option( 'cc_woo_store_information_last_name' );
		delete_option( 'cc_woo_store_information_phone_number' );
		delete_option( 'cc_woo_store_information_store_name' );
		delete_option( 'cc_woo_store_information_currency' );
		delete_option( 'cc_woo_store_information_currency' );
		delete_option( 'cc_woo_store_information_contact_email');
		delete_option( 'cc_woo_store_information_alt_login_url' );
		delete_option( 'constant_contact_for_woo_has_setup' );
		delete_option( 'cc_woo_customer_data_allow_import' );
    }

    /**
    * Redirects to the admin page.
    *
    * @since 2.0.0
    * @return void
    */
    public function redirect() {
        $url = admin_url( 'admin.php?page=' . esc_attr( $_GET['page'] ) );
        $url = add_query_arg([
            'tab'  => 'wc-settings' === $_GET['page'] ? 'cc_woo' : '',
            'cc-connect' => '',
        ], $url );
        wp_redirect( $url );
        exit;
    }
}
