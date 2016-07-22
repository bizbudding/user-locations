<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function ul_is_admin_location_page( $archive_new_or_edit = '' ) {
	global $pagenow, $typenow;
	// Bail if not dealing with location_page
	if ( $typenow != 'location_page' ) {
		return false;
	}
	/**
	 * Editing a post $pagenow is 	'post.php'
	 * Creating a post $pagenow is  'post-new.php'
	 * Archive view $pagenow is 	'edit.php'
	 */
	// If no variable specific, then check if editing or creating a new post
	if ( empty($archive_new_or_edit) && in_array( $pagenow, array('post-new.php','post.php') ) ) {
		return true;
	}
	// Viewing admin archive
	if ( ( $archive_new_or_edit == 'archive' && $pagenow == 'edit.php' )
		// Or if creating a new location page
		|| ( $archive_new_or_edit == 'new' && $pagenow == 'post-new.php' )
		// Or if editing a location page
		|| ( $archive_new_or_edit == 'edit' && $pagenow == 'post.php' ) ) {
		return true;
	}
	return false;
}
