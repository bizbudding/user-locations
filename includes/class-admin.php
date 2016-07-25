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
final class User_Locations_Admin {

	/**
	 * @var User_Locations_Admin The one true User_Locations_Admin
	 * @since 1.0.0
	 */
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new User_Locations_Admin;
			// Methods
			self::$instance->init();
		}
		return self::$instance;
	}

	public function init() {
		// Remove admin archive filter posts by SEO
		add_filter( 'wpseo_use_page_analysis', '__return_false' );
		// Lower the priority of Yoast SEO metabox
		add_filter( 'wpseo_metabox_prio', function() { return 'low'; } );
		// Actions
		add_action( 'post_updated',							array( $this, 'maybe_add_capabilities' ), 10, 3 );
		add_action( 'user_register', 						array( $this, 'add_user_form_capabilities' ), 10, 1 );
		add_action( 'admin_head', 							array( $this, 'redirect_dashboard' ) );
		add_action( 'admin_head', 							array( $this, 'redirect_edit_profile' ) );
		add_action( 'admin_head', 							array( $this, 'redirect_jetpack' ) );
		add_action( 'admin_head', 							array( $this, 'admin_css' ) );
		add_action( 'admin_bar_menu', 						array( $this, 'custom_toolbar' ), 200 );
		add_action( 'admin_init', 							array( $this, 'remove_admin_menu_items' ) );
		add_action( 'admin_menu', 							array( $this, 'remove_footer_wp_version' ) );
		// Meta Boxes
		add_action( 'admin_init', 							array( $this, 'maybe_disable_drag_metabox' ) );
		add_action( 'add_meta_boxes', 						array( $this, 'add_parent_page_meta_box' ), 10, 2 );
		add_action( 'edit_form_after_title', 				array( $this, 'add_after_title_meta_box_location' ) );
		add_action( 'do_meta_boxes', 						array( $this, 'remove_meta_boxes' ) );
		// Filters
		add_filter( 'wp_dropdown_users_args', 				array( $this, 'users_in_author_dropdown' ), 10, 2 );
		add_filter( 'pre_get_posts',  	  					array( $this, 'limit_location_posts' ) );
		add_filter( 'pre_get_posts',						array( $this, 'limit_location_media' ) );
		add_filter( 'page_attributes_dropdown_pages_args',  array( $this, 'limit_location_parent_page_attributes' ), 10, 2 );
		add_filter( 'author_link',	  	  					array( $this, 'location_author_link' ), 20, 2 );
		add_filter( 'screen_options_show_screen', 			array( $this, 'remove_screen_options_tab' ) );
		add_filter( 'contextual_help', 						array( $this, 'remove_help_tab' ), 999, 3 );
		$this->remove_admin_columns();
	}

 	/**
 	 * Add capabilities to a user that is author of a parent location page
 	 *
 	 * @since  1.1.0
 	 *
 	 * @return void
 	 */
	public function maybe_add_capabilities( $post_ID, $post_after, $post_before ) {
 		// Bail if not a parent location page
 		if ( $post_after->post_type != 'location_page' && $post_after->post_parent != 0 ) {
  			return;
  		}
  		// Post author
  		$this->maybe_add_user_capabilities( $post_after->post_author );

  		// If Co-Authors Plus plugin is active
  		// if ( function_exists('get_coauthors') ) {
  		// 	// If post has co-authors, give them access
  		// 	$coauthors = get_coauthors($post_after->ID);
  		// 	if ( $coauthors ) {
  		// 		foreach ( $coauthors as $coauthor ) {
			 		// $this->maybe_add_user_capabilities( $coauthor->ID );
  		// 		}
  		// 	}
  		// }
 	}

 	public function maybe_add_user_capabilities( $user_id ) {
  		// Bail if user is admin/editor, they can already do the things
  		if ( user_can( $user_id, 'edit_others_posts' ) ) {
  			return;
  		}
 		// Bail if user is already a location
 		if ( ul_user_is_location( $user_id ) ) {
  			return;
  		}
  		// Add new location capabilities for the author
 		ul_add_user_location_pages_capabilities( $user_id );
 	}

	function add_user_form_capabilities( $user_id ) {
		if ( ! $_POST['acf'] ) {
			return;
		}
		// field_579674617e6be = Is a User Location Account
		if ( ! isset($_POST['acf']['field_579674617e6be'] ) || $_POST['acf']['field_579674617e6be'] != true ) {
			return;
		}
		ul_add_user_location_pages_capabilities( $user_id );
		// field_57966571c999c = Create Location Page
		if ( ! isset($_POST['acf']['field_57966571c999c'] ) || $_POST['acf']['field_57966571c999c'] != true ) {
			return;
		}
		// Create post
		$args = array(
			'post_author' => $user_id,
			'post_status' => 'draft',
			'post_type'	  => 'location_page',
			'post_title'  => isset($_POST['acf']['field_57967d545ea64'] ) ? $_POST['acf']['field_57967d545ea64'] : ul_get_singular_name() . $_POST['user_login'],
		);
		wp_insert_post( $args );
	}

	/**
	 * Redirect dashboard
	 *
	 * @since   1.0.0
	 *
	 * @return  redirect
	 */
	public function redirect_dashboard() {
		if ( ! ul_user_is_location() ) {
			return;
		}
		global $pagenow;
		if ( $pagenow != 'index.php' ) {
			return;
		}
		wp_redirect( admin_url('edit.php?post_type=location_page') ); exit;
	}

	/**
	 * Redirect to settings page if a location(user) is trying to edit their profile the default WP way
	 *
	 * @since   1.0.0
	 *
	 * @return  redirect
	 */
	public function redirect_edit_profile() {
		if ( ! ul_user_is_location() ) {
			return;
		}
		global $pagenow;
		if ( $pagenow != 'profile.php' ) {
			return;
		}
		wp_redirect( admin_url('admin.php?page=location_settings') ); exit;
	}

	/**
	 * Redirect to settings page if a location(user) is trying to view Jetpack settings
	 *
	 * @since   1.0.0
	 *
	 * @return  redirect
	 */
	public function redirect_jetpack() {
		if ( ! ul_user_is_location() ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen->id != 'toplevel_page_jetpack' ) {
			// /wp-admin/admin.php?page=jetpack
			return;
		}
		wp_redirect( admin_url('admin.php?page=location_settings') ); exit;
	}

	/**
	 * Add custom CSS to <head>
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function admin_css() {

		if ( ul_user_is_location() ) {
			/**
			 *  Remove admin menu item separators
			 *  Remove notices (leave user-location and ACF - hopefully )
			 *  location form acf_form() padding/margin and fields
			 *  Remove (All | Mine | Published) posts links
			 *  Remove (Page Attributes) metabox - can't actually remove it cause values won't save
			 *  Faux hide parent page text in admin page view
			 */
			echo '<style type="text/css">
				#adminmenu li.wp-menu-separator,
		        .update_nag,
		        .notice:not(#message),
				#wpbody-content .subsubsub {
		            display:none !important;
		            visibility:hidden !important;
				}';
			    $args = array(
			    	'author'	     => get_current_user_id(),
			        'post_type'      => 'location_page',
			        'post_parent'	 => 0,
			        'posts_per_page' => 2,
			    );
			    $location_pages = new WP_Query( $args );
				/**
				 * If user has less than or equal to 1 parent location page
				 * hide the 'Location Page (parent)' select box since there will only be one choice in it
				 * hide the 'Location Feed' (on Posts) select box since there will only be one choice in it
				 */
				if ( $location_pages->found_posts <= 1 ) {
					echo '#ul_pageparentdiv,
						#acf-group_577fb3d45639b {
						display:none !important;
						visibility:hidden !important;
					}';
				}
			    wp_reset_postdata();
			    /**
			     * Remove slug metabox if a location user is viewing a parent location page
			     */
			    global $post;
			    if ( ul_user_is_location() && ul_is_admin_location_page() && $post->post_parent == 0 ) {
					echo '#edit-slug-box {
						display:none !important;
						visibility:hidden !important;
					}';
			    }
			echo '</style>';
		}

		/**
		 * Hide metabox accordian toggles
		 * Hide other parent page metabox elements
		 * Remove default metabox styling from parent page metabox
		 * Disable pointer cursor on metaboxes
		 * Make parent page metabox select box full width
		 */
		echo '<style type="text/css">
			.post-type-location_page .postbox .handlediv,
			#ul_pageparentdiv p {
				display:none !important;
				visibility:hidden !important;
			}
			.post-type-location_page .postbox .hndle,
			.post-type-location_page .postbox .hndle:hover {
				cursor:auto !important;
			}
			#ul_pageparentdiv.postbox .hndle,
			#ul_pageparentdiv.postbox .hndle:hover {
				border: none !important;
			}
			#ul_pageparentdiv.postbox {
				background: none !important;
				border: none !important;
				box-shadow: none !important;
				margin-top: 20px !important;
			}
			#ul_pageparentdiv.postbox h2,
			#ul_pageparentdiv.postbox .inside,
			#ul_pageparentdiv.postbox.closed .inside {
				display: block !important;
				padding: 0 !important;
			}
			#ul_pageparentdiv.postbox #parent_id {
				width: 100%;
			}
			</style>';

	}

	/**
	 * Remove/add admin bar menu items
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function custom_toolbar() {

		if ( ! ul_user_is_location() ) {
			return;
		}

		// Get the user object for use in menu items below
		$current_user = wp_get_current_user();
		$user_id 	  = $current_user->ID;

	    global $wp_admin_bar;
	    if ( ! is_object( $wp_admin_bar ) ) {
	        return;
	    }

	    // The menu items we want to keep
	    $nodes_to_keep = array(
	    	// 'appearance',
	    	// 'dashboard',
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
	    	// echo '<pre>';
		    // print_r($node);
		    // echo '</pre>';
	    	// Remove all items we don't want to keep
	        if ( ! in_array( $node->id, $nodes_to_keep ) ) {
	            $wp_admin_bar->remove_menu( $node->id );
	        }
	    }

		$args = array(
			'author'		   => $user_id,
			'orderby'          => 'title',
			'order'            => 'ASC',
			'post_type'        => 'location_page',
			'post_parent'      => 0,
			'posts_per_page'   => -1,
			'post_status'      => array( 'publish' ),
			'suppress_filters' => true,
		);
		$pages = get_posts( $args );

		if ( $pages ) {
		    if ( is_admin() ) {
				foreach ( $pages as $page ) {
				    $wp_admin_bar->add_menu( array(
			           'id'     => 'view-location-' . $page->ID,
			           'title'  => 'View ' . $page->post_title,
			           'parent' => 'site-name',
			           'href'   => get_permalink( $page->ID ),
					) );
				}
			} else {
				foreach ( $pages as $page ) {
				    $wp_admin_bar->add_menu( array(
			           'id'     => 'edit-location-' . $page->ID,
			           'title'  => 'Edit ' . $page->post_title,
			           'parent' => 'site-name',
			           'href'   => get_edit_post_link( $page->ID ),
					) );
				}
			}
		}

	    // User actions node
		$my_account = $wp_admin_bar->get_node('my-account');
			// Change the info
			$my_account->href = '';
			// Add it back with our changes
			$wp_admin_bar->add_node($my_account);

	    // Get the user info node
		$user_info = $wp_admin_bar->get_node('user-info');
			// Change the info
			$user_info->title = get_avatar( $user_id, 64 ) . '<span class=\'username\'>' . $current_user->user_login . '</span>';
			$user_info->href  = '';
			// Add it back with our changes
			$wp_admin_bar->add_node($user_info);
	}

	/**
	 * Remove the WP version number from the admin footer
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function remove_footer_wp_version() {
		if ( ! ul_user_is_location() ) {
			return;
		}
        remove_filter( 'update_footer', 'core_update_footer' );
	}

 	/**
 	 * Disable the sortable UI for metaboxes
 	 *
 	 * @since  1.1.0
 	 *
 	 * @return void
 	 */
	public function maybe_disable_drag_metabox() {
		if ( ! ul_is_admin_location_page() ) {
			return;
		}
		wp_deregister_script('postbox');
	}

	/**
	 * Add the excerpt meta box back in with a custom screen location
	 *
 	 * @since  1.1.0
 	 *
	 * @param  string $post_type
	 *
	 * @return void
	 */
	public function add_parent_page_meta_box( $post_type, $post ) {
		if ( ! in_array( $post_type, array( 'location_page' ) ) ) {
			return;
		}
		// trace( ul_is_admin_location_page('edit') );
		if ( ul_user_is_location() && ul_is_admin_location_page('edit') && $post->post_parent == 0 ) {
			return;
		}
		add_meta_box(
			'ul_pageparentdiv',
			ul_get_singular_name() . ' (' . __( 'parent', 'user-locations' ) . ')',
			'page_attributes_meta_box',
			$post_type,
			'ul_after_title',
			'high'
		);
	}

	/**
	 * Registered custom meta box position
	 *
	 * @since  1.1.0
	 *
	 * @return void
	 */
	public function add_after_title_meta_box_location() {
		global $post, $wp_meta_boxes;
		do_meta_boxes( get_current_screen(), 'ul_after_title', $post );
	}

	/**
	 * Remove metaboxes from admin
	 *
	 * @since  1.0.0
	 *
	 * @link    http://codex.wordpress.org/Function_Reference/remove_meta_box
	 * @uses    do_meta_boxes to fire late enough to catch plugin metaboxes
	 *
	 * @return  void
	 */
	public function remove_meta_boxes() {
		global $post;
		/**
		 * Remove if creating a new location page and not a location user
		 * Location users will only be able to create new 'child' pages, so they should aways see it when creating new
		 */
		if ( ( ul_is_admin_location_page('new') && ! ul_user_is_location() )
			// Or remove if editing a parent location page
			|| ( ul_is_admin_location_page('edit') && $post->post_parent == 0 ) ) {
			// Remove 'Location Page Template' metabox
			remove_meta_box( 'location_page_templatediv', 'location_page', 'side' );
		}

		// Bail if not a location user
		if ( ! ul_user_is_location() ) {
			return;
		}

        /****************************
         * Content area - WordPress *
         ****************************/
        // Posts
        remove_meta_box( 'commentstatusdiv', 'post', 'normal' );  // Comments Status
        remove_meta_box( 'commentsdiv', 	 'post', 'normal' );  // Comments
        remove_meta_box( 'postcustom', 		 'post', 'normal' );  // Custom Fields
        remove_meta_box( 'postexcerpt', 	 'post', 'normal' );  // Excerpt
        remove_meta_box( 'revisionsdiv', 	 'post', 'normal' );  // Revisions
        remove_meta_box( 'slugdiv', 		 'post', 'normal' );  // Slug
        remove_meta_box( 'trackbacksdiv', 	 'post', 'normal' );  // Trackback
        // Location Pages
        remove_meta_box( 'commentstatusdiv', 'location_page', 'normal' );  // Comments Status
        remove_meta_box( 'commentsdiv', 	 'location_page', 'normal' );  // Comments
        remove_meta_box( 'postcustom', 		 'location_page', 'normal' );  // Custom Fields
        remove_meta_box( 'postexcerpt', 	 'location_page', 'normal' );  // Excerpt
        remove_meta_box( 'revisionsdiv', 	 'location_page', 'normal' );  // Revisions
        remove_meta_box( 'slugdiv', 		 'location_page', 'normal' );  // Slug
        remove_meta_box( 'trackbacksdiv', 	 'location_page', 'normal' );  // Trackback
        /****************************
         * Content area - Genesis   *
         ****************************/
        // Posts
        remove_meta_box( 'genesis_inpost_seo_box', 	  'post', 'normal' );  // Genesis SEO
        remove_meta_box( 'genesis_inpost_layout_box', 'post', 'normal' );  // Genesis Layout
        // Location Pages
        remove_meta_box( 'genesis_inpost_seo_box', 	  'location_page', 'normal' );  // Genesis SEO
        remove_meta_box( 'genesis_inpost_layout_box', 'location_page', 'normal' );  // Genesis Layout
        /****************************
         * Sidebar - WordPress      *
         ****************************/
		remove_meta_box( 'tagsdiv-post_tag', 'post', 'side' ); 			// Tags

        /****************************
         * Sidebar - Plugins        *
         ****************************/
		// Hide page template metabox if no terms available
		$terms = wp_count_terms( 'location_page_template', array( 'hide_empty' => false ) );
		if ( ! $terms ) {
			remove_meta_box( 'location_page_templatediv', 'location_page', 'side' ); // Page Templates
		}
        // remove_meta_box( 'sharing_meta','post','low' ); // Jetpack Sharing
	}

	/**
	 * Restrict author dropdown to all users except some roles
	 * This allows admins/editors to set authors (or other roles) as authors of location pages
	 *
	 * @since  1.0.0
	 *
	 * @param  array  $query_args  The query arguments for wp_dropdown_users()
	 * @param  array  $r           The default arguments for wp_dropdown_users()
	 *
	 * @return array
	 */
	public function users_in_author_dropdown( $query_args, $r ) {
	    $query_args['who'] = '';
	    $query_args['role__not_in'] = array('subscriber','customer');
	    return $query_args;
	}

	/**
	 * Limit the main admin query to only show posts by the logged in user (if location role)
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function limit_location_posts( $query ) {

		if ( ! $query->is_main_query() || ! is_admin() ) {
	        return;
	    }

		if ( ! ul_user_is_location() ) {
			return;
		}

		// Set the author
		$query->set( 'author', get_current_user_id() );

		return;
	}

	/**
	 * Limit a location (user) to only access their own uploaded media
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function limit_location_media( $query ) {

	    global $current_user, $pagenow;

	    if ( ! is_a( $current_user, 'WP_User') ) {
	        return;
	    }

		if ( ! ul_user_is_location( $current_user ) ) {
			return;
		}

	    if ( 'admin-ajax.php' != $pagenow || $_REQUEST['action'] != 'query-attachments' ) {
	        return;
	    }

	    if ( ! current_user_can('manage_media_library') ) {
	        $query->set('author', $current_user->ID );
	    }
	    return;

	}

	/**
	 * Limit the Attributes metabox to only show top level location pages authored by the existing post's author
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function limit_location_parent_page_attributes( $dropdown_args, $post ) {
		if ( $post->post_type != 'location_page' ) {
			return $dropdown_args;
		}
		/**
		 * Top level only
		 * As of now, we never want 3rd level (grandchild) pages
		 */
		$dropdown_args['depth'] = 1;
		if ( ! ul_user_is_location() ) {
			return $dropdown_args;
		}
		$dropdown_args['authors']			= $post->post_author;
		$dropdown_args['post_status']	    = array( 'publish', 'pending', 'draft', 'future', 'private' ); // If user tries to add a new child page when their main page is a draft still
		$dropdown_args['show_option_none']	= false;
		return $dropdown_args;
	}

	/**
	 * Change the location author link to point to the correct location page
	 * Had to up the priority of this filter to 20, maybe to run later than Genesis? Idk.
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function location_author_link( $link, $user_id ) {
		if ( ! ul_user_is_location($user_id) ) {
			return;
		}
		if ( ! is_singular('post') ) {
			return $link;
		}
		$post_id 	 = get_the_ID();
		$location_id = ul_get_location_parent_page_id_from_post_id( $post_id );
		if ( $location_id ) {
			$link = get_permalink( $location_id );
		}
		return $link;
	}

	/**
	 * Remove menu items
	 * Hooked into admin_init instead of admin_menu so it runs late enough to remove jetpack and others
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function remove_admin_menu_items() {
		if ( ! ul_user_is_location() ) {
			return;
		}
		remove_menu_page('index.php');   // Dashboard
		remove_menu_page('tools.php');   // Tools
		remove_menu_page('upload.php');  // Media
		remove_menu_page('profile.php'); // Profile
		if ( class_exists( 'Jetpack' ) && ! current_user_can( 'manage_options' ) ) {
			remove_menu_page( 'jetpack' ); // Jetpack
		}
	}

	/**
	 * Remove screen options tab
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function remove_screen_options_tab() {
		if ( ! ul_user_is_location() ) {
			return true;
		}
		return false;
	}

	/**
	 * Remove admin help tab
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function remove_help_tab( $old_help, $screen_id, $screen ) {
		if ( ! ul_user_is_location() ) {
			return;
		}
		$screen = get_current_screen();
		$screen->remove_help_tabs();
	}

	/**
	 * Remove admin columns from post types
	 *
	 * @since   1.0.0
	 *
	 * @return  string
	 */
	public function remove_admin_columns() {
		$post_types = array( 'post', 'location_page' );
		foreach ( $post_types as $type ) {
			add_filter( "manage_{$type}_posts_columns", array( $this, 'remove_columns' ), 99, 1 );
		}
	}

	/**
	 * Remove admin columns
	 *
	 * @since   1.0.0
	 *
	 * @param   $columns  array  the existing admin columns
	 *
	 * @return  $columns  array  the modified admin columns
	 */
	public function remove_columns( $columns ) {
		// This check needs to be here, if moved to 'remove_admin_columns()' method it runs too early
		if ( ! ul_user_is_location() ) {
			return $columns;
		}
		$keys = array(
			'author',
			'coauthors',
			'date',
			'tags',
			// 'wpseo-score',
			// 'wpseo-title',
			// 'wpseo-metadesc',
			// 'wpseo-focuskw',
		);
		$keys = apply_filters( 'ul_remove_admin_column_keys', $keys );
		foreach ( $keys as $column ) {
			if ( isset( $columns[ $column ] ) ) {
				unset( $columns[ $column ] );
			}
		}
		return $columns;
	}

}
