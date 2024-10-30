<?php
/**
 * Interface for objects that need to validate contents.
 *
 * @author  Jeremy Ward <jeremy.ward@webdevstudios.com>
 * @package WebDevStudios\Utility
 * @since 2019-03-07
 *
 * phpcs:disable WebDevStudios.All.RequireReturn -- Valid use of @return tags for interface methods.
 */

namespace WebDevStudios\CCForWoo\Utility;

/**
 * Interface Validatable
 *
 * @author  Jeremy Ward <jeremy.ward@webdevstudios.com>
 * @package WebDevStudios\Utility
 * @since   2019-03-07
 */
interface Validatable {
	/**
	 * Confirm whether data is valid.
	 *
	 * @author Jeremy Ward <jeremy.ward@webdevstudios.com>
	 * @since  2019-03-07
	 *
	 * @return bool Whether data is valid.
	 */
	public function is_valid() : bool;
}
