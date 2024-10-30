<?php

namespace WebDevStudios\CCForWoo\Utility;

/**
 * Class DebugLogging
 */
class DebugLogging {

	/**
	 * The logger from WooCommerce.
	 *
	 * @since 2.1.0
	 * @var object
	 */
	private $logger;

	/**
	 * The message to log.
	 *
	 * @var mixed|string
	 */
	private $message;

	/**
	 * The message level.
	 *
	 * See https://developer.woocommerce.com/2017/01/26/improved-logging-in-woocommerce-2-7/
	 *
	 * @var mixed|string
	 */
	private $level;

	/**
	 * Extra contextual information for the log item.
	 *
	 * @var array|mixed
	 */
	private $extras;

	/**
	 * Construct our compatibility checker with the main plugin class.
	 *
	 * @param string $classname The classname to use for testing.
	 *
	 * @author Zach Owen <zach@webdevstudios>
	 * @since  2.1.0
	 */
	public function __construct( $logger, $message = '', $level = '', $extras = [] ) {
		$this->logger  = $logger;
		$this->message = $message;
		$this->level   = $level;
		$this->extras  = $extras;
	}

	/**
	 * Perform our logging, if WP_DEBUG_LOG is enabled.
	 *
	 * @since 2.1.0
	 */
	public function log() {
		if ( defined( 'WP_DEBUG_LOG' ) ) {
			$this->logger->{$this->level}(
				$this->message,
				$this->extras
			);
		}
	}
}
