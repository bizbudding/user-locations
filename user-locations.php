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
 * Description: 	   The core User Locations plugin
 * Plugin URI:         https://github.com/JiveDig/user-locations
 * Author:             Mike Hemberger
 * Author URI:         http://bizbudding.com
 * Text Domain:        User_Locations
 * License:            GPL-2.0+
 * License URI:        http://www.gnu.org/licenses/gpl-2.0.txt
 * Version:            1.0.0
 * GitHub Plugin URI:  https://github.com/JiveDig/user-locations
 * GitHub Branch:	   master
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'User_Locations_Setup' ) ) :

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
	 * User_Locations_Template_Loader Object
	 *
	 * @since 1.0.0
	 *
	 * @var object | User_Locations
	 */
	public $templates;

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
			self::$instance->content   = User_Locations_Content_Types::instance();
			self::$instance->fields    = User_Locations_Fields::instance();
			self::$instance->frontend  = User_Locations_Frontend::instance();
			self::$instance->templates = User_Locations_Template_Loader::instance();
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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'User_Locations' ), '1.0' );
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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'User_Locations' ), '1.0' );
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
			define( 'USER_LOCATIONS_VERSION', '1.2.4' );
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
		// Vendor
		require_once USER_LOCATIONS_INCLUDES_DIR . 'lib/class-gamajo-template-loader.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'lib/extended-cpts.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'lib/extended-taxos.php';
		// Classes
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-content-types.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-fields.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-frontend.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-template-loader.php';
		// Functions
		require_once USER_LOCATIONS_INCLUDES_DIR . 'user-locations-functions.php';
	}

	// TODO: CHECK IF ACF PRO IS ACTIVE
	public function setup() {

		// Bail if Posts to Posts or Piklist are not active
		// if ( ! function_exists( 'p2p_register_connection_type' ) ) {
		// 	return;
		// }
		// Genesis & WooCommerce Connect
		add_theme_support( 'genesis-connect-woocommerce' );
		// Options page
		$this->create_options_page();
		// Login redirect
		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );
		// Remove menu
		add_action( 'admin_menu', array( $this, 'remove_admin_menu_items' ) );
	}

	public function create_options_page() {
		acf_add_options_page(array(
			'page_title' 	 => 'My Location',
			'menu_title'	 => 'Settings',
			'menu_slug' 	 => 'location_settings',
			'capability'	 => 'edit_posts',
			'icon_url'       => 'dashicons-location-alt',
			'position'       => 2,
			'redirect'		 => false
		));
	}

	/**
	 * Redirect user after successful login.
	 *
	 * @param  string $redirect_to URL to redirect to.
	 * @param  string $request URL the user is coming from.
	 * @param  object $user Logged user's data.
	 *
	 * @return string
	 */
	public function login_redirect( $redirect_to, $request, $user ) {
	    // is there a user to check?
	    global $user;
	    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
	        //check for admins
	        if ( in_array( 'administrator', $user->roles ) ) {
	            // redirect them to the default place
	            return $redirect_to;
	        } else {
	            if ( in_array( $GLOBALS['pagenow'], array( 'wp-login.php' ) ) ) {
					// Options page url
	                return admin_url( 'admin.php?page=general' );
	            } else {
	                return $request;
	            }
	        }
	    } else {
	        return $redirect_to;
	    }
	}

	public function remove_admin_menu_items() {
		if ( current_user_can('manage_options') ) {
			return;
		}
		remove_menu_page('index.php');  // Dashboard
		remove_menu_page('upload.php'); // Media
		remove_menu_page('tools.php');  // Tools
	}

}
endif; // End if class_exists check.

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
function User_Locations() {
	return User_Locations_Setup::instance();
}

// Get User_Locations Running.
User_Locations();
