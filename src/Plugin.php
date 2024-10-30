<?php
/**
 * Constant Contact + WooCommerce
 *
 * @since 1.0.0
 * @author WebDevStudios <https://www.webdevstudios.com/>
 * @package cc-woo
 */

namespace WebDevStudios\CCForWoo;

use Exception;

use WebDevStudios\CCForWoo\Utility\CheckoutBlockNewsletter;
use WebDevStudios\CCForWoo\Utility\HealthPanel;
use WebDevStudios\CCForWoo\Utility\PluginCompatibilityCheck;
use WebDevStudios\CCForWoo\Utility\AdminNotifications;
use WebDevStudios\OopsWP\Structure\ServiceRegistrar;
use WebDevStudios\CCForWoo\View\ViewRegistrar;
use WebDevStudios\CCForWoo\View\Admin\Notice;
use WebDevStudios\CCForWoo\View\Admin\NoticeMessage;
use WebDevStudios\CCForWoo\Meta\ConnectionStatus;
use WebDevStudios\CCForWoo\Api\KeyManager;
use WebDevStudios\CCForWoo\WebHook\Disconnect;
use WebDevStudios\CCForWoo\View\Admin\MenuItem;
use WebDevStudios\CCForWoo\View\Admin\Disconnect as DisconnectSettings;
use WebDevStudios\CCForWoo\AbandonedCheckouts\CheckoutHandler;
use WebDevStudios\CCForWoo\AbandonedCheckouts\CheckoutsTable;
use WebDevStudios\CCForWoo\AbandonedCheckouts\CheckoutRecovery;
use WebDevStudios\CCForWoo\Rest\Registrar as RestRegistrar;

/**
 * "Core" plugin class.
 *
 * @since 1.0.0
 */
final class Plugin extends ServiceRegistrar {

	/**
	 * The plugin name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const PLUGIN_NAME = 'Constant Contact + WooCommerce';

	/**
	 * The plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const PLUGIN_VERSION = '2.3.1';

	/**
	 * Whether the plugin is currently active.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private $is_active = false;

	/**
	 * The plugin file path, should be __FILE__ of the main entry point script.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Services to register.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $services = [
		ViewRegistrar::class,
		KeyManager::class,
		Disconnect::class,
		MenuItem::class,
		CheckoutHandler::class,
		CheckoutsTable::class,
		CheckoutRecovery::class,
		RestRegistrar::class,
		DisconnectSettings::class,
	];

	/**
	 * Setup the instance of this class.
	 *
	 * Prepare some things for later.
	 *
	 * @since 1.0.0
	 * @author Zach Owen <zach@webdevstudios.com>
	 * @param string $plugin_file The plugin file path of the entry script.
	 * @package cc-woo
	 */
	public function __construct( string $plugin_file ) {
		$this->plugin_file = $plugin_file;
		add_action( 'admin_notices', [ $this, 'add_ssl_notice' ] );
	}

	/**
	 * Returns a notice if SSL is not active.
	 *
	 * @since 2.0.0
	 * @author Biplav Subedi <biplav.subedi@webdevstudios.com>
	 */
	public function add_ssl_notice() {
		$connected = get_option( 'cc_woo_import_connection_established' );

		if ( ! $connected && ( isset( $_SERVER['HTTPS'] ) && 'on' !== $_SERVER['HTTPS'] ) ) {
			$message = __( 'Your site does not appear to be using a secure connection (SSL). You might face issues when connecting to your account. Please add HTTPS to your site to make sure you have no issues connecting.', 'constant-contact-woocommerce' );
			new Notice(
				new NoticeMessage( $message, 'error', true )
			);
		}
	}

	/**
	 * Deactivate this plugin.
	 *
	 * @since 1.0.0
	 * @author Zach Owen <zach@webdevstudios.com>
	 * @param string $reason The reason for deactivating.
	 * @throws Exception If the plugin isn't active, throw an Exception.
	 */
	private function deactivate( $reason ) {
		unset( $_GET['activate'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Ok use of $_GET.

		if ( ! $this->is_active() ) {
			throw new Exception( $reason );
		}

		/**
		 * Fires when plugin is deactivated.
		 *
		 * @author Zach Owen <zach@webdevstudios>
		 * @since  1.3.2
		 *
		 * @param  string $message Deactivation message.
		 */
		do_action( 'cc_woo_disconnect', esc_html__( 'Plugin deactivated.', 'constant-contact-woocommerce' ) );

		$this->do_deactivation_process();

		new Notice(
			new NoticeMessage( $reason, 'error', true )
		);

		Notice::set_notices();

		add_action( 'admin_notices', [ '\WebDevStudios\CCForWoo\View\Admin\Notice', 'maybe_display_notices' ] );

		deactivate_plugins( $this->plugin_file );
	}

	/**
	 * Maybe deactivate the plugin if certain conditions aren't met.
	 *
	 * @since 1.0.0
	 * @author Zach Owen <zach@webdevstudios.com>
	 * @throws Exception When WooCommerce is not found or compatible.
	 */
	public function check_for_required_dependencies() {
		try {
			$compatibility_checker = new PluginCompatibilityCheck( '\\WooCommerce' );

			// Ensure requirements.
			if ( ! $compatibility_checker->is_available() ) {
				// translators: placeholder is the minimum supported WooCommerce version.
				$message = sprintf( esc_html__( 'WooCommerce version "%1$s" or greater must be installed and activated to use %2$s.', 'constant-contact-woocommerce' ), PluginCompatibilityCheck::MINIMUM_WOO_VERSION, self::PLUGIN_NAME );
				throw new Exception( $message );
			}

			if ( ! $compatibility_checker->is_compatible( \WooCommerce::instance() ) ) {
				// translators: placeholder is the minimum supported WooCommerce version.
				$message = sprintf( esc_html__( 'WooCommerce version "%1$s" or greater is required to use %2$s.', 'constant-contact-woocommerce' ), PluginCompatibilityCheck::MINIMUM_WOO_VERSION, self::PLUGIN_NAME );
				throw new Exception( $message );
			}
		} catch ( Exception $e ) {
			$this->deactivate( $e->getMessage() );
		}


	}

	/**
	 * Run things once the plugin instance is ready.
	 *
	 * @since 1.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 */
	public function run() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->is_active = is_plugin_active( plugin_basename( $this->plugin_file ) );
		$this->register_hooks();

		parent::run();
	}

	/**
	 * Register the plugin's hooks with WordPress.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since  1.0.0
	 */
	public function register_hooks() {
		add_action( 'plugins_loaded', [ $this, 'check_for_required_dependencies' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ], 99 );
		add_action( 'init', [ $this, 'load_plugin_textdomain' ] );
		add_action( 'init', [ $this, 'load_health_panel' ] );
		add_action( 'woocommerce_init', [ $this, 'load_checkout_block_newsletter' ] );
		add_action( 'init', [ $this, 'load_admin_notifications' ] );

		register_activation_hook( $this->plugin_file, [ $this, 'do_activation_process' ] );
		register_deactivation_hook( $this->plugin_file, [ $this, 'do_deactivation_process' ] );
	}

	/**
	 * Returns whether the plugin is active.
	 *
	 * @since 1.0.0
	 * @author Zach Owen Zach Owen <zach@webdevstudios>
	 * @return bool
	 */
	public function is_active() : bool {
		return $this->is_active;
	}

	/**
	 * Get the plugin file path.
	 *
	 * @since 1.0.0
	 * @author Zach Owen Zach Owen <zach@webdevstudios>
	 * @return string
	 */
	public function get_plugin_file() : string {
		return $this->plugin_file;
	}

	/**
	 * Activate WooCommerce along with Constant Contact + WooCommerce if it's present and not already active.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since  2019-03-18
	 */
	private function maybe_activate_woocommerce() {
		$woocommerce = 'woocommerce/woocommerce.php';

		if ( ! is_plugin_active( $woocommerce ) && in_array( $woocommerce, array_keys( get_plugins() ), true ) ) {
			activate_plugin( $woocommerce );
		}
	}

	/**
	 * Callback for register_activation_hook.
	 *
	 * Performs the plugin's activation routines.
	 *
	 * @see register_activation_hook()
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since  2019-03-18
	 */
	public function do_activation_process() {
		$this->maybe_activate_woocommerce();

		$this->create_abandoned_checkouts_table();
		$this->create_abandoned_checkouts_expiration_check();

		flush_rewrite_rules();
	}

	/**
	 * Creates the database table for Abandoned Checkouts.
	 *
	 * @author Rebekah Van Epps <rebekah.vanepps@webdevstudios.com>
	 * @since 2019-10-24
	 */
	private function create_abandoned_checkouts_table() {
		( new CheckoutsTable() )->create_table();
	}

	/**
	 * Schedules the daily check for abandoned checkouts that have sat in the DB longer than 30 days (by default...).
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-24
	 */
	private function create_abandoned_checkouts_expiration_check() {
		if ( ! wp_next_scheduled( 'cc_woo_check_expired_checkouts' ) ) {
			wp_schedule_event( strtotime( 'today' ), 'daily', 'cc_woo_check_expired_checkouts' );
		}
	}

	/**
	 * Removes the scheduled daily check for expired abandoned checkouts.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 2019-10-24
	 */
	private function clear_abandoned_checkouts_expiration_check() {
		wp_clear_scheduled_hook( 'cc_woo_check_expired_checkouts' );
	}

	/**
	 * Callback for register_deactivation_hook.
	 *
	 * Performs the plugin's deactivation routines, including notifying Constant Contact of disconnection.
	 *
	 * @see register_deactivation_hook()
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since  2019-03-18
	 * @return void
	 */
	public function do_deactivation_process() {

		/**
		 * Fires when plugin is deactivated.
		 *
		 * @author Zach Owen <zach@webdevstudios>
		 * @since  1.3.2
		 */
		do_action( 'wc_ctct_disconnect' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intentional improperly-prefixed hookname, used in webhooks.

		$this->clear_abandoned_checkouts_expiration_check();

		delete_option( CheckoutsTable::DB_VERSION_OPTION_NAME );
		delete_option( ConnectionStatus::CC_CONNECTION_USER_ID );
		delete_option( ConnectionStatus::CC_FIRST_CONNECTION );
		delete_option( ConnectionStatus::CC_CONNECTION_ESTABLISHED_KEY );


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
		delete_option( 'cc-woo-is-reviewed' );
		delete_option( 'cc-woo-review-dismissed-count' );

	}

	/**
	 * Registers public scripts.
	 *
	 * @author George Gecewicz <george.gecewicz@webdevstudios.com>
	 * @since 1.2.0
	 */
	public function register_scripts() {
		wp_register_script( 'cc-woo-public', trailingslashit( plugin_dir_url( $this->get_plugin_file() ) ) . 'app/bundle.js', [ 'wp-util' ], self::PLUGIN_VERSION, false );
	}

	/**
	 * Load back-end scripts.
	 *
	 * @author Biplav Subedi <biplav.subedi@webdevstudios.com>
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'cc-woo-admin', trailingslashit( plugin_dir_url( $this->get_plugin_file() ) ) . 'app/admin-bundle.js', [ 'wp-util' ], self::PLUGIN_VERSION, false );
		wp_enqueue_style( 'cc-woo-admin', trailingslashit( plugin_dir_url( $this->get_plugin_file() ) ) . 'app/admin.css' );
    	wp_enqueue_style( 'cc-woo-google-fonts', 'https://fonts.googleapis.com/css2?family=Maven+Pro:wght@400;700&display=swap', false );
		wp_localize_script( 'cc-woo-admin', 'cc_woo_ajax', [ 'ajax_url' => admin_url( 'admin-ajax.php' ) ] );
	}

	/**
	 * Load textdomain.
	 *
	 * @author Michael Beckwith <michael@webdevstudios.com>
	 * @since 2.1.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'constant-contact-woocommerce' );
	}

	/**
	 * Load health panel.
	 *
	 * @author Michael Beckwith <michael@webdevstudios.com>
	 * @since 2.2.0
	 */
	public function load_health_panel() {
		new HealthPanel();
	}

	/**
	 * Register our block newsletter checkbox.
	 *
	 * @since 2.3.0
	 * @author Michael Beckwith <michael@webdevstudios.com>
	 */
	public function load_checkout_block_newsletter() {
		/* We are running this here because adding into `NewsletterPreferenceCheckbox` class is running too late. That class has things run on `init` hook and we need to run earlier on `after_setup_theme`.
		*/
		$checkoutBlockNewsletter = new CheckoutBlockNewsletter();
		$checkoutBlockNewsletter->add_newsletter_to_checkout_block();
		$checkoutBlockNewsletter->register_hooks();
  }

  /**
	 * Load admin notifications.
	 *
	 * @author Michael Beckwith <michael@webdevstudios.com>
	 * @since 2.3.0
	 */
	public function load_admin_notifications() {
		$notifications = new AdminNotifications();
		$notifications->register_hooks();
	}
}

