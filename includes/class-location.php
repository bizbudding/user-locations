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

		// Location role link
		add_filter( 'author_link',	  array( $this, 'location_author_link' ), 10, 2 );
		// View own posts
		add_filter( 'pre_get_posts',  array( $this, 'view_author_posts' ) );

		add_action( 'admin_head', array( $this, 'redirect_if_editing_profile' ) );
		add_action( 'admin_head', array( $this, 'redirect_if_editing_parent_id' ) );

		// Custom Dashboard
		add_action( 'admin_enqueue_scripts',  array( $this, 'dashboard_widget_header' ) );
		add_action( 'wp_dashboard_setup', 	  array( $this, 'dashboard_widget' ), 99 );
		add_action( 'admin_head-index.php',   array( $this, 'dashboard_columns' ) );

		// Remove menu
		add_action( 'admin_menu', array( $this, 'remove_admin_menu_items' ) );
		// Remove menu
		add_action( 'do_meta_boxes', array( $this, 'remove_meta_boxes' ) );
		// Remove admin columns
		$this->remove_admin_columns();
		// Admin CSS
		add_action( 'admin_head', array( $this, 'admin_css' ) );

		add_action( 'admin_bar_menu', array( $this, 'remove_admin_bar_menu' ), 200 );
	}

	public function location_author_link( $link, $user_id ) {
		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
			return $link;
		}
		$parent_id = userlocations_get_location_parent_page_id( $user_id );
		return get_permalink( $parent_id );
	}

	public function view_author_posts( $query ) {

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

	/**
	 * Add ACF form header function
	 *
	 * @since 	1.0.0
	 *
	 * @param  string  $hook  The current page we are viewing
	 *
	 * @return void
	 */
	public function dashboard_widget_header( $hook ) {

		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
			return;
		}

		if ( 'index.php' != $hook ) {
			return;
		}
		// ACF required
		acf_form_head();
	}

	public function dashboard_widget() {

		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
			return;
		}

		// Remove all of the existing dashboard widgets
		$this->remove_dashboard_widgets();

		// Add our new dashboard widget
		wp_add_dashboard_widget(
			'my_location_info',
			'My Location Info',
			array( $this, 'dashboard_widget_cb' )
		);
	}

	public function remove_dashboard_widgets() {
		global $wp_meta_boxes;
		unset($wp_meta_boxes['dashboard']);
	}

	public function dashboard_widget_cb() {

		$args = array(
			// 'post_title'			=> true,
			// 'post_content'			=> true,
			'post_id'				=> userlocations_get_location_parent_page_id( get_current_user_id() ),
			'field_groups'			=> array('group_5773cc5bdf8dc'), // Create post field group ID(s)
			'form'					=> true,
			// 'honeypot'				=> true,
			// 'uploader'			 	=> 'basic',
			// 'return'				=> $permalink, // Redirect to new/edited post url
			'html_before_fields'	=> '',
			'html_after_fields'		=> '',
			'submit_value'			=> 'Save Changes',
			'updated_message'		=> 'Saved!'
		);
		echo acf_form( $args );
	}

	/**
	 * Force the dashboard to only show 1 column
	 *
	 * @since 	1.0.0
	 *
	 * @return	void
	 */
	public function dashboard_columns() {

		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
			return;
		}

		add_screen_option(
			'layout_columns',
			array(
				'max'     => 1,
				'default' => 1
			)
		);
	}

	public function remove_admin_menu_items() {
		$user_id = get_current_user_id();
		if ( ! userlocations_is_location_role( $user_id ) ) {
			return;
		}
		// remove_menu_page('index.php');   // Dashboard
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
		// remove_meta_box( 'pageparentdiv','location_page','side' ); 	// Page Attributes

        // Content area - Genesis
        remove_meta_box( 'genesis_inpost_seo_box','post','normal' ); 	 // Genesis SEO
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
		 * 1. Remove (All | Mine | Published) posts links
		 * 2. Remove (Page Attributes) metabox - can't actually remove it cause values won't save
		 */
		echo '<style type="text/css">
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
			</style>';
			// #wpseo-score {
			// 	float: right;
			// }
	}

	function remove_admin_bar_menu() {

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

	    $wp_admin_bar->add_menu( array(
           'id'     => 'view-profile',
           'title'  => __( 'Visit My', 'user-locations' ) . ' ' . userlocations_get_default_name('singular'),
           'parent' => 'site-name',
           'href'   => $profile_url,
		) );

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
