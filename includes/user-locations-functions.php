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
 * Address shortcode handler
 *
 * @since  1.0.0
 *
 * @param  array $args Array of shortcode parameters.
 *
 * @return string
 */
function ul_show_address( $args ) {
	$args = ul_check_falses( shortcode_atts( array(
		'id'                 => '',
		'hide_name'          => false,
		'show_state'         => true,
		'show_country'       => true,
		'show_phone'         => true,
		'show_phone_2'       => true,
		'show_fax'           => true,
		'show_email'         => true,
		'show_url'           => false,
		'show_opening_hours' => false,
		'hide_closed'        => false,
		'oneline'            => false,
		'comment'            => '',
		'from_sl'            => false,
		'from_widget'        => false,
		'widget_title'       => '',
		'before_title'       => '',
		'after_title'        => '',
		'echo'               => false,
	), $args, 'ul_show_address' ) );

	$is_postal = false;

	// Bail, we don't know what data to show
	if ( $args['id'] == '' ) {
		return;
	}

	// Get the location data if its already been entered.
	$name      = get_the_title( $args['id'] );
	$type      = ul_get_field( $args['id'], 'location_type' );
	$is_postal = ul_get_field( $args['id'], 'address_is_postal' );
	$street    = ul_get_field( $args['id'], 'address_street' );
	$street_2  = ul_get_field( $args['id'], 'address_street_2' );
	$city      = ul_get_field( $args['id'], 'address_city' );
	$state     = ul_get_field( $args['id'], 'address_state' );
	$postcode  = ul_get_field( $args['id'], 'address_postcode' );
	$country   = ul_get_field( $args['id'], 'address_country' );
	$phone     = ul_get_field( $args['id'], 'phone' );
	$phone_2nd = ul_get_field( $args['id'], 'phone_2' );
	$fax       = ul_get_field( $args['id'], 'fax' );
	$email     = ul_get_field( $args['id'], 'email' );
	$url 	   = ''; // Should we have a website field?

	if ( empty( $url ) ) {
		$url = get_permalink( $args['id'] );
	}

	if ( '' == $type ) {
		$type = 'LocalBusiness';
	}

	/*
	* This array can be used in a filter to change the order and the labels of contact details
	*/
	$contact_details = array(
		array(
			'key'   => 'phone',
			'label' => __( 'Phone', 'user-locations' ),
		),
		array(
			'key'   => 'phone_2',
			'label' => __( 'Secondary phone', 'user-locations' ),
		),
		array(
			'key'   => 'fax',
			'label' => __( 'Fax', 'user-locations' ),
		),
		array(
			'key'   => 'email',
			'label' => __( 'Email', 'user-locations' ),
		),
		array(
			'key'   => 'url',
			'label' => __( 'URL', 'user-locations' ),
		),
	);

	$contact_details = apply_filters( 'ul_contact_details', $contact_details );

	$tag_title_open  = '';
	$tag_title_close = '';
	if ( ! $args['oneline'] ) {
		if ( ! $args['from_widget'] ) {
			$tag_name        = apply_filters( 'ul_title_tag_name', 'h3' );
			$tag_title_open  = '<' . esc_html( $tag_name ) . '>';
			$tag_title_close = '</' . esc_html( $tag_name ) . '>';
		}
		else if ( $args['from_widget'] && $args['widget_title'] == '' ) {
			$tag_title_open  = $args['before_title'];
			$tag_title_close = $args['after_title'];
		}
	}

	$output = '<div id="ul_location-' . esc_attr( $args['id'] ) . '" class="ul-location" itemscope itemtype="http://schema.org/' . ( ( $is_postal ) ? 'PostalAddress' : esc_attr( $type ) ) . '">';

	if ( false == $args['hide_name'] ) {
		$output .= $tag_title_open . ( ( $args['from_sl'] ) ? '<a href="' . esc_url( $url ) . '">' : '' ) . '<span class="ul-business-name" itemprop="name">' . esc_html( $name ) . '</span>' . ( ( $args['from_sl'] ) ? '</a>' : '' ) . $tag_title_close;
	}

	$output .= '<' . ( ( $args['oneline'] ) ? 'span' : 'div' ) . ' ' . ( ( $is_postal ) ? '' : 'itemprop="address" itemscope itemtype="http://schema.org/PostalAddress"' ) . ' class="ul-address-wrapper">';

	// Output city/state/zipcode in right format.
	$street_format_output = ul_get_address_format( $street, $args['oneline'], $postcode, $city, $state, $args['show_state'] );

	// Remove first comma from oneline addresses when business name is hidden.
	if ( ! empty( $street_format_output ) && true === $args['hide_name'] && true === $args['oneline'] ) {
		$street_format_output = substr( $street_format_output, 2 );
	}
	$output .= $street_format_output;

	if ( $args['show_country'] && ! empty( $country ) ) {
		$output .= ( $args['oneline'] ) ? ', ' : ' ';
		$output .= '<' . ( ( $args['oneline'] ) ? 'span' : 'div' ) . '  class="country-name" itemprop="addressCountry">' . User_Locations()->fields->get_country( $country ) . '</' . ( ( $args['oneline'] ) ? 'span' : 'div' ) . '>';
	}
	$output .= '</' . ( ( $args['oneline'] ) ? 'span' : 'div' ) . '>';

	$details_output = '';
	foreach ( $contact_details as $order => $details ) {

		if ( 'phone' == $details['key'] && $args['show_phone'] && ! empty( $phone ) ) {
			$details_output .= sprintf( '<span class="ul-phone">%s: <a href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ) . '" class="tel"><span itemprop="telephone">' . esc_html( $phone ) . '</span></a></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
		if ( 'phone_2' == $details['key'] && $args['show_phone_2'] && ! empty( $phone_2nd ) ) {
			$details_output .= sprintf( '<span class="ul-phone2nd">%s: <a href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone_2nd ) ) . '" class="tel">' . esc_html( $phone_2nd ) . '</a></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
		if ( 'fax' == $details['key'] && $args['show_fax'] && ! empty( $fax ) ) {
			$details_output .= sprintf( '<span class="ul-fax">%s: <span class="tel" itemprop="faxNumber">' . esc_html( $fax ) . '</span></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
		if ( 'email' == $details['key'] && $args['show_email'] && ! empty( $email ) ) {
			$details_output .= sprintf( '<span class="ul-email">%s: <a href="' . esc_url( 'mailto:' . $email ) . '" itemprop="email">' . esc_html( $email ) . '</a></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
		if ( 'url' == $details['key'] && $args['show_url'] ) {
			$details_output .= sprintf( '<span class="ul-url">%s: <a href="' . esc_url( $url ) . '" itemprop="url">' . esc_html( $url ) . '</a></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
	}

	if ( '' != $details_output && true == $args['oneline'] ) {
		$output .= ' - ';
	}

	$output .= $details_output;

	if ( $args['show_opening_hours'] ) {
		$args = array(
			'id'          => $args['id'],
			'hide_closed' => $args['hide_closed'],
		);
		$output .= '<br/>' . ul_show_opening_hours( $args, true ) . '<br/>';
	}
	$output .= '</div>';

	if ( isset($args['comment']) != '' ) {
		$output .= '<div class="ul-extra-comment">' . wpautop( html_entity_decode( $args['comment'] ) ) . '</div>';
	}

	if ( isset($args['echo']) && $args['echo'] == true ) {
		echo $output;
	}

	return $output;
}

/**
 * Get a location's address, properly formatted
 *
 * @param string $street The address of the business.
 * @param bool   $oneline          Whether to show the address on one line or not.
 * @param string $postcode 		   The business zipcode.
 * @param string $city    		   The business city.
 * @param string $state   		   The business state.
 * @param bool   $show_state       Whether to show the state or not.
 * @param bool   $escape_output    Whether to escape the output or not.
 * @param bool   $use_tags         Whether to use HTML tags in the outpput or not.
 *
 * @return string
 */
function ul_get_address_format( $street = '', $oneline = false, $postcode = '', $city = '', $state = '', $show_state = false, $escape_output = false, $use_tags = true ) {
	$output = '';

	$city_string = $city;
	if ( $use_tags ) {
		$city_string = '<span class="locality" itemprop="addressLocality"> ' . esc_html( $city ) . '</span>';
	}

	$state_string = $state;
	if ( $use_tags ) {
		$state_string = '<span  class="region" itemprop="addressRegion">' . esc_html( $state ) . '</span>';
	}

	$postcode_string = $postcode;
	if ( $use_tags ) {
		$postcode_string = '<span class="postal-code" itemprop="postalCode">' . esc_html( $postcode ) . '</span>';
	}

	if ( ! empty( $street ) ) {
		$output .= ( ( $oneline ) ? ', ' : '' );

		if ( $use_tags ) {
			$output .= '<' . ( ( $oneline ) ? 'span' : 'div' ) . ' class="street-address" itemprop="streetAddress">' . esc_html( $street ) . '</' . ( ( $oneline ) ? 'span' : 'div' ) . '>';
		}
		else {
			$output .= esc_html( $street ) . ' ';
		}
	}

	if ( ! empty( $city ) ) {
		$output .= ( ( $oneline ) ? ', ' : '' );
		$output .= $city_string;

		if ( true === $show_state && ! empty( $state ) ) {
			$output .= ',';
		}
	}

	if ( $show_state && ! empty( $state ) ) {
		$output .= ' ' . $state_string;
	}

	if ( ! empty( $postcode ) ) {
		if ( true == $show_state ) {
			$output .= ',';
		}

		$output .= ' ' . $postcode_string;
	}

	if ( $escape_output ) {
		$output = addslashes( $output );
	}

	return trim( $output );
}

/**
 * Function for displaying opening hours
 *
 * @since   1.0.0
 *
 * @param   array $atts        Array of shortcode parameters.
 * @param   bool  $show_schema choose to show schema.org HTML or not.
 * @param   bool  $standalone  Whether the opening hours are used stand alone or part of another function (like address).
 *
 * @return  string
 */
function ul_show_opening_hours( $atts, $show_schema = true, $standalone = true ) {
	$atts = ul_check_falses( shortcode_atts( array(
		'id'          => '',
		'hide_closed' => false,
		'echo'        => false,
		'comment'     => '',
		'show_days'   => array(),
	), $atts, 'ul_local_opening_hours' ) );

	// Output meta tags with required address information when using this as stand alone.
	$type = $name = null;
	if ( true == $standalone ) {
		$name = ul_get_field( $atts['id'], 'display_name' );
		$type = ul_get_field( $atts['id'], 'location_type' );
		$is_postal_address = ul_get_field( $atts['id'], 'address_is_postal' );
		if ( $is_postal_address ) {
			$type = 'PostalAddress';
		}
		if ( '' == $type ) {
			$type = 'LocalBusiness';
		}
	}

	$output = '<table class="ul-opening-hours" style="margin:0;"' . ( ( true == $standalone ) ? 'itemscope itemtype="http://schema.org/' . $type . '"' : '' ) . '">';

	// Output meta tags with required address information when using this as stand alone.
	if ( true == $standalone && ! empty($name) ) {
		$output .= '<meta itemprop="name" content="' . esc_attr( $name ) . '">';
	}

	if ( ! is_array( $atts['show_days'] ) ) {
		$show_days = explode( ',', $atts['show_days'] );
	}
	else {
		$show_days = (array) $atts['show_days'];
	}

	$multiple_opening_hours = ul_get_field( $atts['id'], 'opening_hours_multiple' );

	$days = array(
		'monday' => array(
			'name'   => __( 'Monday', 'user-locations' ),
			'from'   => ul_get_field( $atts['id'], 'opening_hours_monday_from' ),
			'to'     => ul_get_field( $atts['id'], 'opening_hours_monday_to' ),
			'from_2' => ul_get_field( $atts['id'], 'opening_hours_monday_from_2' ),
			'to_2'   => ul_get_field( $atts['id'], 'opening_hours_monday_to_2' ),
		),
		'tuesday' => array(
			'name'   => __( 'Tuesday', 'user-locations' ),
			'from'   => ul_get_field( $atts['id'], 'opening_hours_tuesday_from' ),
			'to'     => ul_get_field( $atts['id'], 'opening_hours_tuesday_to' ),
			'from_2' => ul_get_field( $atts['id'], 'opening_hours_tuesday_from_2' ),
			'to_2'   => ul_get_field( $atts['id'], 'opening_hours_tuesday_to_2' ),
		),
		'wednesday' => array(
			'name'   => __( 'Wednesday', 'user-locations' ),
			'from'   => ul_get_field( $atts['id'], 'opening_hours_wednesday_from' ),
			'to'     => ul_get_field( $atts['id'], 'opening_hours_wednesday_to' ),
			'from_2' => ul_get_field( $atts['id'], 'opening_hours_wednesday_from_2' ),
			'to_2'   => ul_get_field( $atts['id'], 'opening_hours_wednesday_to_2' ),
		),
		'thursday' => array(
			'name'   => __( 'Thursday', 'user-locations' ),
			'from'   => ul_get_field( $atts['id'], 'opening_hours_thursday_from' ),
			'to'     => ul_get_field( $atts['id'], 'opening_hours_thursday_to' ),
			'from_2' => ul_get_field( $atts['id'], 'opening_hours_thursday_from_2' ),
			'to_2'   => ul_get_field( $atts['id'], 'opening_hours_thursday_to_2' ),
		),
		'friday' => array(
			'name'   => __( 'Friday', 'user-locations' ),
			'from'   => ul_get_field( $atts['id'], 'opening_hours_friday_from' ),
			'to'     => ul_get_field( $atts['id'], 'opening_hours_friday_to' ),
			'from_2' => ul_get_field( $atts['id'], 'opening_hours_friday_from_2' ),
			'to_2'   => ul_get_field( $atts['id'], 'opening_hours_friday_to_2' ),
		),
		'saturday' => array(
			'name'   => __( 'Saturday', 'user-locations' ),
			'from'   => ul_get_field( $atts['id'], 'opening_hours_saturday_from' ),
			'to'     => ul_get_field( $atts['id'], 'opening_hours_saturday_to' ),
			'from_2' => ul_get_field( $atts['id'], 'opening_hours_saturday_from_2' ),
			'to_2'   => ul_get_field( $atts['id'], 'opening_hours_saturday_to_2' ),
		),
		'sunday' => array(
			'name'   => __( 'Sunday', 'user-locations' ),
			'from'   => ul_get_field( $atts['id'], 'opening_hours_sunday_from' ),
			'to'     => ul_get_field( $atts['id'], 'opening_hours_sunday_to' ),
			'from_2' => ul_get_field( $atts['id'], 'opening_hours_sunday_from_2' ),
			'to_2'   => ul_get_field( $atts['id'], 'opening_hours_sunday_to_2' ),
		),
	);

	// Hide if closed every day
	if ( 'closed' != $days['monday']['from'] && $days['tuesday']['from'] && $days['wednesday']['from'] && $days['thursday']['from'] && $days['friday']['from'] && $days['saturday']['from'] && $days['sunday']['from'] ) {

		// Loop through em
		foreach ( $days as $key => $day ) {

			// Skip if hide closed setting is true, and location is closed this day
			if ( $atts['hide_closed'] && $day['from'] == 'closed' ) {
				continue;
			}
			$name             = $day['name'];
			$from_formatted   = date( 'g:i A', strtotime( $day['from'] ) );
			$to_formatted     = date( 'g:i A', strtotime( $day['to'] ) );
			$from_2_formatted = date( 'g:i A', strtotime( $day['from_2'] ) );
			$to_2_formatted   = date( 'g:i A', strtotime( $day['to_2'] ) );
			$day_abbr         = ucfirst( substr( $key, 0, 2 ) );

			$output .= '<tr>';
				$output .= '<td class="day">' . $name . '&nbsp;</td>';
				$output .= '<td class="time">';

					// $output_time = '';
					if ( $day['from'] != 'closed' && $day['to'] != 'closed' ) {
						$output .= '<time ' . ( ( $show_schema ) ? 'itemprop="openingHours"' : '' ) . ' content="' . $day_abbr . ' ' . $day['from'] . '-' . $day['to'] . '">' . $from_formatted . ' - ' . $to_formatted . '</time>';
					} else {
						$output .= __( 'Closed', 'user-locations' );
					}

					if ( $multiple_opening_hours ) {
						if ( $day['from'] != 'closed' && $day['to'] != 'closed' && $day['from_2'] != 'closed' && $day['to_2'] != 'closed' ) {
							$output .= '<span class="openingHoursAnd"> ' . __( 'and', 'user-locations' ) . ' </span> ';
							$output .= '<time ' . ( ( $show_schema ) ? 'itemprop="openingHours"' : '' ) . ' content="' . $day_abbr . ' ' . $day['from_2'] . '-' . $day['to_2'] . '">' . $from_2_formatted . ' - ' . $to_2_formatted . '</time>';
						}
						else {
							$output .= '';
						}
					}

				$output .= '</td>';
			$output .= '</tr>';
		}
	}

	$output .= '</table>';

	if ( $atts['comment'] != '' ) {
		$output .= '<div class="ul-extra-comment">' . wpautop( html_entity_decode( $atts['comment'] ) ) . '</div>';
	}

	if ( $atts['echo'] ) {
		echo $output;
	}

	return $output;
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

function ul_get_location_menu() {
	// if ( ul_is_location_content() ) {
	// 	$user_id = ul_get_location_user_id();
	// 	return ul_get_location_menu_by_id( $user_id );
	// }
	return '';
}

function ul_get_location_menu_by_id( $user_id ) {
	return User_Locations()->frontend->get_location_menu( $user_id );
}

function ul_is_current_user_location_page() {
	if ( is_user_logged_in() ) {
		if ( get_the_ID() == ul_get_location_page_id() ) {
			return true;
		}
	}
	return false;
}

/**
 * Helper function to check if viewing a single location package
 *
 * @since   1.0.0
 *
 * @return  bool
 */
function ul_is_location_content() {
	if ( is_singular( array( 'post', 'location_page' ) ) ) {
		return true;
	}
	return false;
}

/**
 * Check if viewing a location page
 *
 * @since  1.0.0
 *
 * @return bool
 */
function ul_is_location_page() {
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
function ul_is_location_page_template( $term_slug_or_id = '' ) {
	if ( has_term( $term_slug_or_id, 'location_page_template' ) ) {
		return true;
	}
	return false;
}

function ul_get_location_page_id() {
	return User_Locations()->location->get_location_page_id();
}

// function ul_get_location_user_id() {
// 	return User_Locations()->location->get_location_user_id();
// }

function ul_get_admin_location_user_id() {
	return User_Locations()->location->get_admin_location_user_id();
}

function ul_get_location_parent_page_status( $user_id ) {
	$parent_id = ul_get_location_parent_page_id( $user_id );
	return get_post_status($parent_id);
}

/**
 * Get the location parent page URL
 * If no user id, get the parent page ID from the author meta
 *
 * @since  1.0.0
 *
 * @param  int  $user_id  Optional
 *
 * @return url|string
 */
function ul_get_location_parent_page_url( $user_id = '' ) {
	if ( empty($user_id) ) {
		$user_id = get_the_author_meta('ID');
	}
	$parent_id = ul_get_location_parent_page_id( $user_id );
	if ( $parent_id ) {
		return get_permalink( $parent_id );
	}
	return false;
}

/**
 * Get the location parent page URL
 * If no user id, get the parent page ID from the author meta
 *
 * @since  1.0.0
 *
 * @param  int  $user_id  Optional
 *
 * @return url|string
 */
function ul_get_location_parent_page_id( $user_id = '' ) {
	return User_Locations()->content->get_location_parent_page_id( $user_id );
}

function ul_is_location_role( $user_id = '' ) {
	if ( empty($user_id) ) {
		$user_id = get_current_user_id();
	}
	$user = get_user_by( 'ID', $user_id );
	if ( in_array('location', (array)$user->roles) ) {
		return true;
	}
	return false;
}

/**
 * Create location pages
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
