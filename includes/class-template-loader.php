<?php
/**
 * User_Locations Template Loader
 *
 * @package   User_Locations
 * @author    Mike Hemberger
 * @link      https://bizbudding.com
 * @copyright 2016 Mike Hemberger
 * @license   GPL-2.0+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Template loader for User_Locations
 *
 * @package User_Locations
 * @author  Mike Hemberger
 */
final class User_Locations_Template_Loader extends Gamajo_Template_Loader {

	/**
	 * @var   User_Locations_Template_Loader The one true User_Locations_Template_Loader
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Prefix for filter names.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	protected $filter_prefix = 'user_locations';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	protected $theme_template_directory = 'user-locations';

	/**
	 * Reference to the root directory path of this plugin.
	 *
	 * Can either be a defined constant, or a relative reference from where the subclass lives.
	 *
	 * In this case, `MEAL_PLANNER_PLUGIN_DIR` would be defined in the root plugin file as:
	 *
	 * ~~~
	 * define( 'MEAL_PLANNER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	 * ~~~
	 *
	 * @since 1.0.0
	 * @type string
	 */
	protected $plugin_directory = USER_LOCATIONS_PLUGIN_DIR;

	/**
	 * Directory name where templates are found in this plugin.
	 *
	 * Can either be a defined constant, or a relative reference from where the subclass lives.
	 *
	 * @since 1.1.0
	 *
	 * @type string
	 */
	protected $plugin_template_directory = 'templates'; // or includes/templates, etc.

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new User_Locations_Template_Loader;
			// Methods
			self::$instance->init();
		}
		return self::$instance;
	}

	public function init() {
		// Maybe remove the loop, if location page is a specific page type (template)
		add_action( 'wp_head', array( $this, 'maybe_location_page_template' ) );
	}

	public function maybe_location_page_template() {

	    if ( ! is_singular('location_page') || ! ul_is_location_page_template() ) {
	    	return;
	    }
		// Add custom body class to the head
		add_filter( 'body_class', array( $this, 'body_classes' ) );
		add_action( 'genesis_loop', array( $this, 'do_location_page_templates_loop' ) );
	}

	public function body_classes( $classes ) {
		$terms = get_the_terms ( get_the_ID(), 'location_page_template' );
		if ( $terms ) {
			// Get the first term slug
			$slug = $terms[0]->slug;
			$classes[] = $slug . ' location-page-type-' . $slug;
		}
		return $classes;
	}

	/**
	 * Attempt to load a template file in the theme based on location_page_template slug
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function do_location_page_templates_loop() {
		$terms = get_the_terms ( get_the_ID(), 'location_page_template' );
		// Get the first term slug
		$slug  = $terms[0]->slug;
		// Try to load the template
		ul_get_template_part( $slug );
	}

}
