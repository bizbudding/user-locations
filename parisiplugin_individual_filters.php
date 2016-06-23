<?php
/**
 * @package   Parisi
 * @author    BizBudding, INC <mike@bizbudding.com>
 * @license   GPL-2.0+
 * @link      http://bizbudding.com.com
 * @copyright 2016 BizBudding, Inc
 *
 * @wordpress-plugin
 * Plugin Name:        Parisi Plugin
 * Description: 	   The core Parisi plugin
 * Plugin URI:         https://github.com/JiveDig/parisi-plugin
 * Author:             Mike Hemberger
 * Author URI:         http://bizbudding.com
 * Text Domain:        Parisi
 * License:            GPL-2.0+
 * License URI:        http://www.gnu.org/licenses/gpl-2.0.txt
 * Version:            1.2.4
 * GitHub Plugin URI:  https://github.com/JiveDig/parisi-plugin
 * GitHub Branch:	   master
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Parisi_Setup' ) ) :

/**
 * Main Parisi_Setup Class.
 *
 * @since 1.0.0
 */
final class Parisi_Setup {
	/** Singleton *************************************************************/

	/**
	 * @var Parisi_Setup The one true Parisi_Setup
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Parisi Content Types Object
	 *
	 * @since 1.0.0
	 *
	 * @var object | Parisi_Content_Types
	 */
	public $content;

	/**
	 * Main Parisi_Setup Instance.
	 *
	 * Insures that only one instance of Parisi_Setup exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   1.0.0
	 * @static  var array $instance
	 * @uses    Parisi_Setup::setup_constants() Setup the constants needed.
	 * @uses    Parisi_Setup::includes() Include the required files.
	 * @uses    Parisi_Setup::load_textdomain() load the language files.
	 * @see     Parisi()
	 * @return  object | Parisi_Setup The one true Parisi_Setup
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new Parisi_Setup;
			// Methods
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->setup();
			// Instantiate Classes
			self::$instance->content		= Parisi_Content_Types::instance();
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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'Parisi' ), '1.0' );
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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'Parisi' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access private
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_constants() {

		// Plugin version.
		if ( ! defined( 'PARISI_VERSION' ) ) {
			define( 'PARISI_VERSION', '1.2.4' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'PARISI_PLUGIN_DIR' ) ) {
			define( 'PARISI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Includes Path
		if ( ! defined( 'PARISI_INCLUDES_DIR' ) ) {
			define( 'PARISI_INCLUDES_DIR', PARISI_PLUGIN_DIR . 'includes/' );
		}

		// Plugin Folder URL.
		if ( ! defined( 'PARISI_PLUGIN_URL' ) ) {
			define( 'PARISI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'PARISI_PLUGIN_FILE' ) ) {
			define( 'PARISI_PLUGIN_FILE', __FILE__ );
		}

		// Plugin Base Name
		if ( ! defined( 'PARISI_BASENAME' ) ) {
			define( 'PARISI_BASENAME', dirname( plugin_basename( __FILE__ ) ) );
		}

	}

	/**
	 * Include required files.
	 *
	 * @access private
	 * @since 1.0.0
	 * @return void
	 */
	private function includes() {
		// Vendor
		require_once PARISI_INCLUDES_DIR . 'lib/class-gamajo-template-loader.php';
		require_once PARISI_INCLUDES_DIR . 'lib/extended-cpts.php';
		require_once PARISI_INCLUDES_DIR . 'lib/extended-taxos.php';
		// Classes
		require_once PARISI_INCLUDES_DIR . 'class-parisi-content-types.php';
		require_once PARISI_INCLUDES_DIR . 'class-parisi-template-loader.php';
		// Functions
		// require_once PARISI_INCLUDES_DIR . 'functions-helpers.php';
	}

	// TODO: CHECK IF ACF PRO IS ACTIVE
	public function setup() {

		// Bail if Posts to Posts or Piklist are not active
		// if ( ! function_exists( 'p2p_register_connection_type' ) ) {
		// 	return;
		// }

		// Login redirect
		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );

		// Remove menu
		add_action( 'admin_menu', array( $this, 'remove_admin_menu_items' ) );

		// Options page
		$this->create_options_page();
		// Update user data
		$this->load_user_data_fields();
		// Update user meta
		$this->load_user_meta_fields();
		// Save user data fields
		$this->save_user_data_fields();
		// Save user meta fields
		$this->save_user_meta_fields();
		// Save user special fields
		$this->save_user_special_fields();
		// add_action( 'admin_head', array( $this, 'custom_admin_css' ) );

		// Genesis & WooCommerce Connect
		add_theme_support( 'genesis-connect-woocommerce' );
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
		remove_menu_page('index.php'); // Dashboard
		remove_menu_page('upload.php'); // Media
		remove_menu_page('tools.php'); // Tools
	}

	public function create_options_page() {
		acf_add_options_page(array(
			'page_title' 	 => 'My Location',
			'menu_title'	 => 'General Info',
			'menu_slug' 	 => 'general',
			'capability'	 => 'edit_posts',
			'icon_url'       => 'dashicons-location-alt',
			'position'       => 2,
			'redirect'		 => false
		));
	}

	public function load_user_data_fields() {
  		$fields = $this->get_user_data_fields();
		foreach( $fields as $name ) {
			add_filter('acf/load_value/name=' . $name, array( $this, 'load_user_data_field' ), 10, 3 );
		}
	}

	public function load_user_meta_fields() {
		$fields = $this->get_user_meta_fields();
		foreach( $fields as $name ) {
			add_filter('acf/load_value/name=' . $name, array( $this, 'load_user_meta_field' ), 10, 3 );
		}
	}

	public function load_user_data_field( $value, $post_id, $field ) {
		$user  = get_user_by( 'ID', get_current_user_id() );
		$value = $user->$field['name'];
		return $value;
	}

	public function load_user_meta_field( $value, $post_id, $field ) {
		$value = get_user_meta( get_current_user_id(), $field['name'], true );
		return $value;
	}

	public function save_user_data_fields() {
		$fields = $this->get_user_meta_fields();
		foreach( $fields as $name ) {
			// Skip if a special field, those are handle separately
			if ( in_array( $name, $this->get_user_special_fields() ) ) {
				continue;
			}
			add_filter( 'acf/update_value/name=' . $name, array( $this, 'save_user_data_field' ), 10, 3 );
		}
	}

	public function save_user_meta_fields() {
		$fields = $this->get_user_meta_fields();
		foreach( $fields as $name ) {
			add_filter( 'acf/update_value/name=' . $name, array( $this, 'save_user_meta_field' ), 10, 3 );
		}
	}

	public function save_user_special_fields() {
		$fields  = $this->get_user_special_fields();
		foreach ( $fields as $name ) {
			add_filter( 'acf/update_value/name=' . $name, array( $this, 'save_user_special_field' ), 10, 3 );
		}
	}

	public function save_user_special_field( $value, $post_id, $field ) {
		$user_id = get_current_user_id();
		trace($field);
		if ( $field['name'] == 'nickname' ) {
			$user_data = array(
				'ID' 	       => $user_id,
				'nickname'     => $value,
				'display_name' => $value,
			);
			wp_update_user( $user_data );
		}
	}

	public function save_user_data_field( $value, $post_id, $field ) {
		// trace(get_current_user_id());
		$user_data = array(
			'ID'           => get_current_user_id(),
			$field['name'] => $value,
		);
		wp_update_user( $user_data );
		return '';
	}

	public function save_user_meta_field( $value, $post_id, $field ) {
		// trace(get_current_user_id());
		update_user_meta( get_current_user_id(), $field['name'], $value );
		return '';
	}

	public function get_user_data_fields() {
		return array(
			'display_name',
			'user_email',
			// 'user_url',
		);
	}

	public function get_user_special_fields() {
		return array(
			'nickname',
		);
	}

	public function get_user_meta_fields() {
		return array(
			'first_name',
			'last_name',
			'nickname',
			'description',
			'phone',
			'address_street',
			'address_street_2',
			'address_city',
			'address_state',
			'address_postcode',
			'facebook',
			'twitter',
			'googleplus',
			'youtube',
			'linkedin',
			'instagram',
		);
	}

	public function get_all_field_names_old( $field_group_key_or_id ) {

		$names = array();

		$fields = acf_get_fields($field_group_key_or_id);
		if ( $fields ) {
			foreach( $fields as $field ) {
				// Skip fields with no name (Tab/Message/ect fields)
				if ( ! $field['name'] ) {
					continue;
				}
				$names[] = $field['name'];
			}
		}
		return $names;
	}

	/**
	 * Add custom CSS to <head>
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function custom_admin_css() {
		// Bail if administrators
		if ( current_user_can('manage_options') ) {
			return;
		}
		echo '<style type="text/css">
			.user-comment-shortcuts-wrap,
			.user-admin-bar-front-wrap,
			.user-url-wrap,
			.user-googleplus-wrap,
			.user-twitter-wrap,
			.user-facebook-wrap,
			.user-profile-picture,
			.wc-memberships.user-memberships {
				display:none !important;
			}
		</style>';
	}

}
endif; // End if class_exists check.

/**
 * The main function for that returns Parisi_Setup
 *
 * The main function responsible for returning the one true Parisi_Setup
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $parisi = Parisi(); ?>
 *
 * @since 1.0.0
 *
 * @return object|Parisi_Setup The one true Parisi_Setup Instance.
 */
function Parisi() {
	return Parisi_Setup::instance();
}

// Get Parisi Running.
Parisi();
