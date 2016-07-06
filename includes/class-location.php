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

	public $parent_id;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new User_Locations_Location;
			// Methods
			self::$instance->init();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->parent_id = ul_get_location_parent_page_id( get_current_user_id() );
	}

	public function init() {
		// Location isn't live, show notice!
		add_action( 'admin_notices',  array( $this, 'location_not_live' ) );
		// Location role link
		add_filter( 'author_link',	  array( $this, 'location_author_link' ), 10, 2 );
		// Remove location role posts from loops
		add_filter( 'pre_get_posts',  array( $this, 'limit_main_blog' ) );
		// View own posts
		add_filter( 'pre_get_posts',  array( $this, 'limit_location_posts' ) );
		// Text when no location (user) posts
		add_filter( 'genesis_noposts_text', array( $this, 'no_location_posts_text' ) );
		// Redirects
		$this->redirects();
		// Remove menu
		add_action( 'admin_menu', 					array( $this, 'remove_admin_menu_items' ) );
		add_action( 'admin_menu', 					array( $this, 'remove_footer_wp_version' ) );
		add_filter( 'screen_options_show_screen', 	array( $this, 'remove_screen_options_tab' ) );
		add_filter( 'contextual_help', 				array( $this, 'remove_help_tab' ), 999, 3 );
 		// Remove metaboxes
		add_action( 'do_meta_boxes', array( $this, 'remove_meta_boxes' ) );
		// Remove admin columns
		$this->remove_admin_columns();
		// Admin CSS
		add_action( 'admin_head', array( $this, 'admin_css' ) );
		// Remove toolbar items
		add_action( 'admin_bar_menu', array( $this, 'remove_admin_bar_menu' ), 200 );
	}

	/**
	 * CURENTLY UNUSED!!!!
	 *
	 * Customisable capability mapping for updateable pages
	 *
	 * @param  $caps 	 A list of required capabilities for this action
	 * @param  $cap 	 The capability being checked
	 * @param  $user_id  The current user ID
	 * @param  $args 	 A numerically indexed array of additional arguments dependent on the meta cap being used
	 */
	function location_post_caps( $caps, $cap, $user_id, $args ) {

		if ( ! ul_is_location_role( $user_id ) ) {
			return $caps;
		}

		if ( ul_get_location_parent_page_status( $user_id ) == 'publish' ) {
			return $caps;
		}

		$post_id = $args[0];

		if ( get_post_type($post_id) == 'location_page' ) {
			$caps = array();
			$caps[] = 'manage_options';
		}

	    /* Return the capabilities required by the user. */
	    return $caps;
	}

	/**
	 * See WP_User::has_cap() in wp-includes/capabilities.php
	 *
	 * @param  array  $allcaps  All the capabilities of the user
	 * @param  array  $cap      [0] Required capability
	 * @param  array  $args     [0] Requested capability
	 *                          [1] User ID
	 *                          [2] Associated object ID
	 *
	 * @return array
	 */
	function location_post_caps_og( $allcaps, $caps, $args ) {
		$user_id = get_current_user_id();
		// Bail if not the user we want
		if ( $args[1] != $user_id ) {
			return $allcaps;
		}
		if ( $args[0] != 'edit_posts' ) {
			return $allcaps;
		}
		// If location parent page is published, let them post baby!!!
		if ( ul_get_location_parent_page_status( $user_id ) == 'publish' ) {
			$args[0] = true;
		}
		// $args[2] is the post ID
		// if ( ! isset($args[2]) $args[0] !== 'beat_chuck_norris' ||  ) {
			// return $allcaps;
		// }
		// $allcaps['beat_chuck_norris'] = 1;
		return $allcaps;
	}

	/**
	 * Show an admin notice to all locations(users) who's parent page is not published
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	function location_not_live() {
		$user_id = get_current_user_id();
		// Bail if not a location role
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}
		// Bail if page is already published
		if ( ul_get_location_parent_page_status( $user_id ) == 'publish' ) {
			return;
		}
	    echo '<div class="notice notice-error">';
		    echo '<p>Your page is not public yet. Update your <a href="' . get_dashboard_url() . '">' . ul_get_default_name('singular') . ' Info</a> to make your page live.</p>';
	    echo '</div>';
	}

	public function location_author_link( $link, $user_id ) {
		// $user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return $link;
		}
		$parent_id = ul_get_location_parent_page_id( $user_id );
		return get_permalink( $parent_id );
	}

	public function limit_main_blog( $query ) {
		if ( ! $query->is_main_query() || is_admin() || is_singular() ) {
	        return;
	    }
		$ids = get_users( array(
			'role'	 => 'location',
			'fields' => 'ID',
		) );
	    $query->set( 'author__not_in', $ids );
		return $query;
	}

	public function limit_location_posts( $query ) {

		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return $query;
		}

		global $pagenow;

		if ( $pagenow != 'edit.php' || ! $query->is_admin ) {
			return $query;
		}

		// Set the author
		$query->set('author', $user_id );

		// If dealing with location pages
		global $typenow;
		if ( $typenow == 'location_page' ) {
			// Set the post parent
			$parent_id = ul_get_location_parent_page_id( $user_id );
			$query->set('post_parent', $parent_id );
		}
		return $query;
	}

	public function no_location_posts_text( $text ) {
		if ( ul_is_location_page_type( 'blog' ) ) {
			$text = 'Sorry, this ' . ul_get_singular_name( true ) . ' has no posts.';
		}
		return $text;
	}

	public function redirects() {
		add_action( 'admin_head', array( $this, 'maybe_redirect_all_admin_location_pages' ) );
		add_action( 'admin_head', array( $this, 'redirect_if_editing_profile' ) );
		add_action( 'admin_head', array( $this, 'redirect_if_editing_parent_id' ) );
	}

	/**
	 * Redirect to settings page if a location(user)'s parent page isn't live
	 * and they are trying view the admin location pages archive
	 *
	 * @since   1.0.0
	 *
	 * @return  redirect
	 */
	public function maybe_redirect_all_admin_location_pages() {

		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}

		// Bail if location parent page is already live
		if ( ul_get_location_parent_page_status( $user_id ) == 'publish' ) {
			return;
		}

		global $typenow;

		if ( $typenow != 'location_page' ) {
			return;
		}

		wp_redirect( admin_url('admin.php?page=location_settings') ); exit;
	}

	/**
	 * Redirect to settings page if a location(user) is trying to edit their profile the default WP way
	 *
	 * @since   1.0.0
	 *
	 * @return  redirect
	 */
	public function redirect_if_editing_profile() {

		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}

		global $pagenow;

		if ( $pagenow != 'profile.php' ) {
			return;
		}

		wp_redirect( admin_url('admin.php?page=location_settings') ); exit;
	}

	/**
	 * Redirect to dashboard if a location(user) is trying to edit their parent page
	 *
	 * @since   1.0.0
	 *
	 * @return  redirect
	 */
	public function redirect_if_editing_parent_id() {

		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}

		global $pagenow, $typenow;

		if ( $pagenow != 'post.php' || $typenow != 'location_page' ) {
			return;
		}

		$parent_id = ul_get_location_parent_page_id( $user_id );

		// If attempting to edit the parent ID
		if ( isset($_GET['post']) && $_GET['post'] == $parent_id ) {
			wp_redirect( get_dashboard_url() ); exit;
		}
	}

	public function remove_admin_menu_items() {

		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}
		/**
		 * If main page is not published, remove the pages menu item so they don't try to add more
		 */
		if ( ul_get_location_parent_page_status( $user_id ) != 'publish' ) {
			remove_menu_page('edit.php?post_type=location_page'); // Location Pages
		}
		// remove_menu_page('index.php');   // Dashboard
		remove_menu_page('tools.php');   // Tools
		remove_menu_page('upload.php');  // Media
		remove_menu_page('profile.php'); // Profile
	}

	public function remove_footer_wp_version() {
		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}
        remove_filter( 'update_footer', 'core_update_footer' );
	}

	/**
	 * Remove admin help tab
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function remove_help_tab( $old_help, $screen_id, $screen ) {
		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}
		$screen = get_current_screen();
		$screen->remove_help_tabs();
	}

	/**
	 * Remove screen options tab for non-admins
	 */
	public function remove_screen_options_tab() {
		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Remove metaboxes from admin
	 *
	 * @author  Mike Hemberger
	 * @link    http://codex.wordpress.org/Function_Reference/remove_meta_box
	 * @uses    do_meta_boxes to fire late enough to catch plugin metaboxes
	 */
	public function remove_meta_boxes() {

		remove_meta_box( 'location_page_typediv', 'location_page', 'side' );  // Location Type

		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}
        // Content area - WordPress
        remove_meta_box( 'commentstatusdiv', 'post', 'normal' ); 	// Comments Status
        remove_meta_box( 'commentsdiv', 'post', 'normal' ); 		// Comments
        remove_meta_box( 'postcustom', 'post', 'normal' ); 			// Custom Fields
        remove_meta_box( 'postexcerpt', 'post', 'normal' ); 		// Excerpt
        remove_meta_box( 'revisionsdiv', 'post', 'normal' ); 		// Revisions
        remove_meta_box( 'slugdiv', 'post', 'normal' ); 			// Slug
        remove_meta_box( 'trackbacksdiv', 'post', 'normal' ); 		// Trackback

        // Content area - Genesis
        remove_meta_box( 'genesis_inpost_seo_box', 'post', 'normal' ); 		// Genesis SEO
        remove_meta_box( 'genesis_inpost_layout_box', 'post', 'normal' );  // Genesis Layout

		// Sidebar - WordPress
		remove_meta_box( 'tagsdiv-post_tag', 'post', 'side' ); 			// Tags
		remove_meta_box( 'pageparentdiv', 'location_page', 'side' ); 	// Page Attributes

		// Sidebar - Plugins
        // remove_meta_box( 'sharing_meta','post','low' ); 			// Jetpack Sharing
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
		// This check needs to be here, if moved to 'remove_admin_columns()' method it runs too early
		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return $columns;
		}
		$keys = apply_filters( 'ul_remove_admin_column_keys',
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
		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}
		/**
		 *  Force full width metabox container
		 *  Dashboard acf_form() padding/margin
		 *  Dashboard acf_form() fields
		 *  Dashboard acf_form() submit button
		 *  Remove (All | Mine | Published) posts links
		 *  Remove (Page Attributes) metabox - can't actually remove it cause values won't save
		 *  Faux hide parent page text in admin page view
		 */
		echo '<style type="text/css">
			.dashicons-dashboard:before {
				content: "\f231" !important;
			}
			#wpbody-content #dashboard-widgets.columns-1 .postbox-container {
				width:100% !important;
			}
			#my_location_info .inside {
				padding: 0;
				margin: 0;
			}
			#my_location_info .acf-form-fields {
				border-bottom: 1px solid #dfdfdf;
			}
			#my_location_info .acf-form-submit {
				padding: 20px;
			}
			#wpbody-content .subsubsub,
			#pageparentdiv {
				display:none;
				visibility:hidden;
			}
			.wp-list-table .type-location_page:nth-child(odd) .page-title {
				color: #f9f9f9;
			}
			.wp-list-table .type-location_page:nth-child(even) .page-title {
				color: #fff;
			}
			.wp-list-table .type-location_page .page-title .post-state {
				color: #555;
			}
			</style>';
			// #wpseo-score {
			// 	float: right;
			// }
	}

	public function remove_admin_bar_menu() {

		$current_user = wp_get_current_user();
		$user_id 	  = $current_user->ID;

		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}

	    global $wp_admin_bar;
	    if ( ! is_object( $wp_admin_bar ) ) {
	        return;
	    }

	    // The menu items we want to keep
	    $nodes_to_keep = array(
	    	// 'appearance',
	    	'dashboard',
	    	'site-name',
	    	'view-site',
	    	'new-content',
	    	'new-post',
	    	'new-location_page',
	    	'comments',
	    	'edit',
	    	'user-actions',
	    	'user-info',
	    	'logout',
	    	'menu-toggle',
	    	'my-account',
    	);

	    // Clean the AdminBar
	    $nodes = $wp_admin_bar->get_nodes();
	    foreach( $nodes as $node ) {
	    	// Remove all items we don't want to keep
	        if ( ! in_array( $node->id, $nodes_to_keep ) ) {
	            $wp_admin_bar->remove_menu( $node->id );
	        }
	    }

	    $profile_url = ul_get_location_parent_page_url( $user_id );

	    if ( is_admin() ) {
		    $wp_admin_bar->add_menu( array(
	           'id'     => 'view-profile',
	           'title'  => __( 'View My', 'user-locations' ) . ' ' . ul_get_default_name('singular'),
	           'parent' => 'site-name',
	           'href'   => $profile_url,
			) );
		}

	    // User actions node
		$my_account = $wp_admin_bar->get_node('my-account');
			// Change the info
			$my_account->href = $profile_url;
			// Add it back with our changes
			$wp_admin_bar->add_node($my_account);

	    // Get the user info node
		$user_info = $wp_admin_bar->get_node('user-info');
			// Change the info
			$user_info->title = get_avatar( $user_id, 64 ) . '<span class=\'display-name\'>' . __( 'Edit My', 'user-locations' ) . ' ' . ul_get_default_name('singular') . '</span><span class=\'username\'>' . $current_user->user_login . '</span>';
			$user_info->href  = $profile_url;
			$user_info->href  = get_dashboard_url();
			// Add it back with our changes
			$wp_admin_bar->add_node($user_info);
	}

	/**
	 * Get the location page ID from the current page being viewed
	 *
	 * @since  1.0.0
	 *
	 * @return int | bool
	 */
	public function get_location_page_id() {
		if ( ul_is_location_content() ) {
			return (int)get_the_author_meta('location_parent_id');
		}
		return false;
	}

	public function get_location_user_id() {
		if ( ul_is_location_content() ) {
			return (int)get_the_author_meta('ID');
		}
		return false;
	}

	public function get_admin_location_user_id() {
		// if ( ! is_admin() ) {
		// 	return false;
		// }
		$user_id = get_current_user_id();
		if ( ul_is_location_role( $user_id ) ) {
			return $user_id;
		}
		return false;
	}

}
