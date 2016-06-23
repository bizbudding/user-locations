<?php
/**
 * Parisi
 *
 * @package   Parisi
 * @author    Mike Hemberger <mike@bizbudding.com.com>
 * @link      https://github.com/JiveDig/Parisi/
 * @copyright 2016 Mike Hemberger
 * @license   GPL-2.0+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main plugin class.
 *
 * @package Parisi
 */
final class Parisi_Fields {
	/** Singleton *************************************************************/

	/**
	 * @var Parisi_Fields The one true Parisi_Fields
	 * @since 1.0.0
	 */
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new Parisi_Fields;
			// Methods
			self::$instance->init();
		}
		return self::$instance;
	}

	public function __construct() {
	}

	public function init() {
        // Add new user profile contact fields
        add_filter( 'user_contactmethods', array( $this, 'add_user_contact_methods' ), 30, 1 );
        // Options page
        $this->create_options_page();
        // Load user field values
        add_filter( 'acf/load_value', array( $this, 'load_user_fields' ), 10, 3 );
        // Save user field values
        add_filter( 'acf/update_value', array( $this, 'save_user_fields' ), 10, 3 );
    }

	public function add_user_contact_methods( $user_contact ) {
		$user_contact['twitter']          = __( 'Twitter Username (without @)', 'bizbudding' );
		$user_contact['instagram']        = __( 'Instagram Username', 'bizbudding' );
		$user_contact['youtube']          = __( 'YouTube URL', 'bizbudding' );
		$user_contact['linkedin']         = __( 'LinkedIn URL', 'bizbudding' );
		$user_contact['phone']            = __( 'Phone Number', 'bizbudding' );
		$user_contact['address_street']   = __( 'Street', 'bizbudding' );
		$user_contact['address_street_2'] = __( 'Street (2nd line)', 'bizbudding' );
		$user_contact['address_city']     = __( 'City', 'bizbudding' );
		$user_contact['address_state']    = __( 'State', 'bizbudding' );
		$user_contact['address_postcode'] = __( 'Zip Code', 'bizbudding' );
		return $user_contact;
	}

	public function create_options_page() {
		acf_add_options_page(array(
			'page_title' 	 => 'My Location',
			'menu_title'	 => 'Settings',
			'menu_slug' 	 => 'location',
			'capability'	 => 'edit_posts',
			'icon_url'       => 'dashicons-location-alt',
			'position'       => 2,
			'redirect'		 => false
		));
	}

	public function load_user_fields( $value, $post_id, $field ) {
		$data_fields = $this->get_user_data_fields();
		$meta_fields = $this->get_user_meta_fields();
		$all_fields  = array_merge($data_fields,$meta_fields);
		// Bail if not a field we want (for other field groups and forms)
		if ( ! in_array( $field['name'], $all_fields ) ) {
			return $value;
		}
		// Get current user data and meta
		$user = wp_get_current_user();
	    $meta = get_user_meta( $user->ID );
		// Update user data fields
		if ( in_array( $field['name'], $data_fields ) ) {
			return $user->$field['name'];
		}
		// Update user meta fields
		if ( in_array( $field['name'], $meta_fields ) ) {
			return $meta[$field['name']][0];
		}
		// Just incase we missed something
		return $value;
	}

	public function save_user_fields( $value, $post_id, $field ) {
		$data_fields    = $this->get_user_data_fields();
		$meta_fields    = $this->get_user_meta_fields();
		$all_fields     = array_merge($data_fields,$meta_fields);
		// Bail if not a field we want (for other field groups and forms)
		if ( ! in_array( $field['name'], $all_fields ) ) {
			return $value;
		}
		// Get current user data and meta
		$user = wp_get_current_user();
		$meta = get_user_meta( $user->ID );
		// Update user data fields
		if ( in_array( $field['name'], $data_fields ) ) {
			// Set the user data
			$user_data = array(
				'ID' 	       => $user->ID,
				$field['name'] => $value,
			);
			// Special exception for nickname to also save as display_name
			if ( $field['name'] == 'nickname' ) {
				$user_data = array(
					'ID' 	       => $user->ID,
					'nickname'     => $value,
					'display_name' => $value,
				);
			}
			// Update user
			wp_update_user( $user_data );
		}
		// Update user meta fields
		if ( in_array( $field['name'], $meta_fields ) ) {
			update_user_meta( $user->ID, $field['name'], $value );
		}
		// Save empty data since the form doesn't save data where we need it to on its own
		return '';
	}

	/**
	 * Get user data fields
	 * Some of these are actually meta, but work with wp_update_user
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_user_data_fields() {
		return array(
			'display_name',
			'user_email',
			'first_name',
			'last_name',
			'nickname',
			'description',
			'user_url',
		);
	}

	/**
	 * Get user meta fields
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_user_meta_fields() {
		return array(
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

}
