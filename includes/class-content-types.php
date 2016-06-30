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

		add_action( 'get_header', 				array( $this, 'remove_meta' ) );
		// Filters
		// add_filter( 'post_type_link', 			array( $this, 'post_type_link' ), 10, 4 );
		// add_filter( 'wpseo_breadcrumb_links', 	array( $this, 'author_in_breadcrumbs' ), 10, 1 );
		// add_filter( 'wp_get_nav_menu_items', 	array( $this, 'location_menu_items' ), 10, 3 );
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
			'quick_edit'		  => current_user_can('manage_options'),
			// 'show_in_nav_menus'   => true,
			'show_ui'             => true,
		    'has_archive'         => false,
			'supports' 	          => array( 'title', 'editor', 'author', 'thumbnail', 'page-attributes' ),
			// 'supports' 	          => array( 'title', 'editor', 'author', 'thumbnail' ),
			'capability_type'	  => 'post',
			// 'rewrite' 			  =>  array( 'slug' => '/' . sanitize_title_with_dashes( User_Locations()->get_default_name('slug') ) . '/%author%' ),
			'rewrite' 			  =>  array( 'slug' => sanitize_title_with_dashes( User_Locations()->get_default_name('slug') ) ),
			// 'rewrite' 			  =>  array( 'slug' => '/%location_rewrite%' ),
			// 'admin_cols' => array(
		 //        'featured_image' => array(
		 //            'title'          => 'Image',
		 //            'featured_image' => 'thumbnail',
		 //        ),
			// ),
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
		register_extended_taxonomy( 'location_type', 'user' );
		// register_extended_taxonomy( 'user_type', 'user' );
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
		$location_parent_id = userlocations_get_location_parent_page_id( $postarr['post_author'] );
		if ( ! $location_parent_id ) {
			$location_parent_id = wp_insert_post( $args );
			// Add page ID as user meta
			update_user_meta( $user_id, 'location_parent_id', (int)$location_parent_id );
		}
	}

	public function location_page_parents( $dropdown_args, $post ) {
		if ( $post->post_type != 'location_page' ) {
			return $dropdown_args;
		}
		$dropdown_args['depth'] = '1';
		if ( in_array('location', wp_get_current_user()->roles) ) {
			$dropdown_args['show_option_none'] = '';
			if ( ! empty( $post->post_author ) ) {
				$dropdown_args['authors']  = (string)$post->post_author;
			} else {
				$dropdown_args['authors']  = (string)get_current_user_id();
			}
		}
		return $dropdown_args;
	}

	public function get_location_parent_page_id( $user_id ) {
		$parent_id = get_user_meta( $user_id, 'location_parent_id', true );
		return ! empty( $parent_id ) ? (int)$parent_id : false;
	}

	/**
	 * Remove post info and meta on location pages
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function remove_meta() {
		if ( ! is_singular('location_page') ) {
			return;
		}
		remove_action( 'genesis_entry_header', 'genesis_post_info', 12 );
		remove_action( 'genesis_entry_footer', 'genesis_entry_footer_markup_open', 5 );
		remove_action( 'genesis_entry_footer', 'genesis_entry_footer_markup_close', 15 );
		remove_action( 'genesis_entry_footer', 'genesis_post_meta' );
	}

	/**
	 * Alter the location_page post type url
	 *
	 * @see		http://wordpress.stackexchange.com/questions/73228/display-posts-with-author-in-the-url-with-custom-post-types
	 *
	 * @param   url	    $post_link	The post URL
	 * @param   object  $post		The post object
	 * @param   bool    $leavename  Whether to keep the post name
	 * @param   bool    $sample     Is it a sample permalink?
	 *
	 * @return  url
	 */
	public function post_type_link( $post_link, $post, $leavename, $sample ){
		// Bail if not a location_page post
	    if ( 'location_page' != get_post_type($post) ) {
	        return $post_link;
		}
		if ( $post->post_parent == 0 ) {
			$rewrite = 'redirect';
		} else {
			$rewrite = sanitize_title_with_dashes( User_Locations()->get_default_name('slug') );
		}
	    $post_link  = str_replace('%location_rewrite%', $rewrite, $post_link);
	    return $post_link;
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

	/**
	 * ARE WE USING THIS RIGHT NOW!?!?!?!?
	 *
	 * Submenu items in secondary menu
	 *
	 * Assign the same menu to 'header' and 'secondary'.
	 * This will display the current section's subpages in 'secondary'
	 *
	 * @author Bill Erickson
	 * @link http://www.billerickson.net/building-dynamic-secondary-menu
	 *
	 * @param array $menu_items, menu items in this menu
	 * @param array $args, arguments passed to wp_nav_menu()
	 * @return array $menu_items
	 *
	 */
	public function location_menu_items( $items, $menu, $args ) {

		if ( $menu->slug != 'location-menu' ) {
			return $items;
		}

		$items = array();

		$args = array(
			'post_type'        => 'location_page',
			'post_status'      => 'publish',
			// 'post_parent'	   => 0,
			'suppress_filters' => true
		);
		$pages = get_posts( $args );

		$new_items_data = array();
		$menu_order     = count( $items ) + 1;
		foreach ( $pages as $page ) {
			$new_item                   = new stdClass();
			$new_item->menu_item_parent = 0;
			$new_item->url              = get_permalink( $page->ID );
			$new_item->title            = get_the_title( $page->ID );
			$new_item->menu_order       = $menu_order;
			$new_item->type				= '';
			$items[]                    = $new_item;
			$menu_order++;
		}

		return $items;

	}

}
