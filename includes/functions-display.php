<?php
/**
 * @package User_Locations
 */

// Create shortcode functionality. Functions are defined in includes/wpseo-local-functions.php because they're also used by some widgets.
add_shortcode( 'ul_address',       'ul_show_address' );
add_shortcode( 'ul_map',           'ul_show_map' );
// add_shortcode( 'ul_all_locations', 'ul_show_all_locations' );
// add_shortcode( 'ul_opening_hours', 'ul_show_openinghours_shortcode_cb' );

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
 * TODO ?!?!?!?!?
 */
function ul_show_map( $location_parent_id ) {

	// Get the location
    $location = get_field( 'location', $location_parent_id );

    // d($location);
    // Bail if no location
    if ( ! $location ) {
    	return;
    }

    $output = '';

   	// Enqueue our scripts (previously registered)
    wp_enqueue_script('google-map');
    wp_enqueue_script('user-locations-map');

    $output .= '<div class="user-locations-map">';
        $output .= '<div class="marker" data-lat="' . $location['lat'] . '" data-lng="' . $location['lng'] . '">';
            $output .= '<a style="display:block;text-align:center;" href="' . get_permalink($location_parent_id) . '">' . get_the_title($location_parent_id) . '</a>';
        $output .= '</div>';
    $output .= '</div>';

    // Include CSS for our map (You can move this to your stylesheet)
    $output .= '<style type="text/css">
        .user-locations-map {
            width: 100%;
            height: 300px;
            margin-bottom: 20px;
        }
        .acf-map img {
		    max-width: none;
		}
	    </style>';

    // Output our data
	return $output;
}
