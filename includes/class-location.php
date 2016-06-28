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
final class User_Locations_Location {

	/**
	 * @var User_Locations_Location The one true User_Locations_Location
	 * @since 1.0.0
	 */
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new User_Locations_Location;
			// Methods
			self::$instance->init();
		}
		return self::$instance;
	}

	public function init() {

		// Login redirect
		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );
		// View own posts
		add_filter( 'pre_get_posts', array( $this, 'view_author_posts' ) );
		// Remove menu
		add_action( 'admin_menu', array( $this, 'remove_admin_menu_items' ) );
		// Remove menu
		add_action( 'do_meta_boxes', array( $this, 'remove_meta_boxes' ) );
		// Remove admin columns
		$this->remove_admin_columns();
		// Admin CSS
		add_action( 'admin_head', array( $this, 'admin_css' ) );

	}

	/**
	 * Redirect user after successful login.
	 *
	 * @param  string $redirect_to URL to redirect to.
	 * @param  string $request URL the user is coming from.
	 * @param  object $user Logged user's data.
	 *
	 * @return string
	 */
	public function login_redirect( $redirect_to, $request, $user ) {
	    // is there a user to check?
	    global $user;
	    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
	        //check for admins
	        if ( in_array( 'administrator', $user->roles ) ) {
	            // redirect them to the default place
	            return $redirect_to;
	        } else {
	            if ( in_array( $GLOBALS['pagenow'], array( 'wp-login.php' ) ) ) {
					// Options page url
	                return admin_url( 'admin.php?page=general' );
	            } else {
	                return $request;
	            }
	        }
	    } else {
	        return $redirect_to;
	    }
	}

	public function view_author_posts( $query ) {
		global $pagenow;
		if ( 'edit.php' != $pagenow || ! $query->is_admin ) {
			return $query;
		}
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			global $user_ID;
			$query->set('author', $user_ID );
		}
		return $query;
	}

	public function remove_admin_menu_items() {
		if ( current_user_can('manage_options') ) {
			return;
		}
		remove_menu_page('index.php');   // Dashboard
		remove_menu_page('tools.php');   // Tools
		remove_menu_page('upload.php');  // Media
		remove_menu_page('profile.php'); // Profile
	}

	/**
	 * Remove metaboxes from admin
	 *
	 * @author Mike Hemberger
	 * @link http://codex.wordpress.org/Function_Reference/remove_meta_box
	 * @uses do_meta_boxes to fire late enough to catch plugin metaboxes
	 *
	 */

	function remove_meta_boxes() {
		// Bail if administrator
		if ( current_user_can('manage_options') ) {
			return;
		}
        // Content area - WordPress
        remove_meta_box( 'commentstatusdiv','post','normal' ); 	// Comments Status
        remove_meta_box( 'commentsdiv','post','normal' ); 		// Comments
        remove_meta_box( 'postcustom','post','normal' ); 		// Custom Fields
        remove_meta_box( 'postexcerpt','post','normal' ); 		// Excerpt
        remove_meta_box( 'revisionsdiv','post','normal' ); 		// Revisions
        remove_meta_box( 'slugdiv','post','normal' ); 			// Slug
        remove_meta_box( 'trackbacksdiv','post','normal' ); 	// Trackback

		// Sidebar - wordpress
		remove_meta_box( 'tagsdiv-post_tag','post','side' ); 	// Tags

        // Content area - Genesis
        remove_meta_box( 'genesis_inpost_seo_box','post','normal' ); 	 // Genesis SEO
        remove_meta_box( 'genesis_inpost_layout_box','post','normal' );  // Genesis Layout

        // Content area - Jetpack
        // remove_meta_box( 'sharing_meta','post','low' ); // Jetpack Sharing
	}

	public function remove_admin_columns() {
		$post_types = array( 'post', 'location_page' );
		foreach ( $post_types as $type ) {
			add_filter( "manage_{$type}_posts_columns", array( $this, 'remove_columns' ), 99, 1 );
		}
	}

	/**
	 * Disable all Yoast admin columns except for the score light.
	 *
	 * @since   1.0.0
	 * @access  public
	 * @param   $columns  array  the existing admin columns
	 * @return  $columns  array  the modified admin columns
	 */
	public function remove_columns( $columns ) {
		// Bail if administrator
		if ( current_user_can('manage_options') ) {
			return $columns;
		}
		$keys = apply_filters( 'userlocations_remove_admin_column_keys',
			array(
				'author',
				'coauthors',
				'date',
				'tags',
				'wpseo-score',
				'wpseo-title',
				'wpseo-metadesc',
				'wpseo-focuskw',
			)
		);
		foreach ( $keys as $column ) {
			if ( isset( $columns[ $column ] ) ) {
				unset( $columns[ $column ] );
			}
		}
		return $columns;
	}

	/**
	 * Add custom CSS to <head>
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function admin_css() {
		// Bail if administrators
		if ( current_user_can('manage_options') ) {
			return;
		}
		echo '<style type="text/css">
			#wpbody-content .subsubsub {
				display:none;
				visibility:hidden;
			}
			#wpseo-score {
				float: right;
			}
			</style>';
	}

	// public function get_location_id_og() {
	// 	$location = $this->get_location();
	// 	if ( $location ) {
	// 		return $location->ID;
	// 	}
	// 	return false;
	// }

	public function get_location_id() {
		if ( userlocations_is_location_content() ) {
			return get_the_author_meta('ID');
		}
		return false;
	}

	public function get_admin_location_id() {
		if ( ! is_admin() ) {
			return false;
		}
		global $pagenow;
		if ( $pagenow == 'profile.php' ) {
			global $user_id;
			return $user_id;
		}
		return get_current_user_id();
	}

}
