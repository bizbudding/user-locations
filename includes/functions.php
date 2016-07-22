<?php
/**
 * @package User_Locations
 */

function ul_get_singular_name( $lowercase = false ) {
	return User_Locations()->get_singular_name( $lowercase );
}

function ul_get_plural_name( $lowercase = false ) {
	return User_Locations()->get_plural_name( $lowercase );
}

/**
 * Get default name for Locations
 *
 * @since  1.0.0
 *
 * @param  string  $key  Only 'singular', 'plural', or 'slug'
 *
 * @return string
 */
function ul_get_default_name( $key ) {
	return User_Locations()->get_default_name( $key );
}

/**
 * Helper function to check if Dashboard, get the logged in users location parent page ID
 *
 * @since   1.0.0
 *
 * @return  bool
 */
function ul_is_dashboard() {
	global $pagenow;
	if ( $pagenow == 'index.php' ) {
		return true;
	}
	return false;
}

/**
 * Checks whether array keys are meant to mean false but aren't set to false.
 *
 * @since	1.0.0
 *
 * @param   array $args Array to check.
 *
 * @return  array
 */
function ul_check_falses( $args ) {
	if ( ! is_array( $args ) ) {
		return $args;
	}
	foreach ( $args as $key => $value ) {
		if ( $value === 'false' || $value === 'off' || $value === 'no' || $value === '0' ) {
			$args[ $key ] = false;
		}
		else if ( $value === 'true' || $value === 'on' || $value === 'yes' || $value === '1' ) {
			$args[ $key ] = true;
		}
	}
	return $args;
}

function ul_get_field( $post_id, $name ) {
	return get_post_meta( $post_id, $name, true );
}

function ul_get_acf_field( $post_id, $name ) {
	return get_field( $name, $post_id );
}

function ul_do_location_menu() {
	echo ul_get_location_menu();
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
function ul_get_location_menu() {

	if ( ! is_singular( array( 'post', 'location_page' ) ) ) {
		return;
	}
	$post_id = get_the_ID();
	// Set the parent page ID
	if ( is_singular('post') ) {
		$parent_id = ul_get_location_parent_page_id_from_post_id( $post_id );
		/**
		 * Bail if no parent ID
		 * Probably because this post is not from a location
		 */
		if ( ! $parent_id ) {
			return;
		}
	} else {
		$parent_id = ul_get_location_parent_page_id();
	}

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

/**
 * Echo the location posts loop
 *
 * @since  1.0.0
 *
 * @return mixed
 */
function ul_do_location_posts() {
	if ( ! is_singular('location_page') ) {
		return;
	}
	global $post;
	if ( $post->post_parent != 0 ) {
		return;
	}
	$user_id = $post->post_author;
	$count	 = count_user_posts( $user_id , 'post' );
	if ( $count > 0 ) {
		echo '<div class="location-template location-posts">';
			echo '<h2>' . __( 'Recent Posts', 'user-locations' ) . '</h2>';
			$args	 = array(
			    'post_type' => 'post',
			    'author'	=> $user_id,
			);
			genesis_custom_loop( wp_parse_args( $args ) );
			wp_reset_postdata();
		echo '</div>';
	}
}

/**
 * Helper function to check if viewing a single location package
 *
 * @since   1.0.0
 *
 * @return  bool
 */
function ul_is_location_content() {
	if ( ! is_singular( array( 'post', 'location_page' ) ) ) {
		return false;
	}
	if ( is_singular( 'post') ) {
		// If the post author is not a 'location'
		$author_id = get_post_field( 'post_author', get_the_ID() );
		if ( ! ul_is_location_role( $author_id ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Check if viewing a location page
 *
 * @since  1.0.0
 *
 * @return bool
 */
function ul_is_location_parent_page() {
	if ( ! is_singular( 'location_page' ) ) {
		return false;
	}
	// If viewing a top level location page
	global $post;
	if ( $post->post_parent == 0 ) {
		return true;
	}
	return false;
}

/**
 * Helper function to check if a specific page type
 *
 * @since   1.0.0
 *
 * @param   int|string  $term_slug_or_id  Optional. If empty, checks if any page type is selected
 *
 * @return  bool
 */
function ul_is_location_parent_page_template( $term_slug_or_id = '' ) {
	if ( has_term( $term_slug_or_id, 'location_page_template' ) ) {
		return true;
	}
	return false;
}

/**
 * Get the location parent page URL
 *
 * @since  1.0.0
 *
 * @return url|string|false
 */
function ul_get_location_parent_page_url() {
	$parent_id = ul_get_location_parent_page_id();
	if ( $parent_id ) {
		return get_permalink( $parent_id );
	}
	return false;
}

/**
 * Get the location parent page ID
 * Must be used in the loop!
 *
 * @since  1.0.0
 *
 * @return url|string
 */
function ul_get_location_parent_page_id() {
	if ( ! is_singular('location_page') ) {
		return false;
	}
	global $post;
	if ( $post->post_parent ) {
		$ancestors	= get_post_ancestors($post->ID);
		$root		= count($ancestors)-1;
		$parent_id	= $ancestors[$root];
	} else {
		$parent_id = $post->ID;
	}
	return $parent_id;
}

/**
 * Get the location parent page ID from a post ID
 *
 * @since  1.1.0
 *
 * @param  int    $post_id
 *
 * @return int|false
 */
function ul_get_location_parent_page_id_from_post_id( $post_id ) {
	if ( 'post' != get_post_type( $post_id ) ) {
		return false;
	}
	$terms = get_the_terms ( $post_id, 'location_feed' );
	if ( ! $terms ) {
		return false;
	}
	// Get the first term slug (should only be 1 term anyway)
	$slug = $terms[0]->slug;
	// Strip out the ID
	$location_id = (int)str_replace( 'location_', '', $slug );
	return $location_id;
}

/**
 * Check if a user is is a 'location' role
 *
 * @since   1.1.0
 *
 * @param   int  $user  (optional) The user object or ID
 *
 * @return  bool
 */
function ul_user_is_location( $user = '' ) {
	if ( empty($user) ) {
		if ( current_user_can('edit_location_pages') && ! current_user_can('edit_others_posts') ) {
			return true;
		}
	}
	elseif ( user_can( $user, 'edit_location_pages' ) && ! user_can( $user, 'edit_others_posts' ) ) {
		return true;
	}
	return false;
}

/**
 * Add location_pages capabilities to a specific user
 *
 * @since  1.1.0
 *
 * @param  integer  $user_id
 * @param  boolean  $others   (Optional) Whether to allow user to edit/delete other peoples pages
 *
 * @return void
 */
function ul_add_user_location_pages_capabilities( $user_id, $others = false ) {
	$user = new WP_User( $user_id );
	$user->add_cap( 'publish_location_pages' );
	$user->add_cap( 'edit_location_page' );
	$user->add_cap( 'edit_location_pages' );
	$user->add_cap( 'edit_published_location_pages' );
	$user->add_cap( 'delete_location_page' );
	$user->add_cap( 'delete_location_pages' );
	if ( $others ) {
		$user->add_cap( 'edit_others_location_pages' );
		$user->add_cap( 'delete_others_location_pages' );
		$user->add_cap( 'read_private_location_pages' );
	}
}

/**
 * Remove location_pages capabilities from a specific user
 *
 * @since  1.1.0
 *
 * @param  integer  $user_id
 * @param  boolean  $others   (Optional) Whether to allow user to edit/delete other peoples pages
 *
 * @return void
 */
function ul_remove_user_location_pages_capabilities( $user_id ) {
	$user = new WP_User( $user_id );
	$user->remove_cap( 'publish_location_pages' );
	$user->remove_cap( 'edit_location_page' );
	$user->remove_cap( 'edit_location_pages' );
	$user->remove_cap( 'edit_published_location_pages' );
	$user->remove_cap( 'delete_location_page' );
	$user->remove_cap( 'delete_location_pages' );
	$user->remove_cap( 'edit_others_location_pages' );
	$user->remove_cap( 'delete_others_location_pages' );
	$user->remove_cap( 'read_private_location_pages' );
}

function ul_get_user_location_pages_capabilities( $others = false ) {
	$caps = array(
		'publish_location_pages',
		'edit_location_page',
		'edit_location_pages',
		'edit_published_location_pages',
		'delete_location_page',
		'delete_location_pages',
	);
	if ( $others == true ) {
		$caps[] = 'edit_others_location_pages';
		$caps[] = 'delete_others_location_pages';
		$caps[] = 'read_private_location_pages';
	}
	return $caps;
}

/**
 * Helper function to create location pages
 * Maybe set location page type
 * Must use wp_set_object_terms in place of $data['tax_input'] since 'location' user role
 * 		doesn't have the capability to manage the 'location_page_template' taxonomy
 *
 *
 * @since  1.0.0
 *
 * @param  int     $parent_id   The parent location page ID that the new page will be a child of
 * @param  int     $author_id  	The user ID who is creating the page
 * @param  string  $title  	 	The title of the post being created
 * @param  string  $status   	The post status
 * @param  string  $terms 	 	The location_page_template (Optional) Name of the page type. Slug will automatically be created
 *
 * @return void
 */
function ul_create_default_location_page( $parent_id, $author_id, $title, $status = 'draft', $page_type = '' ) {
	$data = array(
		'post_author'	=> $author_id,
		'post_parent'	=> $parent_id,
		'post_title'	=> $title,
		'post_type'		=> 'location_page',
		'post_status'	=> $status,
	);
	$page_id = wp_insert_post( $data );
	if ( $page_type ) {
		wp_set_object_terms( $page_id, $page_type, 'location_page_template', false );
	}
}

/**
 * Helper function to get template part
 *
 * ul_get_template_part( 'account', 'page' );
 *
 * This will try to load in the following order:
 * 1: wp-content/themes/theme-name/user-locations/account-page.php
 * 2: wp-content/themes/theme-name/user-locations/account.php
 * 3: wp-content/plugins/plugin-name/templates/account-page.php
 * 4: wp-content/plugins/plugin-name/templates/account.php.
 *
 * @since  1.0.0
 *
 * @param  string  		 $slug
 * @param  string  		 $name
 * @param  boolean 		 $load
 * @param  string|array  $data  optional array of data to pass into template
 *
 * $data param MUST be called $data, not any other variable name
 * $data MUST be an array
 *
 * @return mixed
 */
function ul_get_template_part( $slug, $name = null, $load = true, $data = '' ) {
    if ( is_array($data) ) {
	    User_Locations()->templates->set_template_data( $data );
	}
    User_Locations()->templates->get_template_part( $slug, $name, $load );
}
