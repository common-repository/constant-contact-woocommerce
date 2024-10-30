<?php
/**
 * Validation class for the SettingsModel.
 *
 * This class is used to verify that data submitted to the plugin settings are valid and correctly formatted.
 *
 * @author  Jeremy Ward <jeremy.ward@webdevstudios.com>
 * @package WebDevStudios\CCForWoo\Settings
 * @since   2019-03-07
 */

namespace WebDevStudios\CCForWoo\Settings;

use WebDevStudios\CCForWoo\Utility\Validatable;
use WC_Validation;

/**
 * Class SettingsValidator
 *
 * @author  Jeremy Ward <jeremy.ward@webdevstudios.com>
 * @package WebDevStudios\CCForWoo\Settings
 * @since   2019-03-07
 */
class SettingsValidator implements Validatable {
	/**
	 * Instance of the SettingsModel.
	 *
	 * @var SettingsModel
	 * @since 2019-03-07
	 */
	private $settings;

	/**
	 * SettingsValidator constructor.
	 *
	 * @param SettingsModel $settings Instance of the SettingsModel.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since  2019-03-07
	 */
	public function __construct( SettingsModel $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Confirm whether data is valid.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since  2019-03-07
	 * @return bool
	 */
	public function is_valid(): bool {
		return (
			$this->has_valid_phone()
			&& $this->has_valid_email()
		);
	}

	/**
	 * Verify that the name is valid.
	 *
	 * @since 2019-03-08
	 * @author Zach Owen <zach@webdevstudios>
	 * @return bool
	 */
	private function has_valid_name() {
		return ! empty( trim( $this->settings->get_first_name() ) ) && ! empty( trim( $this->settings->get_last_name() ) );
	}

	/**
	 * Verify that the store name is valid.
	 *
	 * @since 2019-03-08
	 * @author Zach Owen <zach@webdevstudios>
	 * @return bool
	 */
	private function has_valid_store_name() {
		return ! empty( trim( $this->settings->get_store_name() ) );
	}

	/**
	 * Verify that the email address is valid.
	 *
	 * @since 2019-03-08s
	 * @author Zach Owen <zach@webdevstudios>
	 * @return bool
	 */
	private function has_valid_email() {
		// Not required.
		if ( empty( $this->settings->get_email_address() ) ) {
			return true;
		}

		return filter_var( $this->settings->get_email_address(), FILTER_VALIDATE_EMAIL );
	}

	/**
	 * Verify that the phone number is valid.
	 *
	 * @since  2019-03-08
	 * @author Zach Owen <zach@webdevstudios>
	 *
	 * @uses   WC_Validation::is_phone()
	 *
	 * @return bool
	 */
	private function has_valid_phone(): bool {
		// Not required.
		if ( empty( $this->settings->get_phone_number() ) ) {
			return true;
		}
		return ! empty( $this->settings->get_phone_number() )
			? WC_Validation::is_phone( $this->settings->get_phone_number() )
			: false;
	}

	/**
	 * Validates the Country Code field.
	 *
	 * @since 2019-03-11
	 * @author Zach Owen <zach@webdevstudios>
	 * @return bool
	 */
	private function has_valid_country_code() {
		return ! empty( $this->settings->get_country_code() );
	}
}
