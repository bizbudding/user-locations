<?php
/**
 * Wampum Widgets
 *
 * @package   User_Locations_Widgets
 * @author    Mike Hemberger
 * @link      https://bizbudding.com
 * @copyright 2016 Mike Hemberger
 * @license   GPL-2.0+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register Wampum Widgets
 *
 * @package Wampum Widgets
 * @author  Mike Hemberger
 */
final class User_Locations_Widgets {

	/** Singleton *************************************************************/

	/**
	 * @var   Wampum_Settings The one true Wampum_Settings
	 * @since 1.0.0
	 */
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new User_Locations_Widgets;
			// Methods
			self::$instance->init();
		}
		return self::$instance;
	}

	public function init() {
		// Register our new widget
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
	}

	public function register_widgets() {
	    register_widget( 'User_Locations_Location_Info' );
	}

}
