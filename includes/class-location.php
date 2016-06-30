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
		$this->parent_id = userlocations_get_location_parent_page_id( get_current_user_id() );
	}

	public function init() {
		// Location isn't live, show notice!
		add_action( 'admin_notices', array( $this, 'location_not_live' ) );
		// Location role link
		add_filter( 'author_link',	  array( $this, 'location_author_link' ), 10, 2 );
		// View own posts
		add_filter( 'pre_get_posts',  array( $this, 'limit_location_posts' ) );
		// Redirects
		add_action( 'admin_head', array( $this, 'redirect_if_editing_profile' ) );
		add_action( 'admin_head', array( $this, 'redirect_if_editing_parent_id' ) );
		// Remove menu
		add_action( 'admin_menu', array( $this, 'remove_admin_menu_items' ) );
		add_action( 'admin_menu', array( $this, 'remove_footer_wp_version' ) );
		add_filter( 'screen_options_show_screen', array( $this, 'remove_screen_options_tab' ) );
		add_filter( 'contextual_help', array( $this, 'remove_help_tab' ), 999, 3 );
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
	 * Show an admin notice to all locations(users) who's parent page is not published
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	function location_not_live() {
		$user_id = get_current_user_id();
		// Bail if not a location role
		if ( ! userlocations_is_location_role( $user_id ) ) {
			return;
		}
		// Bail if page is already published
		$parent_id = userlocations_get_location_parent_page_id( $user_id );
		if ( get_post_status( (int)$parent_id ) == 'publish' ) {
			return;
		}
	    echo '<div class="notice notice-error">';
		    echo '<p>Your page is not public yet. Update your <a href="' . get_dashboard_url() . '">' . userlocations_get_default_name('singular') . ' Info</a> to make your page live!</p>';
	    echo '</div>';
	}

	public function location_author_link( $link, $user_id ) {
		// $user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
			return $link;
		}
		$parent_id = userlocations_get_location_parent_page_id( $user_id );
		return get_permalink( $parent_id );
	}

	public function limit_location_posts( $query ) {

		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
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
			$parent_id = userlocations_get_location_parent_page_id( $user_id );
			$query->set('post_parent', $parent_id );
		}
		return $query;
	}

	// Redirect to settings page if a location(user) is trying to edit their profile the default WP way
	public function redirect_if_editing_profile() {

		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
			return;
		}

		global $pagenow;

		if ( $pagenow != 'profile.php' ) {
			return;
		}

		wp_redirect( admin_url('admin.php?page=location_settings') ); exit;
	}

	// Redirect to dashboard if a location(user) is trying to edit their parent page
	public function redirect_if_editing_parent_id() {

		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
			return;
		}

		global $pagenow, $typenow;

		if ( $pagenow != 'post.php' || $typenow != 'location_page' ) {
			return;
		}

		$parent_id = userlocations_get_location_parent_page_id( $user_id );

		if ( isset($_GET['post']) && $_GET['post'] == $parent_id ) {
			wp_redirect( get_dashboard_url() ); exit;
		}
	}

	public function remove_admin_menu_items() {
		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
			return;
		}
		/**
		 * If main page is not published, remove the pages menu item so they don't try to add more
		 * TODO: Handle draft parent pages and when to make public!!!!
		 */
		$parent_id = userlocations_get_location_parent_page_id( $user_id );
		if ( get_post_status( (int)$parent_id ) != 'publish' ) {
			remove_menu_page('edit.php?post_type=location_page'); // Location Pages
		}
		// remove_menu_page('index.php');   // Dashboard
		remove_menu_page('tools.php');   // Tools
		remove_menu_page('upload.php');  // Media
		remove_menu_page('profile.php'); // Profile
	}

	public function remove_footer_wp_version() {
		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
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
		if ( ! userlocations_is_location_role( $user_id ) ) {
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
		if ( ! userlocations_is_location_role( $user_id ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Remove metaboxes from admin
	 *
	 * @author Mike Hemberger
	 * @link http://codex.wordpress.org/Function_Reference/remove_meta_box
	 * @uses do_meta_boxes to fire late enough to catch plugin metaboxes
	 *
	 */
	public function remove_meta_boxes() {
		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
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

		// Sidebar - WordPress
		remove_meta_box( 'tagsdiv-post_tag','post','side' ); 		// Tags
		remove_meta_box( 'pageparentdiv','location_page','side' ); 	// Page Attributes

        // Content area - Genesis
        remove_meta_box( 'genesis_inpost_seo_box','post','normal' ); 	// Genesis SEO
        remove_meta_box( 'genesis_inpost_layout_box','post','normal' );  // Genesis Layout

		// Sidebar -
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
		// This check needs to be here, if moved to 'remove_admin_columns()' method it runs too early
		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
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
		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
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

		if ( ! userlocations_is_location_role( $user_id ) ) {
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

	    $profile_url = userlocations_get_location_parent_page_url( $user_id );

	    if ( is_admin() ) {
		    $wp_admin_bar->add_menu( array(
	           'id'     => 'view-profile',
	           'title'  => __( 'View My', 'user-locations' ) . ' ' . userlocations_get_default_name('singular'),
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
			$user_info->title = get_avatar( $user_id, 64 ) . '<span class=\'display-name\'>' . __( 'Edit My', 'user-locations' ) . ' ' . userlocations_get_default_name('singular') . '</span><span class=\'username\'>' . $current_user->user_login . '</span>';
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
		if ( userlocations_is_location_content() ) {
			return (int)get_the_author_meta('location_parent_id');
		}
		return false;
	}

	public function get_location_user_id() {
		if ( userlocations_is_location_content() ) {
			return (int)get_the_author_meta('ID');
		}
		return false;
	}

	public function get_admin_location_user_id() {
		if ( ! is_admin() ) {
			return false;
		}
		// Should we check role?
		return get_current_user_id();
	}

}
