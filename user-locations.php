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
 * Description: 	   Allow users to be considered locations (e.g. franchising website)
 * Plugin URI:         https://github.com/JiveDig/user-locations
 * Author:             Mike Hemberger
 * Author URI:         http://bizbudding.com
 * Text Domain:        user-locations
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
	 * User_Locations_Forms Object
	 *
	 * @since 1.0.0
	 *
	 * @var object | User_Locations
	 */
	public $forms;

	/**
	 * User_Locations_Frontend Object
	 *
	 * @since 1.0.0
	 *
	 * @var object | User_Locations
	 */
	public $frontend;

	/**
	 * User_Locations_Location Object
	 *
	 * @since 1.0.0
	 *
	 * @var object | User_Locations
	 */
	public $location;

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
			self::$instance->content   = User_Locations_Content_Types::instance();
			self::$instance->fields    = User_Locations_Fields::instance();
			self::$instance->forms     = User_Locations_Forms::instance();
			self::$instance->frontend  = User_Locations_Frontend::instance();
			self::$instance->location  = User_Locations_Location::instance();
			self::$instance->templates = User_Locations_Template_Loader::instance();
			self::$instance->widgets   = User_Locations_Widgets::instance();
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
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-forms.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-frontend.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-location.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-template-loader.php';
		require_once USER_LOCATIONS_INCLUDES_DIR . 'class-widgets.php';
		// Widgets
		require_once USER_LOCATIONS_INCLUDES_DIR . 'widgets/widget-show-address.php';
		// Functions
		require_once USER_LOCATIONS_INCLUDES_DIR . 'user-locations-functions.php';
	}

	// TODO: CHECK IF ACF PRO IS ACTIVE
	public function setup() {

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Bail if ACF Pro is not active
		if ( ! class_exists('acf_pro') ) {
			return;
		}
		add_filter( 'acf/settings/load_json', array( $this, 'acf_json_load_point' ) );

		// Genesis & WooCommerce Connect
		add_theme_support( 'genesis-connect-woocommerce' );

		add_action( 'load-post.php',     array( $this, 'load_user_dropdown_filter' ) );
		add_action( 'load-post-new.php', array( $this, 'load_user_dropdown_filter' ) );

		// Add new load point for ACF json field groups
	}

	public function activate() {
		$this->add_roles();
		$this->flush_rewrites();
	}

	public function deactivate() {
		$this->remove_roles();
		$this->flush_rewrites();
	}

	public function flush_rewrites() {
		flush_rewrite_rules();
	}

	public function add_roles() {
		add_role( 'location', $this->get_default_name('singular'), $this->get_location_capabilities() );
		// add_action( 'admin_init', 	  array( $this, 'edit_locations_cap' ) );
		add_filter( 'editable_roles', array( $this, 'remove_role_from_dropdown' ) );
	}

	public function remove_roles() {
		remove_role( 'location' );
	}

	public function get_location_capabilities() {
		$capabilities = array(
			'create_posts'			 => false, // Make this true after first Location Dashboard update/save
			'delete_posts'           => true,
			'delete_published_posts' => true,
			'edit_posts'             => true,
			'edit_published_posts'   => true,
			'publish_posts'          => true,
			'read'                   => true,
			'upload_files'           => true,
		);
		return apply_filters( 'ul_location_capabilities', $capabilities );
	}

	/**
	 * Add capabilities to the admin role and make available for selection on all other roles
	 */
	// function edit_locations_cap() {

	//     $user_roles = array( 'administrator' );

	//     foreach ( $user_roles as $user_role ) {
	// 		$role = get_role( $user_role );
	//     }
	// }

	/**
	 * Remove role from dropdown list on user profile
	 * Too much needs to happen when we create a new location (create a location, update that page's ID as user meta)
	 * It's not worth the trouble of allowing a user to become a location role without going the proper route
	 *
	 * @see    create_location() method in class-forms.php
	 *
	 * @since  1.0.0
	 *
	 * @param  array  $roles  existing user roles
	 *
	 * @return array
	 */
	public function remove_role_from_dropdown( $roles ) {
		unset($roles['location']);
	}

	public function load_user_dropdown_filter() {
	    $screen = get_current_screen();
	    if ( empty( $screen->post_type ) || 'location_page' !== $screen->post_type ) {
	        return;
	    }
	    add_filter( 'wp_dropdown_users_args', array( $this, 'dropdown_users_args' ), 10, 2 );
	}

	public function dropdown_users_args( $args, $r ) {
	    global $wp_roles, $post;
	    // Check that this is the correct drop-down.
	    if ( 'post_author_override' === $r['name'] && 'location_page' === $post->post_type ) {

	        $roles = $this->get_roles_for_post_type( $post->post_type );

	        // If we have roles, change the args to only get users of those roles.
	        if ( $roles ) {
	            $args['who']      = '';
	            $args['role__in'] = $roles;
	        }
	    }

	    return $args;
	}

	public function get_roles_for_post_type( $post_type ) {
	    global $wp_roles;

	    $roles = array();
	    $type  = get_post_type_object( $post_type );

	    // Get the post type object caps.
	    $caps = array( $type->cap->edit_posts, $type->cap->publish_posts, $type->cap->create_posts );
	    $caps = array_unique( $caps );

	    // Loop through the available roles.
	    foreach ( $wp_roles->roles as $name => $role ) {

	        foreach ( $caps as $cap ) {

	            // If the role is granted the cap, add it.
	            if ( isset( $role['capabilities'][ $cap ] ) && true === $role['capabilities'][ $cap ] ) {
	                $roles[] = $name;
	                break;
	            }
	        }
	    }

	    return $roles;
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
		return ($lowercase) ? strtolower($name) : $name;
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
		return ($lowercase) ? strtolower($name) : $name;
	}

	public function get_default_name( $key ) {
		return $this->get_default_names()[$key];
	}

	public function get_default_names() {
		$names = array(
			'plural'   => 'Locations',
			'singular' => 'Location',
			'slug'	   => 'locations',
		);
		return apply_filters( 'ul_get_default_names', $names );
	}

	public function acf_json_load_point( $paths ) {
	    $paths[] = USER_LOCATIONS_INCLUDES_DIR . 'acf-json';
	    return $paths;
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
