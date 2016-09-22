<?php
/**
 * @package User_Locations
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Create shortcode functionality. Functions are defined in includes/wpseo-local-functions.php because they're also used by some widgets.
// add_shortcode( 'ul_address',       'ul_show_info' );
// add_shortcode( 'ul_map',           'ul_show_map' );
// add_shortcode( 'ul_all_locations', 'ul_show_all_locations' );
// add_shortcode( 'ul_opening_hours', 'ul_show_openinghours_shortcode_cb' );

/**
 * Address shortcode handler
 *
 * @since  1.0.0
 *
 * @param  array  $args  Array of parameters.
 *
 * @return string
 */
function ul_get_info( $args ) {

	/**
	 *  Optional args
	 *  Defaults may be set in other functions
	 *
 	 *	'id'					=> '' ,
	 *	'show_name'				=> true,
	 *	'show_social'			=> true,
	 *  'show_street'			=> true,
	 *	'show_street_2'			=> true,
	 *	'show_city'				=> true,
	 *	'show_state'			=> true,
	 *	'show_postcode'			=> true,
	 *	'show_country'			=> true,
	 *	'show_phone'			=> true,
	 *	'show_phone_2'			=> true,
	 *	'show_fax'				=> true,
	 *	'show_email'			=> true,
	 *	'show_url'				=> true,
	 *	'show_social'			=> true,
	 *	'comment'				=> '',
	 *	'show_opening_hours'	=> true,
	 *	'show_closed'			=> true,
	 */

	$defaults = array(
		'id'					=> '',
		'show_name'				=> true,
		'show_social'			=> true,
		'comment'				=> '',
		'show_opening_hours'	=> true,
	);
	$args = wp_parse_args( $args, $defaults );


	// Bail, we don't know what data to show
	if ( $args['id'] == '' ) {
		return;
	}

	// Start the output
	$output = '';

	// Get the location data if its already been entered.
	$type	= ul_get_field( $args['id'], 'location_type' );
	if ( ! $type ) {
		$type = 'LocalBusiness';
	}

	$output = '<div id="ul_location-' . esc_attr( $args['id'] ) . '" class="ul-location" itemscope itemtype="http://schema.org/' . esc_attr( $type ) . '">';

		if ( $args['show_name'] ) {
			$output .= '<div class="ul-name">' . get_the_title( $args['id'] ) . '</div>';
		}

		/**
		 * Get the address
		 * Display conditions happen inside function
		 */
		$output .= ul_get_address( $args );

		/**
		 * Get the contact info
		 * Display conditions happen inside function
		 */
		$output .= ul_get_contact_info( $args );

		if ( $args['show_social'] ) {
			$output .= ul_get_social_links( $args );
		}

		if ( $args['comment'] ) {
			$output .= '<div class="ul-comment">' . wpautop(wp_kses_post( $args['comment'] )) . '</div>';
		}

		if ( $args['show_opening_hours'] ) {
			$output .= ul_get_opening_hours( $args );
		}

	$output .= '</div>';

	return $output;
}

/**
 * Get a location's address, properly formatted
 *
 * @since  1.0.0
 *
 * @param  array  $args  Array of parameters.
 *
 * @return string
 */
function ul_get_address( $args ) {

	$defaults = array(
		'id'			=> '',
		'show_street'	=> true,
		'show_street_2'	=> true,
		'show_city'		=> true,
		'show_state'	=> true,
		'show_postcode'	=> true,
		'show_country'	=> true,
	);
	$args = wp_parse_args( $args, $defaults );

	// Bail, we don't know what data to show
	if ( $args['id'] == '' ) {
		return;
	}

	// Bail if we're not showing anything
	if ( empty($args['show_street']) && empty($args['show_street_2']) && empty($args['show_city']) && empty($args['show_state']) && empty($args['show_postcode']) && empty($args['show_country']) ) {
		return;
	}

	$is_postal = ul_get_field( $args['id'], 'address_is_postal' );
	$street    = ul_get_field( $args['id'], 'address_street' );
	$street_2  = ul_get_field( $args['id'], 'address_street_2' );
	$city      = ul_get_field( $args['id'], 'address_city' );
	$state     = ul_get_field( $args['id'], 'address_state' );
	$postcode  = ul_get_field( $args['id'], 'address_postcode' );
	$country   = ul_get_field( $args['id'], 'address_country' );

	$output = '';

	$output .= '<div ' . ( ( $is_postal ) ? '' : 'itemprop="address" itemscope itemtype="http://schema.org/PostalAddress"' ) . ' class="ul-address-wrapper">';

		if ( $args['show_name'] && $street ) {
			$output .= '<div class="ul-address-item"><span class="street-address" itemprop="streetAddress">' . esc_html( $street ) .'</span></div>';
		}

		if ( ( $args['show_city'] && $city ) || ( $args['show_state'] && $state ) || ( $args['show_postcode'] && $postcode ) ) {
			$output .= '<div class="ul-address-item">';
		}

			if ( $city ) {
				$output .= '<span class="locality" itemprop="addressLocality">' . esc_html( $city ) . '</span>';
			}

			if ( $state ) {
				$output .= '<span class="region" itemprop="addressRegion">&nbsp;' . esc_html( $state ) . '</span>';
			}

			if ( $postcode ) {
				$output .= '<span class="postal-code" itemprop="postalCode">,&nbsp;' . esc_html( $postcode ) . '</span>';
			}

		if ( ( $args['show_city'] && $city ) || ( $args['show_state'] && $state ) || ( $args['show_postcode'] && $postcode ) ) {
			$output .= '</div>';
		}

		if ( $args['show_country'] && $country ) {
			$country = User_Locations()->fields->get_country($country);
			$output .= '<div class="ul-address-item" itemprop="addressCountry">' . $country . '</div>';
		}

	$output .= '</div>';

	return $output;

}

/**
 * Get a location's contact info
 *
 * @since  1.2.0
 *
 * @param  array  $args  Array of parameters.
 *
 * @return string
 */
function ul_get_contact_info( $args ) {

	$defaults = array(
		'id'			=> '',
		'show_phone'	=> true,
		'show_phone_2'	=> true,
		'show_fax'		=> true,
		'show_email'	=> true,
		'show_url'		=> true,
	);
	$args = wp_parse_args( $args, $defaults );

	// Bail, we don't know what data to show
	if ( $args['id'] == '' ) {
		return;
	}

	// Bail if we're not showing anything
	if ( empty($args['show_phone']) && empty($args['show_phone_2']) && empty($args['show_fax']) && empty($args['show_email']) && empty($args['show_url']) ) {
		return;
	}

	$phone		= ul_get_field( $args['id'], 'phone' );
	$phone_2nd	= ul_get_field( $args['id'], 'phone_2' );
	$fax		= ul_get_field( $args['id'], 'fax' );
	$email		= ul_get_field( $args['id'], 'email' );
	$url		= ul_get_field( $args['id'], 'location_url' );

	// This array can be used in a filter to change the order and the labels of contact details
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

	$output = '';

	$output .= '<div class="ul-contact-info">';

	foreach ( $contact_details as $order => $details ) {

		if ( 'phone' == $details['key'] && $args['show_phone'] && ! empty( $phone ) ) {
			$output .= sprintf( '<div class="ul-phone">%s: <a href="%s" class="tel"><span itemprop="telephone">%s</span></a></div>',
				esc_html( $details['label'] ),
				esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ),
				esc_html( $phone )
			);
		}
		if ( 'phone_2' == $details['key'] && $args['show_phone_2'] && ! empty( $phone_2nd ) ) {
			$output .= sprintf( '<div class="ul-phone-2">%s: <a href="%s" class="tel">%s</a></div>',
				esc_html( $details['label'] ),
				esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone_2nd ) ),
				esc_html( $phone_2nd )
			);
		}
		if ( 'fax' == $details['key'] && $args['show_fax'] && ! empty( $fax ) ) {
			$output .= sprintf( '<div class="ul-fax">%s: <span class="tel" itemprop="faxNumber">%s</span></div>',
				esc_html( $details['label'] ),
				esc_html( $fax )
			);
		}
		if ( 'email' == $details['key'] && $args['show_email'] && ! empty( $email ) ) {
			$output .= sprintf( '<div class="ul-email">%s: <a href="%s" itemprop="email">%s</a></div>',
				esc_html( $details['label'] ),
				esc_url( 'mailto:' . antispambot($email) ),
				antispambot( $email )
			);
		}
		if ( 'url' == $details['key'] && $args['show_url'] && ! empty( $url ) ) {
			$output .= sprintf( '<div class="ul-url"><a href="%s" itemprop="url">%s</a></div>',
				esc_url( $url ),
				esc_html( $url )
			);
		}
	}

	$output .= '</div>';

	return $output;

}

function ul_get_social_links( $args ) {

	$defaults = array(
		'id' => '',
	);
	$args = wp_parse_args( $args, $defaults );

	// Bail, we don't know what data to show
	if ( $args['id'] == '' ) {
		return;
	}

	$social_links = array(
		'facebook' => array(
			'url'	=> ul_get_field( $args['id'], 'facebook' ),
			'icon'	=> '<span class="fa fa-facebook"></span>',
		),
		'twitter' => array(
			'url'	=> ul_get_field( $args['id'], 'twitter' ),
			'icon'	=> '<span class="fa fa-twitter"></span>',
		),
		'googleplus' => array(
			'url'	=> ul_get_field( $args['id'], 'googleplus' ),
			'icon'	=> '<span class="fa fa-google-plus"></span>',
		),
		'youtube' => array(
			'url'	=> ul_get_field( $args['id'], 'youtube' ),
			'icon'	=> '<span class="fa fa-youtube"></span>',
		),
		'linkedin' => array(
			'url'	=> ul_get_field( $args['id'], 'linkedin' ),
			'icon'	=> '<span class="fa fa-linkedin"></span>',
		),
		'instagram'	=> array(
			'url'	=> ul_get_field( $args['id'], 'instagram' ),
			'icon'	=> '<span class="fa fa-instagram"></span>',
		),
		'pinterest'	=> array(
			'url'	=> ul_get_field( $args['id'], 'pinterest' ),
			'icon'	=> '<span class="fa fa-pinterest"></span>',
		),
	);
	// Allow social links to be filtered to add new ones, or change the order
	$social_links = apply_filters( 'ul_social_links', $social_links );

	// Check if we have any links
	$has_links = false;
	foreach ( $social_links as $key => $values ) {
		if ( trim($values['url']) ) {
			$has_links = true;
			break;
		}
	}

	// Bail if no links
	if ( ! $has_links ) {
		return;
	}

	$output = '';

	$output .= '<ul class="ul-social-links">';


	foreach ( $social_links as $key => $values ) {
		// Skip if no URL
		if ( ! $values['url'] ) {
			continue;
		}
		// Twitter value is just the username
		if ( 'twitter' == $key ) {
			$values['url'] = 'https://twitter.com/' . $values['url'];
		}
		// Instagram value is just the username
		if ( 'instagram' == $key ) {
			$values['url'] = 'https://instagram.com/' . $values['url'];
		}
		$output .= '<li class="ul-social-link ul-social-link-' . esc_attr($key) . '"><a target="_blank" href="' . esc_url($values['url']) . '">' . wp_kses_post($values['icon']) . '<span class="screen-reader-text">' . esc_attr($key) . '</span></a></li>';
	}

	$output .= '</ul>';

	return $output;

}

/**
 * Function for getting opening hours
 *
 * @since   1.0.0
 *
 * @param   array  $args  Array of optional parameters.
 *
 * @return  string
 */
function ul_get_opening_hours( $args ) {

	$defaults = array(
		'id'          => '',
		'show_closed' => true,
	);
	$args = wp_parse_args( $args, $defaults );

	// Bail, we don't know what data to show
	if ( $args['id'] == '' ) {
		return;
	}

	// Get all our days/times
	$days = array(
		'monday' => array(
			'name'   => __( 'Monday', 'user-locations' ),
			'from'   => ul_get_field( $args['id'], 'opening_hours_monday_from' ),
			'to'     => ul_get_field( $args['id'], 'opening_hours_monday_to' ),
			'from_2' => ul_get_field( $args['id'], 'opening_hours_monday_from_2' ),
			'to_2'   => ul_get_field( $args['id'], 'opening_hours_monday_to_2' ),
		),
		'tuesday' => array(
			'name'   => __( 'Tuesday', 'user-locations' ),
			'from'   => ul_get_field( $args['id'], 'opening_hours_tuesday_from' ),
			'to'     => ul_get_field( $args['id'], 'opening_hours_tuesday_to' ),
			'from_2' => ul_get_field( $args['id'], 'opening_hours_tuesday_from_2' ),
			'to_2'   => ul_get_field( $args['id'], 'opening_hours_tuesday_to_2' ),
		),
		'wednesday' => array(
			'name'   => __( 'Wednesday', 'user-locations' ),
			'from'   => ul_get_field( $args['id'], 'opening_hours_wednesday_from' ),
			'to'     => ul_get_field( $args['id'], 'opening_hours_wednesday_to' ),
			'from_2' => ul_get_field( $args['id'], 'opening_hours_wednesday_from_2' ),
			'to_2'   => ul_get_field( $args['id'], 'opening_hours_wednesday_to_2' ),
		),
		'thursday' => array(
			'name'   => __( 'Thursday', 'user-locations' ),
			'from'   => ul_get_field( $args['id'], 'opening_hours_thursday_from' ),
			'to'     => ul_get_field( $args['id'], 'opening_hours_thursday_to' ),
			'from_2' => ul_get_field( $args['id'], 'opening_hours_thursday_from_2' ),
			'to_2'   => ul_get_field( $args['id'], 'opening_hours_thursday_to_2' ),
		),
		'friday' => array(
			'name'   => __( 'Friday', 'user-locations' ),
			'from'   => ul_get_field( $args['id'], 'opening_hours_friday_from' ),
			'to'     => ul_get_field( $args['id'], 'opening_hours_friday_to' ),
			'from_2' => ul_get_field( $args['id'], 'opening_hours_friday_from_2' ),
			'to_2'   => ul_get_field( $args['id'], 'opening_hours_friday_to_2' ),
		),
		'saturday' => array(
			'name'   => __( 'Saturday', 'user-locations' ),
			'from'   => ul_get_field( $args['id'], 'opening_hours_saturday_from' ),
			'to'     => ul_get_field( $args['id'], 'opening_hours_saturday_to' ),
			'from_2' => ul_get_field( $args['id'], 'opening_hours_saturday_from_2' ),
			'to_2'   => ul_get_field( $args['id'], 'opening_hours_saturday_to_2' ),
		),
		'sunday' => array(
			'name'   => __( 'Sunday', 'user-locations' ),
			'from'   => ul_get_field( $args['id'], 'opening_hours_sunday_from' ),
			'to'     => ul_get_field( $args['id'], 'opening_hours_sunday_to' ),
			'from_2' => ul_get_field( $args['id'], 'opening_hours_sunday_from_2' ),
			'to_2'   => ul_get_field( $args['id'], 'opening_hours_sunday_to_2' ),
		),
	);

	// Check that we have at least one day value
	$has_hours = false;

	foreach ( $days as $key => $day ) {
		if ( $days[$key]['from'] ) {
			$has_hours = true;
			break;
		} elseif ( $days[$key]['to'] ) {
			$has_hours = true;
			break;
		}
	}

	// Bail if we don't have any hours
	if ( ! $has_hours ) {
		return;
	}

	// Start the output
	$output = '';

	$output .= '<table class="ul-opening-hours" style="margin:0;">';

		$multiple_opening_hours = ul_get_field( $args['id'], 'opening_hours_multiple' );

		// Loop through em
		foreach ( $days as $key => $day ) {

			// Skip if hide closed setting is true, and location is closed this day
			if ( ! $args['show_closed'] && $day['from'] == 'closed' ) {
				continue;
			}

			// Skip if from and to fields are empty (not closed and no times set)
			if ( empty($day['from']) && empty($day['to']) ) {
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

					if ( $day['from'] != 'closed' && $day['to'] != 'closed' ) {
						$output .= '<time itemprop="openingHours" content="' . $day_abbr . ' ' . $day['from'] . '-' . $day['to'] . '">' . $from_formatted . ' - ' . $to_formatted . '</time>';
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

	$output .= '</table>';

	return $output;
}

/**
 * TODO
 */
function ul_get_map( $location_parent_id ) {

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
