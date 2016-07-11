<?php /** User_Locations @package   User_Locations @author    Mike Hemberger <mike@bizbudding.com.com> @link      https://github.com/JiveDig/user-locations/
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
		// Actions
		add_action( 'admin_head', 							array( $this, 'redirect_dashboard' ) );
		add_action( 'admin_head', 							array( $this, 'redirect_edit_profile' ) );
		add_action( 'admin_head', 							array( $this, 'admin_css' ) );
		add_action( 'admin_bar_menu', 						array( $this, 'custom_toolbar' ), 200 );
		add_action( 'admin_menu', 							array( $this, 'remove_admin_menu_items' ) );
		add_action( 'admin_menu', 							array( $this, 'remove_footer_wp_version' ) );
		add_action( 'do_meta_boxes', 						array( $this, 'remove_meta_boxes' ) );
		// Filters
		// add_filter( 'pre_get_posts',  	  					array( $this, 'limit_main_blog' ) );
		add_filter( 'pre_get_posts',  	  					array( $this, 'limit_location_posts' ) );
		add_filter( 'pre_get_posts',						array( $this, 'limit_location_media' ) );
		// add_filter( 'page_attributes_dropdown_pages_args',  array( $this, 'limit_location_parent_page_attributes' ), 10, 2 );

		add_filter( 'get_edit_post_link', 					array( $this, 'edit_post_link' ), 10, 3 );
		// add_filter( 'author_link',	  	  					array( $this, 'location_author_link' ), 10, 2 );
		add_filter( 'screen_options_show_screen', 			array( $this, 'remove_screen_options_tab' ) );
		add_filter( 'contextual_help', 						array( $this, 'remove_help_tab' ), 999, 3 );
		add_filter( 'gettext', 	  							array( $this, 'translate_text_strings' ), 20, 3 );
		$this->remove_admin_columns();
	}

	/**
	 * Redirect dashboard
	 *
	 * @since   1.0.0
	 *
	 * @return  redirect
	 */
	public function redirect_dashboard() {
		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}
		global $pagenow;
		if ( $pagenow != 'index.php' ) {
			return;
		}
		wp_redirect( admin_url('admin.php?page=location_info') ); exit;
	}

	/**
	 * Redirect to settings page if a location(user) is trying to edit their profile the default WP way
	 *
	 * @since   1.0.0
	 *
	 * @return  redirect
	 */
	public function redirect_edit_profile() {
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
	 * Add custom CSS to <head>
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function admin_css() {

		$user_id = get_current_user_id();
		if ( ul_is_location_role( $user_id ) ) {
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
		        .notice:not(#message) {
		            display:none !important;
		            visibility: hidden !important;
		        }
				#wpbody-content .subsubsub {
					display:none;
					visibility:hidden;
				}
				#pageparentdiv p {
					display:none;
					visibility:hidden;
				}
				</style>';
		}

		/**
		 *  location form acf_form() padding/margin and fields
		 *  Remove (Page Attributes) metabox - can't actually remove it cause values won't save
		 *  Faux hide parent page text in admin page view
		 */
		echo '<style type="text/css">
	        #new_location_form h2 {
	        	border-bottom: 1px solid #dfdfdf;
	        }
			#new_location_form .inside {
				padding: 0;
				margin: -1px 0 0;
			}
			#new_location_form .acf-form-fields {
				border-bottom: 1px solid #dfdfdf;
			}
			#new_location_form .acf-form-submit {
				padding: 20px;
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

		// Get the user object for use in menu items below
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
		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}
        remove_filter( 'update_footer', 'core_update_footer' );
	}

	/**
	 * Remove metaboxes from admin
	 *
	 * @author  Mike Hemberger
	 * @link    http://codex.wordpress.org/Function_Reference/remove_meta_box
	 * @uses    do_meta_boxes to fire late enough to catch plugin metaboxes
	 */
	public function remove_meta_boxes() {

		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}

        /****************************
         * Content area - WordPress *
         ****************************/
        remove_meta_box( 'commentstatusdiv', 'post', 'normal' ); 	// Comments Status
        remove_meta_box( 'commentsdiv', 'post', 'normal' ); 		// Comments
        remove_meta_box( 'postcustom', 'post', 'normal' ); 			// Custom Fields
        remove_meta_box( 'postexcerpt', 'post', 'normal' ); 		// Excerpt
        remove_meta_box( 'revisionsdiv', 'post', 'normal' ); 		// Revisions
        remove_meta_box( 'slugdiv', 'post', 'normal' ); 			// Slug
        remove_meta_box( 'trackbacksdiv', 'post', 'normal' ); 		// Trackback

        /****************************
         * Content area - Genesis   *
         ****************************/
        remove_meta_box( 'genesis_inpost_seo_box', 'post', 'normal' ); 		// Genesis SEO
        remove_meta_box( 'genesis_inpost_layout_box', 'post', 'normal' );   // Genesis Layout

        /****************************
         * Sidebar - WordPress      *
         ****************************/
		remove_meta_box( 'tagsdiv-post_tag', 'post', 'side' ); 			// Tags
		remove_meta_box( 'pageparentdiv', 'location_page', 'side' ); 	// Page Attributes

        /****************************
         * Sidebar - Plugins        *
         ****************************/
		// Hide page template metabox if no terms available
		$terms = wp_count_terms( 'location_page_template', array( 'hide_empty' => false ) );
		if ( ! $terms ) {
			remove_meta_box( 'location_page_templatediv', 'location_page', 'side' ); 		// Page Templates
		}
        // remove_meta_box( 'sharing_meta','post','low' ); // Jetpack Sharing
	}

	/**
	 * Limit the main front end query to not show posts by location user roles
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
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

		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}

		global $pagenow;

		if ( $pagenow != 'edit.php' ) {
			return;
		}

		// Set the author
		$query->set('author', $user_id );

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

		if ( ! ul_is_location_role( $current_user->ID ) ) {
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
		if ( ! ul_is_location_role( get_current_user_id() ) ) {
			return $dropdown_args;
		}
		$dropdown_args['depth']				= 1;
		$dropdown_args['authors']			= $post->post_author;
		$dropdown_args['show_option_none']	= false;
		return $dropdown_args;
	}

	/**
	 * Change the edit post link for top level location pages
	 * Goes to custom admin pages
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function edit_post_link( $link, $post_id, $context ) {
		$post = get_post($post_id);
		if ( $post->post_type != 'location_page' ) {
			return $link;
		}
		if ( $post->post_parent > 0 ) {
			return $link;
		}
		return admin_url('admin.php?page=' . $post->ID );
	}

	/**
	 * TODO: HOW SHOULD THIS BE HANDLED? IF USER MANAGES MULTIPLE LOCATIONS
	 * Remove menu items
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function location_author_link( $link, $user_id ) {
		if ( ! ul_is_location_role( $user_id ) ) {
			return $link;
		}
		return 'WTH GOES HERE?';
	}

	/**
	 * Remove menu items
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function remove_admin_menu_items() {
		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}
		remove_menu_page('index.php');   // Dashboard
		remove_menu_page('tools.php');   // Tools
		remove_menu_page('upload.php');  // Media
		remove_menu_page('profile.php'); // Profile
	}

	/**
	 * Remove screen options tab
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function remove_screen_options_tab() {
		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
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
		$user_id = get_current_user_id();
		if ( ! ul_is_location_role( $user_id ) ) {
			return;
		}
		$screen = get_current_screen();
		$screen->remove_help_tabs();
	}

	/**
	 * UNUSED SINCE PAGE PARENT IS NOW MANAGED VIA ACF
	 * Translate the Attributes metabox title
	 *
	 * @since   1.0.0
	 *
	 * @return  string
	 */
	function translate_text_strings( $translated_text, $text, $domain ) {

		global $typenow;

		// If adding or editing a location page
		if ( is_admin() && $typenow == 'location_page' )  {

	        switch ( $translated_text ) {
	            case 'Attributes' :
	                $translated_text = ul_get_singular_name('location_page') . ' Page';
	                break;
	        }
	    }

	    return $translated_text;
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
	 * Helper function to get a location menu
	 *
	 * @since   1.0.0
	 *
	 * @param   $columns  array  the existing admin columns
	 *
	 * @return  $columns  array  the modified admin columns
	 */
	public function get_location_menu() {

		if ( ! is_singular( array('location_page') ) ) {
			return;
		}
		// global $post;
		// // Bail if not a location page
		// if ( $post->post_type != 'location_page' ) {
		// 	return;
		// }

		$parent_id = ul_get_location_parent_page_id();

		$args = array(
			'post_type'              => 'location_page',
			// 'author'            	 => $user_id,
			'posts_per_page'         => 50,
			'post_status'            => 'publish',
			'post_parent'			 => $parent_id,
			'orderby'				 => 'menu_order',
			'order'					 => 'ASC',
			// 'no_found_rows'          => true,
			// 'update_post_meta_cache' => false,
			// 'update_post_term_cache' => false,
		);
		// Allow for filtering of the menu item args
		$args  = apply_filters( 'userlocations_location_menu_args', $args );
		// Get the pages
		$pages = get_posts( $args );
		// Allow filtering of the menu pages
		$pages = apply_filters( 'userlocations_location_menu_pages', $pages );

		// Bail if no pages
		if ( ! $pages ) {
			return;
		}
		// Get the current page ID (outside the loop)
		$current_page_id = get_the_ID();

		$output  = '';
		$output .= '<nav class="nav-location" itemscope="" itemtype="http://schema.org/SiteNavigationElement">';
			$output .= '<div class="wrap">';
				$output .= '<ul id="menu-location-menu" class="menu genesis-nav-menu">';

					// Force a home page as first menu item
					$output .= '<li class="menu-item first-menu-item"><a href="' . get_permalink($parent_id) . '" itemprop="url"><span itemprop="name">Home</span></a></li>';

					foreach ( $pages as $page ) {

						$classes = 'menu-item';

						// Add class to current menu item
						$page_id = $page->ID;
						if ( $page_id == $current_page_id ) {
							$classes .= ' current-menu-item';
						}
						// Add each menu item
				        $output .= '<li id="menu-item-' . $page_id . '" class="' . $classes . '"><a href="' . get_the_permalink( $page->ID ) . '" itemprop="url"><span itemprop="name">' . get_the_title( $page->ID ) . '</span></a></li>';
					}

				$output .= '</ul>';
			$output .= '</div>';
		$output .= '</nav>';

		return $output;
	}

}
