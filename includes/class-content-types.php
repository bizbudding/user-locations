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
		// add_action( 'admin_init',			array( $this, 'dont_keep_this_mapping' ) );
		// Actions
		add_action( 'init', 		 		array( $this, 'register_post_types'), 0 );
		add_action( 'init', 		 		array( $this, 'register_taxonomies'), 0 );

		// Filters
		add_filter( 'genesis_post_info', 	array( $this, 'maybe_remove_post_info' ), 99 );
		add_filter( 'genesis_post_meta', 	array( $this, 'maybe_remove_post_meta' ), 99 );
		// add_filter( 'wpseo_breadcrumb_links', 	array( $this, 'author_in_breadcrumbs' ), 10, 1 );
	}

	public function dont_keep_this_mapping() {
		// $user = new WP_User( 11 );
		// $user->add_cap( 'publish_location_pages' );
		// $user->add_cap( 'edit_location_page' );
		// $user->add_cap( 'edit_location_pages' );
		// $user->add_cap( 'delete_location_page' );
		// $user->add_cap( 'delete_location_pages' );
		// $user->add_cap( 'read_private_location_pages' );

			// $user->add_cap( 'delete_others_location_pages' );
			// $user->add_cap( 'edit_others_location_pages' );
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
			'menu_position'		  => current_user_can('edit_others_posts') ? 18 : 3,
			'quick_edit'		  => current_user_can('edit_others_posts'),
			'show_ui'             => true,
		    'has_archive'         => false,
			'rewrite' 			  => array( 'slug' => sanitize_title_with_dashes( User_Locations()->get_default_name('slug') ) ),
			'supports' 	          => array( 'title', 'editor', 'author', 'thumbnail', 'publicize' ),
			// 'capability_type' 	  => 'location_page',
			// 'capability_type' 	  => 'post',
			'capabilities' 		  => array(
				'publish_posts'			=> 'publish_location_pages',
				'edit_post'				=> 'edit_location_page',
				'edit_posts'			=> 'edit_location_pages',
				'edit_others_posts'		=> 'edit_others_location_pages',
				'delete_post'			=> 'delete_location_page',
				'delete_posts'			=> 'delete_location_pages',
				'delete_others_posts'	=> 'delete_others_location_pages',
				'read_private_posts'	=> 'read_private_location_pages',
			),
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
		/**
		 * The 'location' that a post is associated with
		 * Using a taxonomy allows 1 user to manage multiple locations
		 * Saves term slug as 'location_{term_id}'
		 * @see class-fields.php for loading/saving this data
		 */
		register_extended_taxonomy( 'location_feed', 'post', array(
			'public'   => false,
			'show_ui'  => true,
			'meta_box' => false,
		) );
		// The type of 'business' a location may be
		register_extended_taxonomy( 'location_type', 'location_page', array(
			'public'  => false,
			'show_ui' => false,
		) );
		// Used for custom page templates
		register_extended_taxonomy( 'location_page_template', 'location_page', array(
			'public'   => false,
			'show_ui'  => true,
			'meta_box' => 'radio',
		), array(
			'singular' => __( 'Page Template', 'user-locations' ),
			'plural'   => __( 'Page Templates', 'user-locations' ),
		) );
	}

	/**
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

	/**
	 * Remove post footer meta from location pages
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function maybe_remove_post_meta( $post_meta ) {
		if ( is_singular('location_page') ) {
			$post_meta = '';
		}
		return $post_meta;
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
