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
		add_action( 'init', 		array( $this, 'register_post_types'), 0 );
		add_action( 'init', 		array( $this, 'register_taxonomies'), 0 );
		add_action( 'get_header', 	array( $this, 'remove_meta' ) );
		// Filters
		add_filter( 'post_type_link', 			array( $this, 'post_type_link' ), 10, 4 );
		add_filter( 'wpseo_breadcrumb_links', 	array( $this, 'author_in_breadcrumbs' ), 10, 1 );
		add_filter( 'wp_get_nav_menu_items', 	array( $this, 'location_menu_items' ), 10, 3 );
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
			'show_in_nav_menus'   => true,
			'show_ui'             => true,
		    'has_archive'         => false,
			'supports' 	          => array( 'title', 'editor', 'author' ),
			'capability_type'	  => 'post',
			'rewrite' 			  =>  array( 'slug' => '/locations/%author%' ),
	    ), array(
	        'singular' => 'Page',
	        'plural'   => 'Pages',
	        'slug'     => 'user_pages'
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
		register_extended_taxonomy( 'user_type', 'user' );
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
	    $authordata = get_userdata($post->post_author);
	    $author     = $authordata->user_nicename;
	    $post_link  = str_replace('%author%', $author, $post_link);
	    return $post_link;
	}

	// https://gist.github.com/QROkes/62e07eb167089c366ab9
	public function author_in_breadcrumbs( $links ) {
		// Bail if a location page
		if ( ! is_singular('location_page') ) {
			return $links;
		}
		$author = get_user_by( 'slug', get_query_var( 'author_name' ) );
	    $new[]  = array(
	        'url'  => get_author_posts_url( $author->ID ),
	        'text' => $author->display_name,
	    );
	    // Remove middle item and add our new one in its place
	    array_splice( $links, 1, -1, $new );
	    return $links;
	}

	/**
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

		// trace($menu->slug);

		if ( $menu->slug != 'location-menu' ) {
			return $items;
		}

		// $child_items = array();
		// foreach ( $items as &$item ) {
		// 	if ( $item->object != 'location_page' ) {
		// 		continue;
		// 	}
		// 	$item->url = get_post_type_archive_link( $item->type );
		//
		// 	/* retrieve all children */
		// 	foreach ( get_posts( 'post_type='.$item->type.'&numberposts=-1' ) as $post ) {
		// 		/* hydrate with menu-specific information */
		// 		$post->menu_item_parent = $item->ID;
		// 		$post->post_type = 'nav_menu_item';
		// 		$post->object = 'custom';
		// 		$post->type = 'custom';
		// 		$post->menu_order = ++$menu_order;
		// 		$post->title = $post->post_title;
		// 		$post->url = get_permalink( $post->ID );
		// 		/* add as a child */
		// 		$child_items []= $post;
		// 	}
		// }

		$items = array();

		$args = array(
			'post_type'        => 'location_page',
			'post_status'      => 'publish',
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

		// $new_items_data = array(
		// 	array(
		// 		'url' => trailingslashit( bp_loggedin_user_domain() . bp_get_activity_slug() ),
		// 		'title' => 'Personal',
		// 	),
		// 	array(
		// 		'url' => trailingslashit( bp_loggedin_user_domain() . bp_get_activity_slug() . '/' . bp_get_groups_slug() ),
		// 		'title' => 'Groups',
		// 	),
		// 	array(
		// 		'url' => trailingslashit( bp_get_root_domain() . '/' . bp_get_activity_root_slug() ),
		// 		'title' => 'Sitewide',
		// 	),
		// );

		// $menu_order = count( $items ) + 1;
		//
		// foreach ( $new_items_data as $new_item_data ) {
		// 	$new_item                   = new stdClass;
		// 	// $new_item->menu_item_parent = $activity_menu_item;
		// 	$new_item->url              = $new_item_data['url'];
		// 	$new_item->title            = $new_item_data['title'];
		// 	// $new_item->menu_order       = $menu_order;
		// 	$items[]                    = $new_item;
		// 	// $menu_order++;
		// }

		return $items;

		// Find active section
		// $active_section = false;
		// foreach( $menu_items as $menu_item ) {
		// 	if( ! $menu_item->menu_item_parent && array_intersect( array( 'current-menu-item', 'current-menu-ancestor' ), $menu_item->classes ) )
		// 		$active_section = $menu_item->ID;
		// }
		// if( ! $active_section )
		// 	return false;
		// // Gather all menu items in this section
		// $sub_menu = array();
		// $section_ids = array( $active_section );
		// foreach( $menu_items as $menu_item ) {
		// 	if( in_array( $menu_item->menu_item_parent, $section_ids ) ) {
		// 		$sub_menu[] = $menu_item;
		// 		$section_ids[] = $menu_item->ID;
		// 	}
		// }
		// return $sub_menu;
	}

	function bbg_activity_subnav( $items, $menu, $args ) {
		// Find the Activity item
		$bp_pages = bp_core_get_directory_page_ids();
		if ( isset( $bp_pages['activity'] ) ) {
			$activity_directory_page = $bp_pages['activity'];
		} else {
			return $items;
		}

		$activity_menu_item = 0;
		foreach ( $items as $item ) {
			if ( $activity_directory_page == $item->object_id ) {
				$activity_menu_item = $item->ID;
			}
		}

		if ( is_user_logged_in() ) {
			$new_items_data = array(
				array(
					'url' => trailingslashit( bp_loggedin_user_domain() . bp_get_activity_slug() ),
					'title' => 'Personal',
				),
				array(
					'url' => trailingslashit( bp_loggedin_user_domain() . bp_get_activity_slug() . '/' . bp_get_groups_slug() ),
					'title' => 'Groups',
				),
				array(
					'url' => trailingslashit( bp_get_root_domain() . '/' . bp_get_activity_root_slug() ),
					'title' => 'Sitewide',
				),
			);

			$menu_order = count( $items ) + 1;

			foreach ( $new_items_data as $new_item_data ) {
				$new_item = new stdClass;
				$new_item->menu_item_parent = $activity_menu_item;
				$new_item->url = $new_item_data['url'];
				$new_item->title = $new_item_data['title'];
				$new_item->menu_order = $menu_order;
				$items[] = $new_item;
				$menu_order++;
			}
		}

		return $items;
	}

}
