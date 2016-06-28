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
		add_filter( 'post_type_link', array( $this, 'post_type_link' ), 10, 4 );
		add_filter( 'wpseo_breadcrumb_links', array( $this, 'author_in_breadcrumbs' ), 10, 1 );
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
			'show_in_nav_menus'   => false,
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

}
