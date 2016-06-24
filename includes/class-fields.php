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
final class User_Locations_Fields {

	/**
	 * @var User_Locations_Fields The one true User_Locations_Fields
	 * @since 1.0.0
	 */
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new User_Locations_Fields;
			// Methods
			self::$instance->init();
		}
		return self::$instance;
	}

	public function init() {
    	// Add new user profile contact fields
        add_filter( 'user_contactmethods', array( $this, 'add_user_contact_methods' ), 30, 1 );
        // Load user field values
        add_filter( 'acf/load_value', array( $this, 'load_user_fields' ), 10, 3 );
        // Save user field values
        add_filter( 'acf/update_value', array( $this, 'save_user_fields' ), 10, 3 );
		// Disable default saving
		add_action( 'acf/save_post', array( $this, 'disable_default_save' ), 1 );
	}

	public function add_user_contact_methods( $user_contact ) {
		$user_contact['twitter']          = __( 'Twitter Username (without @)', 'user-locations' );
		$user_contact['instagram']        = __( 'Instagram Username', 'user-locations' );
		$user_contact['youtube']          = __( 'YouTube URL', 'user-locations' );
		$user_contact['linkedin']         = __( 'LinkedIn URL', 'user-locations' );
		$user_contact['phone']            = __( 'Phone Number', 'user-locations' );
		$user_contact['address_street']   = __( 'Street', 'user-locations' );
		$user_contact['address_street_2'] = __( 'Street (2nd line)', 'user-locations' );
		$user_contact['address_city']     = __( 'City', 'user-locations' );
		$user_contact['address_state']    = __( 'State', 'user-locations' );
		$user_contact['address_postcode'] = __( 'Zip Code', 'user-locations' );
		return $user_contact;
	}

	public function load_user_fields( $value, $post_id, $field ) {
		// Get all fields
		$all_fields = $this->get_all_fields_grouped();
		// Bail if not a field we want (for other field groups and forms)
		if ( ! in_array( $field['name'], $this->get_all_fields() ) ) {
			return $value;
		}
		$data_fields  = $all_fields['data'];
		$tax_fields	  = $all_fields['tax'];
		$meta_fields  = $all_fields['meta'];

		// Get current user data and meta
		$data = wp_get_current_user();
		$tax  = get_object_taxonomies( 'user', 'names' );
	    $meta = get_user_meta( $data->ID );

		// Load user tax fields
		if ( in_array( $field['name'], $data_fields ) ) {
			return $data->$field['name'];
		}

		// Load user data fields
		if ( in_array( $field['name'], $tax_fields ) ) {
			// $terms = get_the_terms( $data->ID, $field['name'] );
			$terms = wp_get_object_terms( $data->ID, $field['name'], array( 'fields' => 'names' ) );
			if ( $terms ) {
				return $terms[0];
			}
		}

		// Load user meta fields
		if ( in_array( $field['name'], $meta_fields ) ) {
			return $meta[$field['name']][0];
		}

		// Just incase we missed something
		return $value;
	}

	public function save_user_fields( $value, $post_id, $field ) {
		// Sanitize fields
		$this->sanitize_field($value);
		// Get all fields
		$all_fields = $this->get_all_fields_grouped();
		// Bail if not a field we want (for other field groups and forms)
		if ( ! in_array( $field['name'], $this->get_all_fields() ) ) {
			return $value;
		}
		$data_fields  = $all_fields['data'];
		$tax_fields	  = $all_fields['tax'];
		$meta_fields  = $all_fields['meta'];

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
			wp_update_user( $user_data );
		}

		// Update user tax fields
		if ( in_array( $field['name'], $tax_fields ) ) {
			wp_set_object_terms( $user->ID, $value, $field['name'], false );
		}

		// Update user meta fields
		if ( in_array( $field['name'], $meta_fields ) ) {
			update_user_meta( $user->ID, $field['name'], $value );
		}
		// Save empty data since the form doesn't save data where we need it to on its own
		return '';
	}

	public function sanitize_field( $value ) {
		if ( is_array($value) ) {
			return array_map('sanitize_fields', $value);
		}
		return wp_kses_post( $value );
	}

	public function disable_default_save( $post_id ) {
		// bail early if no ACF data
		if ( empty($_POST['acf']) ) {
			return;
		}
		$field_group_key = isset($_POST['acf']['field_576c3d1b190dc']) ? $_POST['acf']['field_576c3d1b190dc'] : '';
		// If hidden field value is the group field ID, set the $post_id to null so nothing gets saved
		if ( $field_group_key === 'group_57699cab27e89' ) {
			$post_id = null;
		}
	}

	public function get_all_fields() {
		$fields = $this->get_all_fields_grouped();
		$arrays = array(
			$fields['data'],
			$fields['tax'],
			$fields['meta'],
		);
		return call_user_func_array( 'array_merge', $arrays );
	}

	public function get_all_fields_grouped() {
		return array(
			'data' => $this->get_user_data_fields(),
			'tax'  => $this->get_user_taxonomy_fields(),
			'meta' => $this->get_user_meta_fields(),
		);
	}

	/**
	 * Get user data fields
	 * Some of these are actually meta, but work with wp_update_user
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_user_taxonomy_fields() {
		return array(
			'location_type',
			'user_type',
		);
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

	/**
	 * Get fields to be hidden
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_hidden_field_names() {
		return array(
			'_field_group_id',
		);
	}

}
