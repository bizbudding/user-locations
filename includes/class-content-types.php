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
final class User_Locations_Content_Types {

	/**
	 * @var User_Locations_Content_Types The one true User_Locations_Content_Types
	 * @since 1.0.0
	 */
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new User_Locations_Content_Types;
			// Methods
			self::$instance->init();
		}
		return self::$instance;
	}

	public function __construct() {
	}

	public function init() {
		// Actions
		add_action( 'init', 		 						array( $this, 'register_post_types'), 0 );
		add_action( 'init', 		 						array( $this, 'register_taxonomies'), 0 );
		// add_action( 'set_user_role', 						array( $this, 'create_location_parent_page' ), 10, 3 );

		// Filters
		add_filter( 'genesis_post_info', 	array( $this, 'remove_post_info' ), 99 );
		// add_filter( 'wp_insert_post_data',  array( $this, 'set_location_parent_page_data' ), 99, 2 );
		// add_filter( 'wpseo_breadcrumb_links', 	array( $this, 'author_in_breadcrumbs' ), 10, 1 );
	}

	/**
	 * Register custom post types
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function register_post_types() {
		// Programs
	    register_extended_post_type( 'location_page', array(
			'enter_title_here'    => 'Enter Page Name',
			'menu_icon'		      => 'dashicons-admin-page',
			'exclude_from_search' => true,
			'hierarchical'		  => true,
			'menu_position'		  => 3,
			'quick_edit'		  => current_user_can('manage_options'),
			'show_ui'             => true,
		    'has_archive'         => false,
			'supports' 	          => array( 'title', 'editor', 'author', 'thumbnail', 'page-attributes', 'publicize' ),
			'capability_type'	  => 'post',
			// 'map_meta_cap' 		  => true,
			'rewrite' 			  =>  array( 'slug' => sanitize_title_with_dashes( User_Locations()->get_default_name('slug') ) ),
	    ), array(
	        'singular' => current_user_can('manage_options') ? 'Location Page' : 'Page',
	        'plural'   => current_user_can('manage_options') ? 'Location Pages' : 'Pages',
	    ) );
	}

	/**
	 * Register custom taxonomies
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function register_taxonomies() {
		// The type of 'business' a location may be
		register_extended_taxonomy( 'location_type', 'location_page', array(
			'public'  => false,
			'show_ui' => false,
		) );
		// Used for custom page templates
		register_extended_taxonomy( 'location_page_template', 'location_page', array(
			'public'  => false,
			'show_ui' => true,
			'meta_box' => 'radio',
		), array(
			'singular' => __( 'Page Template', 'user-locations' ),
			'plural'   => __( 'Page Templates', 'user-locations' ),
		) );
	}

	/**
	 * Create a top level location page for every user with a role of 'location'
	 *
	 * @param  [type] $user_id   [description]
	 * @param  [type] $role      [description]
	 * @param  [type] $old_roles [description]
	 *
	 * @return [type]            [description]
	 */
	public function create_location_parent_page( $user_id, $role, $old_roles ) {
		if ( $role != 'location' ) {
			return;
		}
		$user = get_user_by( 'ID', $user_id );
		$args = array(
			'post_type'		=> 'location_page',
			'post_status'   => 'publish',
			'post_title'    => $user->user_nicename,
			'post_name'		=> $user->display_name,
			'post_content'  => '',
			'post_author'   => $user_id,
		);
		// $location_parent_page = get_page_by_path( $user->user_nicename, OBJECT, 'location_page' );
		$location_parent_id = ul_get_location_parent_page_id( $postarr['post_author'] );
		if ( ! $location_parent_id ) {
			$location_parent_id = wp_insert_post( $args );
			// Add page ID as user meta
			update_user_meta( $user_id, 'location_parent_id', (int)$location_parent_id );
		}
	}

	/**
	 * Hijack the post data before it's saved to the database and auto-set the page as a child of the users parent page ID
	 *
	 * @since   1.0.0
	 *
	 * @param   array  $data
	 * @param   array  $postarr
	 *
	 * @return  array
	 */
	// public function set_location_parent_page_data( $data , $postarr ) {
	// 	if ( $postarr['post_type'] != 'location_page' ) {
	// 		return $data;
	// 	}
	// 	// Get the location parent page
	// 	$location_parent_page = ul_get_location_parent_page_id( $postarr['post_author'] );
	// 	// Bail if saving the parent page
	// 	if ( $location_parent_page == $postarr['ID'] ) {
	// 		return $data;
	// 	}
	// 	$data['post_parent'] = $location_parent_page;
	// 	return $data;
	// }

	public function get_location_parent_page_id( $user_id = '' ) {
		if ( empty($user_id) ) {
			$user_id = get_the_author_meta('ID');
		}
		$parent_id = get_user_meta( $user_id, 'location_parent_id', true );
		return ! empty( $parent_id ) ? (int)$parent_id : false;
	}

	/**
	 * Remove post info from location pages
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function remove_post_info( $post_info ) {
		if ( is_singular('location_page') ) {
			$post_info = '';
		}
		return $post_info;
	}

	// https://gist.github.com/QROkes/62e07eb167089c366ab9
	public function author_in_breadcrumbs( $links ) {
		if ( ! is_singular( array( 'post', 'location_page' ) ) ) {
			return $links;
		}
		// $author = get_user_by( 'slug', get_query_var( 'author_name' ) );
		$author_id = get_the_author_meta('ID');
	    $new[]  = array(
	        'url'  => get_author_posts_url( $author_id ),
	        'text' => get_the_author(),
	    );
	    // Remove middle item and add our new one in its place
	    array_splice( $links, 1, -1, $new );
	    return $links;
	}

}
