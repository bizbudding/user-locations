<?php
/**
 * User_Locations
 *
 * @package   User_Locations
 * @author    Mike Hemberger <mike@bizbudding.com.com>
 * @link      https://github.com/JiveDig/user-locations/
 * @copyright 2016 Mike Hemberger
 * @license   GPL-2.0+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main plugin class.
 *
 * @package User_Locations
 */
final class User_Locations_Forms {

	/**
	 * @var User_Locations_Forms The one true User_Locations_Forms
	 * @since 1.0.0
	 */
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new User_Locations_Forms;
			// Methods
			self::$instance->init();
		}
		return self::$instance;
	}

	public function init() {
		// Options page
		$this->create_options_page();
		// Dashboard settings form
		$this->create_custom_dashboard();
		// Add new location page
		$this->create_new_location_page();
		// Create custom ACF location
		$this->acf_form_location();
	}

	public function create_options_page() {
		acf_add_options_page(array(
			'page_title' 	 => 'Location Settings',
			'menu_title'	 => 'Settings',
			'menu_slug' 	 => 'location_settings',
			'capability'	 => 'edit_posts',
			'icon_url'       => 'dashicons-admin-tools',
			// 'position'       => 2,
			'redirect'		 => false
		));
	}

	public function create_custom_dashboard() {
		// Custom Dashboard
		add_action( 'admin_enqueue_scripts',  array( $this, 'dashboard_widget_header' ) );
		add_action( 'wp_dashboard_setup', 	  array( $this, 'dashboard_widgets' ), 99 );
		add_action( 'admin_head-index.php',   array( $this, 'dashboard_columns' ) );
		// Force post to published
		add_filter( 'acf/pre_save_post', array( $this, 'dashboard_form_transition_status' ) );
	}

	/**
	 * Add ACF form header function
	 *
	 * @since 	1.0.0
	 *
	 * @param  string  $hook  The current page we are viewing
	 *
	 * @return void
	 */
	public function dashboard_widget_header( $hook ) {

		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
			return;
		}

		if ( 'index.php' != $hook ) {
			return;
		}
		// ACF required
		acf_form_head();
	}

	public function dashboard_widgets() {

		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
			return;
		}

		// Remove widgets
		$this->remove_dashboard_widgets();

		$parent_id = userlocations_get_location_parent_page_id( $user_id );

		// Add our new dashboard widget
		wp_add_dashboard_widget(
			'my_location_info',
			'My Location Info',
			array( $this, 'dashboard_widget_cb' )
		);

	}

	public function remove_dashboard_widgets() {
		global $wp_meta_boxes;
		unset($wp_meta_boxes['dashboard']);
	}

	public function dashboard_widget_cb() {

		// TODO: Check if parent ID?
		$parent_id = userlocations_get_location_parent_page_id( get_current_user_id() );

		$args = array(
			// 'post_title'			=> true,
			// 'post_content'			=> true,
			'post_id'				=> $parent_id,
			'field_groups'			=> array('group_5773cc5bdf8dc'),
			'form'					=> true,
			// 'honeypot'				=> true,
			// 'uploader'			 	=> 'basic',
			// 'return'				=> $permalink, // Redirect to new/edited post url
			'html_before_fields'	=> '',
			'html_after_fields'		=> '',
			'submit_value'			=> 'Save Changes',
			'updated_message'		=> 'Changes Saved! <a href="' . get_permalink($parent_id) . '">View your page</a>.'
		);
		echo acf_form( $args );
	}

	public function dashboard_form_transition_status( $post_id ) {
		$parent_id = userlocations_get_location_parent_page_id( get_current_user_id() );
		if ( get_post_status($parent_id) != 'publish' ) {
			$post_data = array(
				'ID'			=> $post_id,
				'post_status'	=> 'publish',
			);
			wp_update_post( $post_data );
		}
		// Must return $post_id or no values will save elsewhere!!!!!
		return $post_id;
	}

	/**
	 * Force the dashboard to only show 1 column
	 *
	 * @since 	1.0.0
	 *
	 * @return	void
	 */

	public function dashboard_columns() {
		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
			return;
		}

		add_screen_option(
			'layout_columns',
			array(
				'max'     => 1,
				'default' => 1
			)
		);
	}

	public function create_new_location_page() {
		// New location form page
		add_action( 'admin_enqueue_scripts',  array( $this, 'new_location_page_header' ) );
		add_action( 'admin_menu', 			  array( $this, 'new_location_page' ) );
		// Validate username
		add_filter( 'acf/validate_value/name=submitted_location_username', array( $this, 'validate_username' ), 10, 4 );
		add_filter( 'acf/validate_value/name=submitted_location_email', array( $this, 'validate_email' ), 10, 4 );
		// Create
		add_filter( 'acf/pre_save_post', array( $this, 'maybe_create_location' ) );
	}

	/**
	 * Add ACF form header function
	 *
	 * @since 	1.0.0
	 *
	 * @param  string  $hook  The current page we are viewing
	 *
	 * @return void
	 */
	public function new_location_page_header( $hook ) {
		if ( 'toplevel_page_new_location' != $hook ) {
			return;
		}

		// ACF required
		acf_form_head();

	   // Custom form CSS
	    echo '<style type="text/css">
		    #new_location_form > h2 {
			    border-bottom: 1px solid #eee;
			}
	        #new_location_form .acf-form-submit {
	        	padding: 10px;
		    }
	        </style>';
	}

	public function new_location_page() {
		$page_title	= 'Add Location';
		$menu_title	= 'Add Location';
		$capability	= 'manage_options';
		$menu_slug	= 'new_location';
		$function	= array( $this, 'new_location_form' );
		$icon_url	= 'dashicons-plus-alt';
		$position	= '80';
		// $position	= '70';
	    $page = add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	}

	public function new_location_form() {

		$page_title		= 'Add ' . userlocations_get_default_name('singular');
		$description	= '';
		$metabox_title	= userlocations_get_default_name('singular') . ' Info';

		$this->do_single_page_metabox_form_open( $page_title, $description, $metabox_title );

			$args = array(
				'post_id'				=> 'new_location',
				'field_groups'			=> array('group_57754c7eb7661'),
				'form'					=> true,
				'honeypot'				=> true,
				'return'				=> '%post_url%', // TODO, return to user profile or something?
				'html_before_fields'	=> '',
				'html_after_fields'		=> '',
				'submit_value'			=> 'Submit',
				'updated_message'		=> 'Success!'
			);
			acf_form( $args );

		$this->do_single_page_metabox_form_close();

	}

	/**
	 * Helper function to build a single metabox page
	 *
	 * @since  1.0.0
	 *
	 * @param  string       $page_title
	 * @param  string       $description
	 * @param  string       $metabox_title
	 *
	 * @return void
	 */
	public function do_single_page_metabox_form_open( $page_title = '', $description = '', $metabox_title = '' ) {
		echo '<div class="wrap">';
			echo '<h1>' . $page_title . '</h1>';
			echo '<div id="poststuff">';
				echo '<div id="post-body" class="metabox-holder columns-1">';
	                echo '<div id="post-body-content">' . $description . '</div>';
					echo '<div id="postbox-container-1" class="postbox-container">';
						echo '<div id="new_location_form" class="postbox ">';
							echo '<h2 class=""><span>' . $metabox_title . '</span></h2>';
							echo '<div class="inside">';

	}

	public function do_single_page_metabox_form_close() {
							echo '</div>';
						echo '</div>';
					echo '</div>';
				echo '</div>';
			echo '</div>';
		echo '</div>';
	}
	public function validate_username( $valid, $value, $field, $input ) {
		if ( ! validate_username($value) ) {
			$valid = 'Not a valid username. Here is a valid version: ' . sanitize_user($value, true);
		}
		if ( username_exists($value) ) {
			$valid = 'Username exists.';
		}
		return $valid;
	}

	public function validate_email( $valid, $value, $field, $input ) {
		if ( ! is_email($value) ) {
			$valid = 'Not a valid email.';
		}
		if ( email_exists($value) ) {
			$valid = 'There is already an account registered with this email address.';
		}
		return $valid;
	}

	public function maybe_create_location( $post_id ) {

		if ( $post_id != 'new_location' ) {
			return $post_id;
		}

		// $_POST['acf']['field_57754c87578d9']  // Location Name
		// $_POST['acf']['field_57755ab77e45d']  // Slug
		// $_POST['acf']['field_57754ceb578da']  // Email
		// $_POST['acf']['field_57754dc8d7549']  // Username
		// $_POST['acf']['5field_57758cf1f6403'] // Send email

		// Replace spaces and underscores with hyphen
		$slug = str_replace( array(' ','_'), '-', $_POST['acf']['field_57755ab77e45d'] );

		$raw_data_array = array(
			'name'			=> $_POST['acf']['field_57754c87578d9'] ,
			'post_name'		=> $slug,
			'user_login'	=> $_POST['acf']['field_57754dc8d7549'],
			'user_email'	=> $_POST['acf']['field_57754ceb578da'],
			'role'			=> 'location',
			'post_status'	=> 'draft', // TODO: Handle when to change to public!
		);

		// Maybe send email (ACF true/false field type)
		if ( $_POST['acf']['field_57758cf1f6403'] ) {
			$send_email = true;
		} else {
			$send_email = false;
		}

		$location_ids = $this->create_location( $raw_data_array, $send_email );

		if ( $location_ids && ! is_wp_error( $location_ids ) ) {
			return $location_ids['page_id'];
		}

	}

	/**
	 * Create location (user), then create a location page, then maybe email the user
	 *
	 * @since  1.0.0
	 *
	 * @param  array  	$raw_data_array  Array of user and post data
	 * @param  boolean 	$email     		 Whether to email the new user or not
	 *
	 * @return array | WP_Error | false
	 */
	public function create_location( $raw_data_array, $email = false ) {

		/**
		 * $raw_data_array must contain the following keys
		 *
		 * 'user_login' // Username
		 * 'user_email' // Email
		 * 'name'		// Location name for Nickname, Display Name, and Page Title
		 */

		// Bail if no username
		if ( ! isset( $raw_data_array['user_login'] ) ) {
			return false;
		}
		// Bail if no email
		if ( ! isset( $raw_data_array['user_email'] ) ) {
			return false;
		}
		// Bail if no name
		if ( ! isset( $raw_data_array['name'] ) ) {
			return false;
		}

		/**
		 * The following code sets all of the user defaults for use in wp_insert_user()
		 *
		 * @since  1.0.0
		 *
		 * @var    array
		 */
		$user_data = array(
			'user_login' => $raw_data_array['user_login'],
			'user_email' => $raw_data_array['user_email'],
		);

		// Set password
		$user_data['user_pass'] = isset( $raw_data_array['user_pass'] ) ? $raw_data_array['user_pass'] : wp_generate_password(24);

		// Set nickname
		$user_data['nickname'] = isset( $raw_data_array['nickname'] ) ? $raw_data_array['nickname'] : $raw_data_array['name'];

		// Set display name
		$user_data['display_name'] = isset( $raw_data_array['display_name'] ) ? $raw_data_array['display_name'] : $raw_data_array['name'];

		// Set role. This is only included to future-proof the plugin. We may add 'location_manager' role later on
		$user_data['role'] = isset( $raw_data_array['role'] ) ? $raw_data_array['role'] : 'location';

		/* *************** *
		 * Create the user *
		 * *************** */

		$user_id = wp_insert_user( $user_data );

		// Return WP_Error if something went wrong
		if ( is_wp_error($user_id) ) {
			return $user_id;
		}

		/**
		 * The following code sets all of the user defaults for use in wp_insert_user()
		 *
		 * @since  1.0.0
		 *
		 * @var    array
		 */
		$post_data = array(
			'ID'		  => 0,
			'post_title'  => $raw_data_array['name'],
			'post_type'   => 'location_page',
			'post_author' => $user_id,
		);

		// Maybe set the slug
		if ( isset( $raw_data_array['post_name'] ) ) {
			$post_data['post_name'] = $raw_data_array['post_name'];
		}

		// Set post status
		$post_data['post_status'] = isset( $raw_data_array['post_status'] ) ? $raw_data_array['post_status'] : 'draft';

		// Set post content
		$post_data['post_content'] = isset( $raw_data_array['post_content'] ) ? $raw_data_array['post_content'] : '';

		/* *************** *
		 * Create the page *
		 * *************** */

		$page_id = wp_insert_post( $post_data, true );

		// Return WP_Error if something went wrong
		if ( is_wp_error($page_id) ) {
			return $page_id;
		}

		// Add page ID as user meta
		update_user_meta( $user_id, 'location_parent_id', $page_id );

		if ( $email ) {
			wp_send_new_user_notifications( $user_id );
		}

		// Success!! Return an array of new location ID's
		return array(
			'user_id' => $user_id,
			'page_id' => $page_id,
		);

	}

	public function acf_form_location() {
		// Custom 'none' location for field groups that will only be used as forms
		add_filter( 'acf/location/rule_types', 			array( $this, 'acf_none_rule_type' ) );
		add_filter( 'acf/location/rule_operators/none', array( $this, 'acf_none_rule_operator' ) );
		add_filter( 'acf/location/rule_values/none', 	array( $this, 'acf_none_location_rules_values' ) );
	}

	public function acf_none_rule_type( $choices ) {
	    $choices['None']['none'] = 'None';
	    return $choices;
	}
	public function acf_none_rule_operator( $choices ) {
		return array(
			'==' => 'is',
		);
	}
	public function acf_none_location_rules_values( $choices ) {
		return array(
			'none' => 'None',
		);
	}

}
