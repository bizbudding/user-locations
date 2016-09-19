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
		add_action( 'init', array( $this, 'register_post_types'), 0 );
		add_action( 'init', array( $this, 'register_taxonomies'), 0 );
		// Filters
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
			'exclude_from_search' => false,
		    'has_archive'         => apply_filters( 'ul_location_page_has_archive', true ),
			'hierarchical'		  => true,
			'menu_icon'		      => 'dashicons-admin-page',
			'menu_position'		  => current_user_can('edit_others_posts') ? 18 : 3,
			'quick_edit'		  => current_user_can('edit_others_posts'),
			'rewrite' 			  => array( 'slug' => sanitize_title_with_dashes( User_Locations()->get_default_name('slug') ) ),
			'show_ui'             => true,
			'show_in_nav_menus'	  => true,
			'supports' 	          => array( 'title', 'editor', 'author', 'thumbnail', 'publicize', 'genesis-cpt-archives-settings' ),
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
	        'singular' => ( is_admin() && ! current_user_can('edit_others_posts') ) ? 'Page' : ul_get_singular_name(),
	        'plural'   => ( is_admin() && ! current_user_can('edit_others_posts') ) ? 'Pages' : ul_get_plural_name(),
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

}
