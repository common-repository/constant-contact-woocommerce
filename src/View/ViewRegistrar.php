<?php
/**
 * ViewRegistrar.
 *
 * @author  Jeremy Ward <jeremy.ward@webdevstudios.com>
 * @package WebDevStudios\CCForWoo\View
 * @since   2019-03-13
 */

namespace WebDevStudios\CCForWoo\View;

use WebDevStudios\CCForWoo\View\Admin\WooTab;
use WebDevStudios\CCForWoo\View\Checkout\CampaignId;
use WebDevStudios\CCForWoo\View\Checkout\NewsletterPreferenceCheckbox;
use WebDevStudios\OopsWP\Structure\Service;

/**
 * Class ViewRegistrar
 *
 * @author  Jeremy Ward <jeremy.ward@webdevstudios.com>
 * @package WebDevStudios\CCForWoo\View
 * @since   2019-03-13
 */
class ViewRegistrar extends Service {
	/**
	 * View settings.
	 *
	 * @var   array
	 * @since 2019-03-13
	 */
	protected $settings = [
		WooTab::class,
	];

	/**
	 * View forms.
	 *
	 * @var   array
	 * @since 2019-03-13
	 */
	protected $forms = [
		NewsletterPreferenceCheckbox::class,
		CampaignId::class,
	];


	/**
	 * Register actions and filters with WordPress.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since  1.0.0
	 */
	public function register_hooks() {
		add_filter( 'woocommerce_get_settings_pages', [ $this, 'register_settings' ] );
		add_action( 'init', [ $this, 'register_forms' ] );
	}

	/**
	 * Register settings.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since  2019-03-12
	 *
	 * @param  array $woo_settings_pages WC settings pages.
	 * @return array                     WC settings pages.
	 */
	public function register_settings( $woo_settings_pages ) {
		array_walk( $this->settings, [ $this, 'register_object' ] );
		return $woo_settings_pages;
	}

	/**
	 * Register forms.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since  2019-03-13
	 */
	public function register_forms() {
		array_walk( $this->forms, [ $this, 'register_object' ] );
	}

	/**
	 * Register object.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since  2019-03-13
	 *
	 * @param  string $object_class The class name of the object.
	 */
	private function register_object( string $object_class ) {
		/* @var \WebDevStudios\OopsWP\Utility\Hookable $object An object that can be hooked into WordPress. */
		$object = new $object_class();
		$object->register_hooks();
	}
}
