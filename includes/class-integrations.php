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
final class User_Locations_Integrations {

	/**
	 * @var User_Locations_Integrations The one true User_Locations_Integrations
	 * @since 1.3.0
	 */
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new User_Locations_Integrations;
			// Methods
			self::$instance->init();
		}
		return self::$instance;
	}

	public function init() {
		// Gravity Forms
		add_action( 'admin_init', array( $this, 'remove_gravity_forms_button' ) );
		// Yoast
		add_filter( 'wpseo_breadcrumb_links', array( $this, 'author_in_breadcrumbs' ), 10, 1 );
		add_filter( 'wpseo_use_page_analysis', '__return_false' ); 		  // Remove admin archive filter posts by SEO
		add_filter( 'wpseo_metabox_prio', function() { return 'low'; } ); // Lower the priority of Yoast SEO metabox
		// Simple Page Ordering
		add_filter( 'simple_page_ordering_edit_rights', array( $this, 'allow_locations_to_sort_posts' ), 10, 2 );
	}

	/**
	 * Remove the 'add gravity form' button for locations when editing a page
	 *
	 * @since  1.2.3
	 */
	public function remove_gravity_forms_button() {
		if ( ! ul_user_is_location( get_current_user_id() ) ) {
			return;
		}
		// Remove gravity form button
		add_filter( 'gform_display_add_form_button', '__return_false' );
	}

	/**
	 * Filter Yoast breadcrumbs with Location data
	 *
	 * @link   https://gist.github.com/QROkes/62e07eb167089c366ab9
	 *
	 * @since  1.1.10
	 *
	 * @return array
	 */
	public function author_in_breadcrumbs( $links ) {
		if ( ! ul_is_location_content() ) {
			return $links;
		}
		// Get the location parent ID from the post ID
		$parent_id = ul_get_location_parent_page_id_from_post_id( get_the_ID() );

		// Change the 'Home' link in the breadcrumbs
		// if ( $parent_id ) {
		// 	$links[0]['url'] = get_permalink($parent_id);
		// }

		// If on a single post, set the /Blog/ to the location parent
		if ( $parent_id && is_singular('post') ) {
			$links[1]['id'] = $parent_id;
		}
	    return $links;
	}

	/**
	 * Allow locations to sort their pages
	 *
	 * @since  1.2.3
	 */
	public function allow_locations_to_sort_posts( $cap, $post_type ) {
	    if ( 'location_page' != $post_type ) {
		    return $cap;
	    }
        return current_user_can( 'edit_posts' );
	}

}
