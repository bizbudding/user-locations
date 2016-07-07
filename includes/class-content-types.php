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
		add_action( 'init', 		 		array( $this, 'register_post_types'), 0 );
		add_action( 'init', 		 		array( $this, 'register_taxonomies'), 0 );
		add_action( 'genesis_before_loop',  array( $this, 'maybe_remove_meta' ) );
		// Filters
		// add_filter( 'genesis_post_info', 	array( $this, 'maybe_remove_post_info' ), 99 );
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
	        'singular' => current_user_can('edit_others_posts') ? 'Location Page' : 'Page',
	        'plural'   => current_user_can('edit_others_posts') ? 'Location Pages' : 'Pages',
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
	 * Remove post meta from location pages
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function maybe_remove_meta() {
		if ( ! is_singular('location_page') ) {
			return;
		}
		remove_action( 'genesis_entry_header', 'genesis_post_info', 12 );
		remove_action( 'genesis_entry_footer', 'genesis_entry_footer_markup_open', 5 );
		remove_action( 'genesis_entry_footer', 'genesis_entry_footer_markup_close', 15 );
		remove_action( 'genesis_entry_footer', 'genesis_post_meta' );
	}

	/**
	 * UNUSED: This is redundant if remove genesis_post_info complelety from maybe_remove_meta() method
	 * Remove post info from location pages
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function maybe_remove_post_info( $post_info ) {
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
