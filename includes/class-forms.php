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
		// Admin form header
		add_action( 'admin_enqueue_scripts',  array( $this, 'admin_form_header' ) );
		// Manage locations forms
		add_action( 'admin_menu', array( $this, 'manage_location_forms' ) );
		add_filter( 'acf/pre_save_post', array( $this, 'manage_location_form_process' ) );
		// $this->create_custom_dashboard();
		// Add settings page
		$this->create_location_settings_page();
		// Add new location page
		$this->create_new_location_page();
		// Create custom ACF location
		$this->acf_form_location();
	}

	/**
	 * Add ACF form header to all location info form pages
	 *
	 * @since 	1.0.0
	 *
	 * @param  string  $hook  The current page we are viewing
	 *
	 * @return void
	 */
	public function admin_form_header( $hook ) {
		if ( strpos($hook, 'location-info_page_') !== false ) {
			// ACF required
			acf_form_head();
		}
	}

	public function manage_location_forms() {

		$this->locations_admin_page();

		$user_id = get_current_user_id();
		$args = array(
			'orderby'          => 'title',
			'order'            => 'ASC',
			'post_type'        => 'location_page',
			'post_parent'      => 0,
			'post_status'      => 'all',
			'suppress_filters' => true,
		);
		if ( ul_is_location_role($user_id) ) {
			$args['author'] = $user_id;
		}
		$pages = get_posts( $args );

		foreach ( $pages as $page ) {
			$page_title	= $page->post_title;
			$menu_title	= $page->post_title;
			$capability	= 'edit_posts';
			$menu_slug	= $page->ID;
			$function	= array( $this, 'location_form' );
		    add_submenu_page( 'location_info', $page_title, $menu_title, $capability, $menu_slug, $function );
		}

		/**
		 * Remove main menu page's auto-created subpage
		 * This also forces a redirect to the first item in the list
		 */
		remove_submenu_page( 'location_info', 'location_info' );

	}

	public function locations_admin_page() {
		$page_title	= ul_get_default_name('singular') . ' Info';
		$menu_title	= ul_get_default_name('singular') . ' Info';
		$capability	= 'edit_posts';
		$menu_slug	= 'location_info';
		$function	= array( $this, 'location_info' );
		$icon_url	= 'dashicons-location-alt';
		$position	= '2';
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	}

	public function location_form() {

		$page_id = isset($_GET['page']) ? absint($_GET['page']) : '';
		if ( empty($page_id) ) {
			return;
		}

		$page = get_post($page_id);

		$this->do_single_page_metabox_form_open( $page->post_title, '', $page->post_title );

			if ( get_post_status($page_id) != 'publish' ) {
			    echo '<div id="message" class="notice notice-error">';
				    echo '<p>This page is not public yet. Once you save changes this page will go live.</p>';
			    echo '</div>';
			}

			$args = array(
				// 'id'					=> 'ul-form-' . $page_id,
				'post_id'				=> $page_id,
				'field_groups'			=> array('group_5773cc5bdf8dc'),
				// 'post_title'			=> true,
				'form'					=> true,
				'honeypot'				=> true,
				'html_before_fields'	=> '<input type="hidden" name="dashboard_form_location_id" value="' . $page_id . '">',
				'html_after_fields' 	=> '',
				'submit_value'			=> 'Save Changes',
				'updated_message'		=> 'Changes Saved! <a href="' . get_permalink($page_id) . '">View your page</a>.'
			);
			acf_form( $args );

		$this->do_single_page_metabox_form_close();

	}

	public function manage_location_form_process( $page_id ) {
		if ( ! isset($_POST['dashboard_form_location_id']) || $_POST['_location_info_form'] != $page_id ) {
			return $page_id;
		}
		// Bail if already published
		if ( get_post_status($page_id) == 'publish' ) {
			return $page_id;
		}
		// Take them live!
		$post_data = array(
			'ID'			=> $page_id,
			'post_status'	=> 'publish',
		);
		wp_update_post( $post_data );

		// Allow the user to create posts now!
		$user = new WP_User( $user_id );
		$user->add_cap( 'create_posts' );

		// Hook for developers to run other code after a location page is made public
		do_action( 'ul_location_page_published', $post_id, $user_id );
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
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}
		if ( 'index.php' != $hook ) {
			return;
		}
		// ACF required
		acf_form_head();
	}

	/**
	 * Transition parent page status to publish after first dashboard form save
	 *
	 * @param  int  $post_id the ID used in post_id param of acf_form $args (This is the parent page ID)
	 *
	 * @return int  The post id
	 */
	public function location_info_form_process( $post_id ) {

		// Bail if hidden field is not set or doesn't equal 1
		if ( ! isset($_POST['_location_info_form']) || $_POST['_location_info_form'] != '1' ) {
			return $post_id;
		}
		$user_id = $status = false;
		// If Dashboard, get the current user ID
		if ( ul_is_dashboard() ) {
			$user_id = get_current_user_id();
			$status  = ul_get_location_parent_page_status( $user_id );
		}
		/**
		 * If we're editing the actual location page, set the ID as the post author
		 * This allows non 'location' role users to edit the page on behalf
		 */
		global $pagenow, $typenow;
		if ( $pagenow = 'post.php' && $typenow == 'location_page' ) {
			global $post;
			$user_id = $post->post_author;
			$status  = $post->post_status;
		}

		trace('User ID: ' . $user_id . '<br />');
		trace('Status: ' . $status . '<br />');

		// Bail if we don't have a user ID or post status
		if ( ! $user_id || ! $status ) {
			// Don't return anything so it doesn't get saved in options table
			return '';
		}

		if ( $status != 'publish' ) {

			// Take them live!
			$post_data = array(
				'ID'			=> $post_id,
				'post_status'	=> 'publish',
			);
			wp_update_post( $post_data );

			// Allow the user to create posts now!
			$user = new WP_User( $user_id );
			$user->add_cap( 'create_posts' );

			/**
			 * Add page ID as user meta
			 * If location user was added via Add Location form, this is redundant, but we'll do it again to confirm it's there.
			 * This helps when importing users via WP All Import Pro or other
			 */
			update_user_meta( $user_id, 'location_parent_id', $post_id );

			// Hook for developers to run other code after a location page is made public
			do_action( 'ul_location_page_published', $post_id, $user_id );

		}
		// Must return $post_id so ACF fields still save correctly to the post
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
		if ( ! ul_is_location_role( $user_id ) ) {
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

	public function create_location_settings_page() {
		add_action( 'admin_menu', 		 array( $this, 'location_settings_page' ) );
		add_filter( 'acf/pre_save_post', array( $this, 'update_location_settings' ) );
	}

	public function location_settings_page() {
		$page_title	= ul_get_default_name('singular') . ' Settings';
		$menu_title	= 'Settings';
		$capability	= 'edit_posts';
		$menu_slug	= 'location_settings';
		$function	= array( $this, 'location_settings_form' );
		$icon_url	= 'dashicons-admin-tools';
		$position	= '76';
	    $page = add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	}

	public function location_settings_form() {

		$page_title		= 'Add ' . ul_get_default_name('singular');
		$description	= '';
		$metabox_title	= 'Add ' . ul_get_default_name('singular');

		$this->do_single_page_metabox_form_open( $page_title, $description, $metabox_title );

			$args = array(
				'post_id'				=> 'update_location_user',
				'field_groups'			=> array('group_577402c6deded'),
				'form'					=> true,
				'honeypot'				=> true,
				// 'return'				=> '',
				'html_before_fields'	=> '',
				'html_after_fields'		=> '',
				'submit_value'			=> 'Save Settings',
				'updated_message'		=> 'Updated!'
			);
			acf_form( $args );

		$this->do_single_page_metabox_form_close();

	}

	public function update_location_settings( $post_id ) {
		// Bail if not the form we want
		if ( $post_id != 'update_location_user' ) {
			return $post_id;
		}
		// Return no data. Everything is handled in class-fields.php by field name
		return '';

	}

	public function create_new_location_page() {
		// New location form page
		add_action( 'admin_menu', array( $this, 'new_location_page' ) );
		// Validate username
		add_filter( 'acf/validate_value/name=submitted_location_username',  array( $this, 'validate_username' ), 10, 4 );
		add_filter( 'acf/validate_value/name=submitted_location_email', 	array( $this, 'validate_email' ), 10, 4 );
		// Create
		add_filter( 'acf/pre_save_post', array( $this, 'maybe_create_location' ) );
	}

	public function new_location_page() {
		$page_title	= 'Add ' . ul_get_default_name('singular');
		$menu_title	= 'Add New ' . ul_get_default_name('singular');
		$capability	= 'manage_options';
		$menu_slug	= 'new_location';
		$function	= array( $this, 'new_location_form' );
		$icon_url	= 'dashicons-plus-alt';
		$position	= '80';
		// $position	= '70';
	    // $page = add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	    add_users_page( $page_title, $menu_title, $capability, $menu_slug, $function );
	}

	public function new_location_form() {

		$page_title		= 'Add ' . ul_get_default_name('singular');
		$description	= '';
		$metabox_title	= ul_get_default_name('singular') . ' Info';

		$this->do_single_page_metabox_form_open( $page_title, $description, $metabox_title );

			$args = array(
				'post_id'				=> 'new_location',
				'field_groups'			=> array('group_57754c7eb7661'),
				'form'					=> true,
				'honeypot'				=> true,
				'return'				=> '', // Returns based off ID returned in acf_form()
				'html_before_fields'	=> '',
				'html_after_fields'		=> '',
				'submit_value'			=> 'Create New ' . ul_get_default_name('singular'),
				'updated_message'		=> 'Success!'
			);
			acf_form( $args );

		$this->do_single_page_metabox_form_close();

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
			'post_status'	=> 'draft', // This changes to public after user saves Location Form for the first time!
		);

		// Maybe send email (ACF true/false field type)
		if ( $_POST['acf']['field_57758cf1f6403'] ) {
			$send_email = true;
		} else {
			$send_email = false;
		}

		$location_ids = $this->create_location( $raw_data_array, $send_email );

		// Don't return anything, everything is saved via create_location()
		return '';
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
			// 'ID'		  => 0,
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

		// Success!! Return an array of new location ID's (currently not using this)
		return array(
			'user_id' => $user_id,
			'page_id' => $page_id,
		);

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
			echo '<div id="poststuff" class="ul-admin-form">';
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
