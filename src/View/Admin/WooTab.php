<?php
/**
 * Constant Contact WooCommerce Settings Tab
 *
 * @since 2.0.0
 * @author  Zach Owen <zach@webdevstudios>, Jeremy Ward <jeremy.ward@webdevstudios.com>
 * @package cc-woo
 */

namespace WebDevStudios\CCForWoo\View\Admin;

use WebDevStudios\CCForWoo\Meta\ConnectionStatus;
use WebDevStudios\CCForWoo\Settings\SettingsModel;
use WebDevStudios\CCForWoo\Settings\SettingsValidator;
use WebDevStudios\CCForWoo\Utility\NonceVerification;
use WebDevStudios\CCForWoo\View\Checkout\NewsletterPreferenceCheckbox;
use WebDevStudios\OopsWP\Utility\Hookable;
use WC_Settings_Page;

/**
 * Class WooTab
 *
 * @author  Jeremy Ward <jeremy.ward@webdevstudios.com>
 * @package WebDevStudios\CCForWoo\View\Admin
 * @since 2.0.0
 */
class WooTab extends WC_Settings_Page implements Hookable {
	use NonceVerification;

	/**
	 * Store owner first name field.
	 *
	 * @since 2.0.0
	 */
	const FIRST_NAME_FIELD = 'cc_woo_store_information_first_name';

	/**
	 * Store owner last name field.
	 *
	 * @since 2.0.0
	 */
	const LAST_NAME_FIELD = 'cc_woo_store_information_last_name';

	/**
	 * Store phone number field.
	 *
	 * @since 2.0.0
	 */
	const PHONE_NUMBER_FIELD = 'cc_woo_store_information_phone_number';

	/**
	 * Store name field.
	 *
	 * @since 2.0.0
	 */
	const STORE_NAME_FIELD = 'cc_woo_store_information_store_name';

	/**
	 * Store currency field.
	 *
	 * @since 2.0.0
	 */
	const CURRENCY_FIELD = 'cc_woo_store_information_currency';

	/**
	 * Is store details is enabled.
	 *
	 * @since 2.0.0
	 */
	const SAVE_STORE_DETAILS = 'cc_woo_save_store_details';

	/**
	 * Store country code field.
	 *
	 * @since 2.0.0
	 */
	const COUNTRY_CODE_FIELD = 'cc_woo_store_information_country_code';

	/**
	 * Store contact e-mail field.
	 *
	 * @since 2.0.0
	 */
	const EMAIL_FIELD = 'cc_woo_store_information_contact_email';

	/**
	 * Store checkbox location.
	 *
	 * @since 2.0.0
	 */
	const CHECKBOX_LOCATION = 'cc_woo_store_information_checkbox_location';

	/**
	 * Settings section ID.
	 *
	 * @var string
	 * @since 2.0.0
	 */
	protected $id = 'cc_woo';

	/**
	 * Settings Section label.
	 *
	 * @var string
	 * @since 2.0.0
	 */
	protected $label = '';

	/**
	 * Array of form errors to display with their fields.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private $errors = [];

	/**
	 * Instance of the ConnectionStatus object.
	 *
	 * @var ConnectionStatus
	 * @since 2.0.0
	 */
	private $connection;

	/**
	 * Is the current request a REST API request?
	 *
	 * @since 2.0.0
	 * @var bool
	 */
	private $is_rest = false;

	/**
	 * The identifier for the Importing Existing Customers section.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private $import_existing_customer_section = 'customer_data_import';

	/**
	 * WooTab constructor.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 */
	public function __construct() {
		$this->label        = esc_html__( 'Constant Contact', 'constant-contact-woocommerce' );
		$this->nonce_name   = '_cc_woo_nonce';
		$this->nonce_action = 'cc-woo-connect-action';
		$this->connection   = new ConnectionStatus();
		$this->is_rest      = defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	/**
	 * Register hooks into WooCommerce
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 */
	public function register_hooks() {
		add_filter( 'woocommerce_settings_tabs_array', [ $this, 'add_settings_page' ], 99 );
		add_action( "woocommerce_settings_cc_woo_store_information_settings_data", [ $this, 'add_optional_fields_wrapper' ] );
		add_action( "woocommerce_settings_cc_woo_store_information_settings_data_end", [ $this, 'add_optional_fields_wrapper_end' ] );

		add_action( "woocommerce_settings_{$this->id}", [ $this, 'output' ] );

		// Output settings sections.
		add_action( "woocommerce_sections_{$this->id}", [ $this, 'output_sections' ] );

		// CC API interactions.
		add_action( "woocommerce_sections_{$this->id}", [ $this, 'maybe_redirect_to_cc' ] );
		add_action( "woocommerce_sections_{$this->id}", [ $this, 'maybe_update_connection_status' ], 1 );

		// REST API.
		add_filter( 'woocommerce_settings_groups', [ $this, 'add_rest_group' ] );
		add_filter( "woocommerce_settings-{$this->id}", [ $this, 'add_rest_fields' ] );

		// Form.
		add_filter( 'pre_option_' . self::CURRENCY_FIELD, 'get_woocommerce_currency' );
		add_filter( 'pre_option_' . self::COUNTRY_CODE_FIELD, [ $this, 'get_woo_country' ] );
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . self::PHONE_NUMBER_FIELD, [ $this, 'sanitize_phone_number' ] );
		add_filter( "woocommerce_get_settings_{$this->id}", [ $this, 'maybe_add_connection_button' ] );

		// Save actions.
		add_filter( 'woocommerce_settings_start', [ $this, 'validate_option_values' ], 10, 3 );
		add_action( "woocommerce_settings_save_{$this->id}", [ $this, 'save' ] );
		add_action( "woocommerce_settings_save_{$this->id}", [ $this, 'update_setup_option' ] );

		//hide default button
		add_action( "admin_head", [ $this, 'hide_default_save_button' ] );

	}

	/**
	 * Add the settings sections.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 * @return array
	 */
	public function get_sections() {
		$sections = [
			''  => esc_html__( 'Store Information', 'constant-contact-woocommerce' ),
		];

		/* This filter is documented in WooCommerce */
		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third-party hook usage.
	}
	/**
	 * Add a custom wrapper for fields.
	 *
	 * @since 2.0.0
	 * @author Biplav Subedi <biplav.subedi@webdevstudios>
	 * @return string
	 */
	public function add_optional_fields_wrapper() {
		echo "<tbody id='cc-optional-fields'>";
	}

	/**
	 * Add a custom wrapper for fields end.
	 *
	 * @since 2.0.0
	 * @author Biplav Subedi <biplav.subedi@webdevstudios>
	 * @return string
	 */
	public function add_optional_fields_wrapper_end() {
		echo "</tbody>";
	}

	/**
	 * Get the settings for the settings tab.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 * @return array
	 */
	public function get_settings() {
		// @TODO this should be able to be removed.
		if ( $this->is_rest ) {
			$settings = $this->get_rest_settings_options();

			if ( ! $this->connection->is_connected() ) {
				$settings = array_merge( $settings, $this->get_connection_attempted_options() );
			}

			return $this->get_filtered_settings( $settings );
		}

		if ( ! $this->connection->connection_was_attempted() ) {
			return $this->get_filtered_settings( $this->get_default_settings_options() );
		}

		if ( ! $this->connection->is_connected() ) {
			return $this->get_filtered_settings(
				array_merge( $this->get_connection_attempted_options(), $this->get_default_settings_options() )
			);
		}

		return $this->get_filtered_settings(
			$this->get_default_settings_options()
		);
	}

	/**
	 * Run the settings for the current connection status through the WooCommerce settings filter.
	 *
	 * @param array $settings Settings options.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since 2.0.0
	 * @return array
	 */
	private function get_filtered_settings( array $settings ) {

		/* This filter is documented in WooCommerce */
		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $GLOBALS['current_section'] ?? '' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third-party hook usage.
	}

	/**
	 * Get the default view for our settings page.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since 2.0.0
	 * @return array
	 */
	private function get_default_settings_options() {
		$settings = [];

		switch ( $GLOBALS['current_section'] ?? '' ) {
			case '':
			default:
				$settings = $this->get_welcome_screen();
				break;
		}

		$settings = $this->process_errors( $settings );
		$settings = $this->adjust_styles( $settings );

		return $settings;
	}

	/**
	 * Add our settings group to the REST API.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 *
	 * @param array $groups The array of groups being sent to the API.
	 *
	 * @return array
	 */
	public function add_rest_group( $groups ) {
		$groups[] = [
			'id'          => 'cc_woo',
			'label'       => esc_html__( 'Constant Contact WooCommerce', 'constant-contact-woocommerce' ),
			'description' => esc_html__( 'This endpoint provides information for the Constant Contact for WooCommerce plugin.', 'constant-contact-woocommerce' ),
		];

		return $groups;
	}

	/**
	 * Add fields to the REST API for our settings.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 *
	 * @param array $settings The array of settings going to the API.
	 *
	 * @return array
	 */
	public function add_rest_fields( $settings ) {
		$fields       = [];
		$section_keys = array_keys( $this->get_sections() );

		foreach ( $section_keys as $section_id ) {
			$fields = array_merge( $fields, $this->get_settings( $section_id ) );
		}

		foreach ( $fields as $field ) {
			$field['option_key'] = $field['option_key'] ?? $field['id'] ?? '';
			$settings[]          = $field;
		}

		return $settings;
	}

	/**
	 * Get the section options for an attempted connection that failed.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since 2.0.0
	 * @return array
	 */
	private function get_connection_attempted_options() {
		return [
			[
				'title' => '',
				'desc'  => '<h2 style="color:red;margin-top:0;">' . esc_html__( 'There was a problem connecting your store to Constant Contact. Please try again.', 'constant-contact-woocommerce' ) . '</h2>',
				'type'  => 'title',
				'id'    => 'cc_woo_connection_attempted_heading',
			],
		];
	}

	/**
	 * Get the settings for the main section if already connected to Constant Contact.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since 2.0.0
	 * @return array
	 */
	private function get_connection_established_options() {
		return [
			[
				'title' => esc_html__( 'Congratulations! Your store is connected to Constant Contact.', 'constant-contact-woocommerce' ),
				'type'  => 'title',
				'id'    => 'cc_woo_connection_established_heading',
			],
			[
				'type' => 'cc_cta_button',
			],
			[
				'type' => 'sectionend',
				'id'   => 'cc_woo_store_information_settings',
			],
		];
	}

	/**
	 * Render the call-to-action button in the admin.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since 2.0.0
	 */
	public function render_cta_button() {
		$url = 'https://login.constantcontact.com/login/?goto=https%3A%2F%2Fapp.constantcontact.com%2Fpages%2Fecomm-dash%2Fdashboard%2F%23%2Fwoocommerce';
		?>
		<a
			class="button button-primary"
			target="_blank"
			href="<?php echo esc_url( $url ); ?>"
		>
			<?php esc_html_e( 'Constant Contact Dashboard', 'constant-contact-woocommerce' ); ?>
		</a>
		<?php
	}

	public function connect_title() {

		return [
			[
				'title' => '',
				'type'  => 'title',
				'id'    => 'cc_woo_store_marketing_title_settings',
				'desc'  => ''
			],
			[
				'title' => esc_html__( 'Import your contacts', 'constant-contact-woocommerce' ),
				'id'    => 'cc_woo_customer_data_settings',
				'type'  => 'title',
				'desc'  => wp_kses(
					sprintf(
						__( "Start marketing to your customers right away by importing all your contacts now.\n\nDo you want to import your current contacts? By selecting yes below, you agree you have permission to market to your current contacts.", 'constant-contact-woocommerce' ),
						esc_url( 'https://www.constantcontact.com/legal/anti-spam' )
					),
					[
						'a' => [
							'href' => [],
							'target' => [],
						],
					]
				)
			],
		];
	}
	/**
	 * Gets the settings for the Store Information section.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 * @return array
	 */
	private function get_store_information_settings() {
		$readonly_from_general_settings = esc_html__( 'This field is read from your General settings.', 'constant-contact-woocommerce' );
		$historical_import_field        = new \WebDevStudios\CCForWoo\View\Admin\Field\ImportHistoricalData();
		$connected                      = get_option( ConnectionStatus::CC_CONNECTION_ESTABLISHED_KEY );
		$title                          = $connected ? __( 'Connected to Constant Contact', 'constant-contact-woocommerce' )  : __( 'Connect to Constant Contact', 'constant-contact-woocommerce' );
		$desc                           = $connected ? ''  : __( 'Enter this information in order to connect your Constant Contact account.', 'constant-contact-woocommerce' );

		$url = admin_url( 'admin.php?page=' . esc_attr( $_GET['page'] ) );
		$url = remove_query_arg( ['cc-connect'], $url );
		$url = add_query_arg(
			['tab' => 'wc-settings' === $_GET['page'] ? 'cc_woo' : ''],
			$url
		);

		return [

			[
				'title' => '',
				'type'  => 'title',
				'id'    => 'cc_woo_store_marketing_title_settings_go_back',
				'desc'  => '<a href="' . esc_url( $url ) . '" class="cc-woo-back"><span class="dashicons dashicons-arrow-left-alt2"></span>' . esc_html__( 'Go back', 'constant-contact-woocommerce' ) . '</span></a>',
			],

			[
				'title' => $title,
				'type'  => 'title',
				'id'    => 'cc_woo_store_marketing_title_settings',
				'desc'  => $desc
			],
			[
				'title' => esc_html__( 'Import your contacts', 'constant-contact-woocommerce' ),
				'id'    => 'cc_woo_customer_data_settings',
				'type'  => 'title',
				'desc'  => wp_kses(
					sprintf(
						__( "Start marketing to your customers right away by importing all your contacts now.\n\nDo you want to import your current contacts? By selecting yes below, you agree you have permission to market to your current contacts.", 'constant-contact-woocommerce' ),
						esc_url( 'https://www.constantcontact.com/legal/anti-spam' )
					),
					[
						'a' => [
							'href' => [],
							'target' => [],
						],
					]
				)
			],
			$historical_import_field->get_form_field(),
			[
				'type' => 'sectionend',
				'id'   => 'cc_woo_customer_data_settings',
			],
			[
				'title' => esc_html__( 'Marketing', 'constant-contact-woocommerce' ),
				'type'  => 'title',
				'id'    => 'cc_woo_store_marketing_title_settings',
			],
			[
				'title'   => '',
				'desc'    => esc_html__( 'At checkout, new customers must check a box if they want to receive marketing emails from you. Do you want this box checked by default?', 'constant-contact-woocommerce' ),
				'type'    => 'radio',
				'id'      => NewsletterPreferenceCheckbox::STORE_NEWSLETTER_DEFAULT_OPTION,
				'default' => 'false',
				'options' => [
					'false' => esc_html__( 'No - do not check this box by default', 'constant-contact-woocommerce' ),
					'true'  => esc_html__( 'Yes - check this box by default', 'constant-contact-woocommerce' ),
				],

			],
			[
				'title'   => '',
				'desc'    => esc_html__( 'Change filter location where checkbox is rendered.', 'constant-contact-woocommerce' ),
				'type'    => 'radio',
				'id'      => self::CHECKBOX_LOCATION,
				'default' => 'woocommerce_after_checkout_billing_form',
				'options' => [
					'woocommerce_after_checkout_billing_form' => esc_html__( 'After checkout billing form', 'constant-contact-woocommerce' ),
					'woocommerce_review_order_before_submit'  => esc_html__( 'Before order submit button', 'constant-contact-woocommerce' ),
				],
			],
			[
				'type' => 'sectionend',
				'id'   => 'cc_woo_store_marketing_ends',
			],
			[
				'title' => esc_html__( 'Store Information', 'constant-contact-woocommerce' ),
				'type'  => 'title',
				'id'    => 'cc_woo_store_information_settings',
			],
			[
				'title'             => esc_html__( 'Enter store information?', 'constant-contact-woocommerce' ),
				'desc'              => 'Yes',
				'id'                => self::SAVE_STORE_DETAILS,
				'type'              => 'checkbox',
			],
			[
				'type' => 'sectionend',
				'id'   => 'cc_woo_store_information_settings_save_end',
			],
			[
				'title' => '',
				'type'  => 'title',
				'id'    => 'cc_woo_store_information_settings_data',
			],
			[
				'title'             => esc_html__( 'First Name', 'constant-contact-woocommerce' ),
				'desc'              => '',
				'id'                => self::FIRST_NAME_FIELD,
				'type'              => 'text',
				'custom_attributes' => [
					'maxlength' => 255,
				],
			],
			[
				'title'             => esc_html__( 'Last Name', 'constant-contact-woocommerce' ),
				'desc'              => '',
				'id'                => self::LAST_NAME_FIELD,
				'type'              => 'text',
				'custom_attributes' => [
					'maxlength' => 255,
				],
			],
			[
				'title'             => esc_html__( 'Phone Number', 'constant-contact-woocommerce' ),
				'id'                => self::PHONE_NUMBER_FIELD,
				'desc'              => '',
				'type'              => 'text',
				'custom_attributes' => [
					'maxlength' => 255,
				],
			],
			[
				'title'             => esc_html__( 'Store Name', 'constant-contact-woocommerce' ),
				'id'                => self::STORE_NAME_FIELD,
				'desc'              => '',
				'type'              => 'text',
				'custom_attributes' => [
					'maxlength' => 255,
				],
			],
			[
				'title'             => esc_html__( 'Contact E-mail Address', 'constant-contact-woocommerce' ),
				'id'                => self::EMAIL_FIELD,
				'desc'              => '',
				'type'              => 'email',
				'custom_attributes' => [
					'maxlength' => 255,
				],
			],
			[
				'title'             => esc_html__( 'Currency', 'constant-contact-woocommerce' ),
				'id'                => self::CURRENCY_FIELD,
				'type'              => 'text',
				'custom_attributes' => [
					'readonly' => 'readonly',
					'size'     => 4,
				],
			],
			[
				'title'             => esc_html__( 'Country Code', 'constant-contact-woocommerce' ),
				'id'                => self::COUNTRY_CODE_FIELD,
				'type'              => 'text',
				'custom_attributes' => [
					'readonly' => 'readonly',
					'size'     => 4,
				],
			],
			[
				'type' => 'sectionend',
				'id'   => 'cc_woo_store_information_settings',
			],
		];
	}

	/**
	 * Show the welcome screen if it's not connected.
	 *
	 * @since 2.0.0
	 * @author Biplav Subedi <biplav.subedi@webdevstudios>
	 * @return array
	 */
	public function get_welcome_screen() {
		if( ! isset( $_GET['cc-connect'] ) && ! get_option( ConnectionStatus::CC_CONNECTION_ESTABLISHED_KEY ) ) {
			include_once dirname( __FILE__ ) . '/welcome.php';

			// Fallback.
			return [
				[
					'title' => '',
					'type'  => 'title',
					'id'    => 'cc_woo_store_welcome_fallback',
				]
			];
		} elseif ( ! isset( $_GET['cc-connect'] ) && get_option( ConnectionStatus::CC_CONNECTION_ESTABLISHED_KEY ) ) {
			include_once dirname( __FILE__ ) . '/connected.php';

			// Fallback.
			return [
				[
					'title' => '',
					'type'  => 'title',
					'id'    => 'cc_woo_store_connected_fallback',
				]
			];
		} else {
			return $this->get_store_information_settings();
		}
	}

	/**
	 * Displays the Constant Contact connection button when the form is validated and a connection is not already established.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 *
	 * @param array $settings The current settings array.
	 *
	 * @return array
	 */
	public function maybe_add_connection_button( $settings ) {
		if ( ! $this->meets_connect_requirements() || $this->connection->is_connected() ) {
			return $settings;
		}

		return array_merge( [ $this->get_connection_button() ], $settings );
	}

	/**
	* Add a go back button.
	*
	* @since 2.0.0
	* @author Biplav Subedi <biplav.subedi@webdevstudios>
	*/
	public function add_go_back_button() {
		if( isset( $_GET['cc-connect'] ) && 'connect' === esc_html( $_GET['cc-connect'] ) && ! get_option( ConnectionStatus::CC_CONNECTION_ESTABLISHED_KEY ) ) {
			$url = admin_url( 'admin.php?page=' . esc_attr( $_GET['page'] ) );
			$url = remove_query_arg( ['cc-connect'], $url );
			?><a href="<?php echo esc_url( $url ); ?>" class="cc-woo-back"> <span class="dashicons dashicons-arrow-left-alt2"></span>  <?php esc_html_e( "Go Back", 'constant-contact-woocommerce' ); ?> </a><?php

		}
	}

	/**
	 * Maybe redirects to Constant Contact to connect accounts.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since 2.0.0
	 * @return void
	 */
	public function maybe_redirect_to_cc() {
		if ( ! $this->requested_connect_to_cc() ) {
			return;
		}

		add_filter( 'allowed_redirect_hosts', [ $this, 'allow_redirect_to_cc' ] );

		wp_safe_redirect( 'https://shoppingcart.constantcontact.com/auth/woocommerce/WhoDis?storeDomain="' . get_home_url() . '"' );
		exit;
	}

	/**
	 * Check whether a connection request to CC has been triggered.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since 2.0.0
	 * @return bool
	 */
	private function requested_connect_to_cc() {

		if ( ! $this->has_valid_nonce() ) {
			return false;
		}

		// phpcs:disable -- Ignoring $_POST warnings.
		return (
			isset( $_POST['save'] )
			&& 'cc-woo-connect' === filter_var( $_POST['save'], FILTER_SANITIZE_FULL_SPECIAL_CHARS )
		);
		// phpcs:enable
	}

	/**
	 * Add the Constant Contact host to the list of allowed hosts.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since 2.0.0
	 * @return array
	 */
	public function allow_redirect_to_cc() {
		$hosts[] = 'shoppingcart.constantcontact.com';

		return $hosts;
	}

	/**
	 * Check to see if the settings meet the requirements to connect to CC.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 * @return bool
	 */
	private function meets_connect_requirements() {
		$model = new SettingsModel(
			get_option( self::FIRST_NAME_FIELD, '' ),
			get_option( self::LAST_NAME_FIELD, '' ),
			get_option( self::PHONE_NUMBER_FIELD, '' ),
			get_option( self::STORE_NAME_FIELD, '' ),
			get_option( self::CURRENCY_FIELD, '' ),
			get_option( self::COUNTRY_CODE_FIELD ),
			get_option( self::EMAIL_FIELD ),
			get_option( self::CHECKBOX_LOCATION, 'woocommerce_after_checkout_billing_form' )
		);

		$validator = new SettingsValidator( $model );

		return $validator->is_valid();
	}

	/**
	 * Listen for GET request that establishes connection.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since 2.0.0
	 * @return void
	 */
	public function maybe_update_connection_status() {
		$success = filter_input( INPUT_GET, 'success', FILTER_SANITIZE_NUMBER_INT );
		$user_id = filter_input( INPUT_GET, 'user_id', FILTER_SANITIZE_NUMBER_INT );

		if ( is_null( $success ) || is_null( $user_id ) ) {
			return;
		}

		$this->connection->set_connection( $success, $user_id );
	}

	/**
	 * Verify that all option values meet the minimum requirements.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 * @return void
	 */
	public function validate_option_values() {
		if ( ! get_option( 'constant_contact_for_woo_has_setup' ) ) {
			return;
		}

		$settings = $this->get_store_information_settings();

		foreach ( $settings as $field ) {
			$this->validate_value( $field );
		}
	}

	/**
	 * Validate a field's value is set, otherwise log an error.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 *
	 * @param array $field The field to validate.
	 *
	 * @return void
	 */
	private function validate_value( $field ) {
		if ( in_array( $field['type'], [ 'title', 'sectionend' ], true ) ) {
			return;
		}

		if ( ! empty( get_option( $field['id'] ) ) ) {
			return;
		}
		$is_required = isset( $field['custom_attributes']['required'] ) ?  (bool)$field['custom_attributes']['required'] : false;

		$this->errors[ $field['id'] ] = $is_required ? sprintf(
			/* Translators: Placeholder is the field's title. */
			esc_html__( 'The "%s" field is required to connect to Constant Contact.', 'constant-contact-woocommerce' ),
			$field['title'] ): '';
	}

	/**
	 * Sanitize incoming phone number.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 *
	 * @param mixed $value The incoming phone number value.
	 *
	 * @return string
	 */
	public function sanitize_phone_number( $value ) {
		if ( function_exists( 'wc_sanitize_phone_number' ) ) {
			return wc_sanitize_phone_number( $value );
		}

		return preg_replace( '/[^\d+]/', '', $value );
	}

	/**
	 * Process errors logged for form fields.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 *
	 * @param array $settings An array of settings fields.
	 *
	 * @return array
	 */
	private function process_errors( $settings ) {
		if ( empty( $this->errors ) ) {
			return $settings;
		}

		foreach ( $settings as $key => &$field ) {
			if ( empty( $field['id'] ) ) {
				continue;
			}

			if ( ! isset( $this->errors[ $field['id'] ] ) ) {
				continue;
			}

			$field['desc'] = ( ! empty( $field['desc'] ) ? $field['desc'] . '<br/>' : '' ) . $this->errors[ $field['id'] ];
		}

		return $settings;
	}

	/**
	 * Update the setup option.
	 *
	 * This is used to prevent errors from appearing before the user has submitted the form,
	 * i.e. after a fresh installation.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 */
	public function update_setup_option() {
		update_option( 'constant_contact_for_woo_has_setup', true );
	}

	/**
	 * Make all form elements for our settings `display:block`.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 *
	 * @param array $settings Array of settings to adjust.
	 *
	 * @return array
	 */
	private function adjust_styles( $settings ) {
		foreach ( $settings as $key => $field ) {
			if ( ! empty( $field['css'] ) ) {
				continue;
			}

			if ( in_array( $field['type'], [ 'title', 'sectionend' ], true ) ) {
				continue;
			}

		}

		return $settings;
	}

	/**
	 * Get the Country code from the WooCommerce settings.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 * @return string
	 */
	public function get_woo_country() : string {
		return wc_get_base_location()['country'] ?? '';
	}

	/**
	 * Save settings.
	 *
	 * @author Zach Owen <zach@webdevstudios>
	 * @since 2.0.0
	 * @return void
	 */
	public function save() {

		parent::save();

		// Prevent redirect to customer_data_import screen if we don't meet connection requirements.
		if ( ! $this->meets_connect_requirements() ) {
			return;
		}

		if ( $this->connection->is_connected() || $this->has_active_settings_section() ) {
			return;
		}

		// Maybe redirect to the connect bridge.
		$this->maybe_redirect_to_cc();

		wp_safe_redirect( add_query_arg( 'section', $this->import_existing_customer_section ) );
		exit;
	}

	/**
	 * Overrides the save button.
	 *
	 * @since 2.0.0
	 * @author Biplav Subedi <biplav.subedi@webdevstudios>
	 * @return array
	 */
	public function override_save_button() {
		if ( ! isset( $_GET['cc-connect'] ) ) {
			return;
		}

		$connected = get_option( ConnectionStatus::CC_CONNECTION_ESTABLISHED_KEY );
		$text      = $connected ? 'Save' : __( 'Save & Connect account', 'constant-contact-woocommerce' );
		$value     = $connected ? 'cc-woo-save' : 'cc-woo-connect';
		wp_nonce_field( $this->nonce_action, $this->nonce_name );

		$disc = admin_url( 'admin.php?page=' . esc_attr( $_GET['page'] ) );
		$disc = add_query_arg( array(
			'cc-connect' => 'disconnect',
			'tab'        => 'wc-settings' === $_GET['page'] ? 'cc_woo' : '',
		), $disc );

		?><div style="padding: 1rem 0;">
			<p class="submit">
				<button name="save" class="cc-woo-btn ctct-woo-connect button-primary woocommerce-save-button" type="submit" value="<?php echo $value; ?>" style="background-color:#1856ED"><?php echo esc_html( $text ); ?></button>
				<?php if( $connected ) { ?>
					<a href="<?php echo esc_url( $disc ); ?>" class="cc-woo-btn btn-alternate" style="margin-left:20px;"> <?php esc_html_e( "Disconnect", 'constant-contact-woocommerce' ); ?> </a>
				<?php } ?>
			</p>
		</div>

	<?php
	}

	/**
	 * Return the options for REST requests.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 * @return array
	 */
	private function get_rest_settings_options() : array {
		return $this->get_store_information_settings();
	}

	/**
	 * Gets the Connect Button for the settings fields.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 * @return array
	 */
	private function get_connection_button() : array {
		return [
			'type' => 'cc_connection_button',
		];
	}

	/**
	 * Check whether there is an active section on the Woo settings page.
	 *
	 * When a user clicks a subsection (in this case the Historical data tab),
	 * Woo sets a global `$current_section` variable to know which tab to select.
	 *
	 * @since 2.0.0
	 * @author Zach Owen <zach@webdevstudios>
	 * @return bool
	 */
	private function has_active_settings_section() : bool {
		return ! empty( $GLOBALS['current_section'] ?? '' );
	}

	/**
	 * Hides the default save button
	 *
	 * @return void
	 * @author Biplav Subedi <biplav.subedi@webdevstudios.com>
	 * @since 2.0.0
	 */
	public function hide_default_save_button(){
		if( isset( $_GET['tab'] ) && 'cc_woo' === $_GET['tab'] ) {
		?>
			<style>
				button.woocommerce-save-button:not(.ctct-woo-connect) {
					display:none;
				}
			</style>
		<?php
		}
	}

	/**
	 * Adding a wrapper class.
	 */
	public function output() {
		echo "<div class='cc-woo-wrap'>";
			parent::output();
			$this->override_save_button();
		echo "</div>";
	}

}
