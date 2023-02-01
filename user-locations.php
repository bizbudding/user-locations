<?php
/**
 * @package   User_Locations
 * @author    BizBudding, INC <mike@bizbudding.com>
 * @license   GPL-2.0+
 * @link      http://bizbudding.com.com
 * @copyright 2016 BizBudding, Inc
 *
 * @wordpress-plugin
 * Plugin Name:        User Locations
 * Description:        Allow users to be considered locations (e.g. franchising website)
 * Plugin URI:         https://github.com/bizbudding/user-locations
 * Author:             Mike Hemberger
 * Author URI:         http://bizbudding.com
 * Text Domain:        user-locations
 * License:            GPL-2.0+
 * License URI:        http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * Version:            1.5.2
 *
 * GitHub Plugin URI:  https://github.com/bizbudding/user-locations
 * GitHub Branch:      master
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main User_Locations_Setup Class.
 *
 * @since 1.0.0
 */
final class User_Locations_Setup {

	/**
	 * @var User_Locations_Setup The one true User_Locations_Setup
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * User_Locations_Admin Object
	 *
	 * @since 1.0.0
	 *
	 * @var object | User_Locations
	 */
	public $admin;

	/**
	 * User_Locations_Content_Types Object
	 *
	 * @since 1.0.0
	 *
	 * @var object | User_Locations
	 */
	public $content;

	/**
	 * User_Locations_Fields Object
	 *
	 * @since 1.0.0
	 *
	 * @var object | User_Locations
	 */
	public $fields;

	/**
	 * User_Locations_Frontend Object
	 *
	 * @since 1.0.0
	 *
	 * @var object | User_Locations
	 */
	public $frontend;

	/**
	 * User_Locations_Frontend Object
	 *
	 * @since 1.2.3
	 *
	 * @var object | User_Locations
	 */
	public $integrations;

	/**
	 * User_Locations_Template_Loader Object
	 *
	 * @since 1.0.0
	 *
	 * @var object | User_Locations
	 */
	public $templates;

	/**
	 * User_Locations_Widgets Object
	 *
	 * @since 1.0.0
	 *
	 * @var object | User_Locations
	 */
	public $widgets;

	/**
	 * Main User_Locations_Setup Instance.
	 *
	 * Insures that only one instance of User_Locations_Setup exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   1.0.0
	 * @static  var array $instance
	 * @uses    User_Locations_Setup::setup_constants() Setup the constants needed.
	 * @uses    User_Locations_Setup::includes() Include the required files.
	 * @uses    User_Locations_Setup::load_textdomain() load the language files.
	 * @see     User_Locations()
	 * @return  object | User_Locations_Setup The one true User_Locations_Setup
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new User_Locations_Setup;
			// Methods
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->setup();
			// Instantiate Classes
			self::$instance->admin        = User_Locations_Admin::instance();
			self::$instance->content      = User_Locations_Content_Types::instance();
			self::$instance->fields       = User_Locations_Fields::instance();
			self::$instance->frontend     = User_Locations_Frontend::instance();
			self::$instance->integrations = User_Locations_Integrations::instance();
			self::$instance->templates    = User_Locations_Template_Loader::instance();
			self::$instance->widgets      = User_Locations_Widgets::instance();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'user-locations' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'user-locations' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function setup_constants() {
		// Plugin version.
		if ( ! defined( 'USER_LOCATIONS_VERSION' ) ) {
			define( 'USER_LOCATIONS_VERSION', '1.5.2' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'USER_LOCATIONS_PLUGIN_DIR' ) ) {
			define( 'USER_LOCATIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Includes Path
		if ( ! defined( 'USER_LOCATIONS_INCLUDES_DIR' ) ) {
			define( 'USER_LOCATIONS_INCLUDES_DIR', USER_LOCATIONS_PLUGIN_DIR . 'includes/' );
		}

		// Plugin Folder URL.
		if ( ! defined( 'USER_LOCATIONS_PLUGIN_URL' ) ) {
			define( 'USER_LOCATIONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'USER_LOCATIONS_PLUGIN_FILE' ) ) {
			define( 'USER_LOCATIONS_PLUGIN_FILE', __FILE__ );
		}

		// Plugin Base Name
		if ( ! defined( 'USER_LOCATIONS_BASENAME' ) ) {
			define( 'USER_LOCATIONS_BASENAME', dirname( plugin_basename( __FILE__ ) ) );
		}
	}

	/**
	 * Include required files.
	 *
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function includes() {
		// Include vendor libraries.
		require_once __DIR__ . '/vendor/autoload.php';
		// Vendor manually.
		require_once USER_LOCATIONS_INCLUDES_DIR . 'lib/class-gamajo-template-loader.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'lib/extended-cpts.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'lib/extended-taxos.php';
		// Classes.
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-admin.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-content-types.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-fields.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-frontend.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-integrations.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-template-loader.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-widgets.php';
		// Widgets.
		require_once USER_LOCATIONS_INCLUDES_DIR . 'widgets/widget-location-info.php';
		// Functions.
		require_once USER_LOCATIONS_INCLUDES_DIR . 'functions.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'functions-admin.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'functions-display.php';
		// require_once USER_LOCATIONS_INCLUDES_DIR . 'functions-maps.php';
	}

	public function setup() {

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

		// Setup updater.
		add_action( 'plugins_loaded', array( $this, 'updater' ) );

		// Bail if ACF Pro is not active.
		if ( ! class_exists('acf_pro') ) {
			return;
		}
		// Add new load point for ACF json field groups.
		add_filter( 'acf/settings/load_json', array( $this, 'acf_json_load_point' ) );

		// Register stylesheet.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_stylesheets' ) );
		// add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
	}

	/**
	 * Setup the updater.
	 *
	 * composer require yahnis-elsts/plugin-update-checker
	 *
	 * @uses    https://github.com/YahnisElsts/plugin-update-checker/
	 *
	 * @return  void
	 */
	public function updater() {

		// Bail if current user cannot manage plugins.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		// Bail if plugin updater is not loaded.
		if ( ! class_exists( 'Puc_v4_Factory' ) ) {
			return;
		}

		// Setup the updater.
		$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/bizbudding/user-locations/', __FILE__, 'user-locations' );
	}

	/**
	 * Adds caps to user roles.
	 *
	 * @return void
	 */
	public function activate() {
		$roles = array( 'administrator', 'editor' );
		$roles = apply_filters( 'ul_get_location_roles', $roles );

		foreach( $roles as $name ) {
			$role = get_role( $name );
			$role->add_cap( 'publish_location_pages' );
			$role->add_cap( 'edit_location_page' );
			$role->add_cap( 'edit_location_pages' );
			$role->add_cap( 'edit_published_location_pages' );
			$role->add_cap( 'edit_others_location_pages' );
			$role->add_cap( 'delete_location_page' );
			$role->add_cap( 'delete_location_pages' );
			$role->add_cap( 'delete_others_location_pages' );
			$role->add_cap( 'read_private_location_pages' );
		}
		self::$instance->content->register_post_types();
		self::$instance->content->register_taxonomies();

		flush_rewrite_rules();
	}

	/**
	 * Get singular post type name
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function get_singular_name( $lowercase = false ) {
		$name = $this->get_default_name('singular');
		return $lowercase ? strtolower($name) : $name;
	}

	/**
	 * Get plural post type name
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function get_plural_name( $lowercase = false ) {
		$name = $this->get_default_name('plural');
		return $lowercase ? strtolower($name) : $name;
	}

	/**
	 * Helper function to get default names by key
	 * Try to use get_singlular_name() and get_plural_name()
	 *
	 * This is mostly here for getting the slug
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function get_default_name( $key ) {
		return $this->get_default_names()[$key];
	}

	/**
	 * Get the default names
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_default_names() {
		$names = array(
			'plural'   => 'Locations',
			'singular' => 'Location',
			'slug'     => 'locations',
		);
		return apply_filters( 'ul_get_default_names', $names );
	}

	/**
	 * Add the new load point for ACF JSON files in the plugin
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function acf_json_load_point( $paths ) {
		$paths[] = USER_LOCATIONS_INCLUDES_DIR . 'acf-json';
		return $paths;
	}

	/**
	 * Register stylesheets for later use
	 *
	 * Use via wp_enqueue_style('user-locations'); in a template
	 *
	 * @since  1.2.0
	 *
	 * @return null
	 */
	public function register_stylesheets() {
		wp_register_style( 'user-locations', USER_LOCATIONS_PLUGIN_URL . 'assets/css/user-locations.css', array(), USER_LOCATIONS_VERSION );
	}
}

/**
 * The main function for that returns User_Locations_Setup
 *
 * The main function responsible for returning the one true User_Locations_Setup
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $user_locations = User_Locations(); ?>
 *
 * @since 1.0.0
 *
 * @return object|User_Locations_Setup The one true User_Locations_Setup Instance.
 */
function user_locations() {
	return User_Locations_Setup::instance();
}

// Get User_Locations Running.
user_locations();
