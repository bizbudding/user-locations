<?php
/**
 * @package User_Locations
 */

/**
 * Address shortcode handler
 *
 * @since  1.0.0
 *
 * @param  array $args Array of shortcode parameters.
 *
 * @return string
 */
function userlocations_show_address( $args ) {
	$args = userlocations_check_falses( shortcode_atts( array(
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
	), $args, 'userlocations_show_address' ) );

	$is_postal = false;

	// Bail, we don't know what data to show
	if ( $args['id'] == '' ) {
		return;
	}

	// Get the location data if its already been entered.
	$name      = userlocations_get_field( $args['id'], 'display_name' );
	$type      = userlocations_get_field( $args['id'], 'location_type' );
	$is_postal = userlocations_get_field( $args['id'], 'address_is_postal' );
	$street    = userlocations_get_field( $args['id'], 'address_street' );
	$street_2  = userlocations_get_field( $args['id'], 'address_street_2' );
	$city      = userlocations_get_field( $args['id'], 'address_city' );
	$state     = userlocations_get_field( $args['id'], 'address_state' );
	$postcode  = userlocations_get_field( $args['id'], 'address_postcode' );
	$country   = userlocations_get_field( $args['id'], 'address_country' );
	$phone     = userlocations_get_field( $args['id'], 'phone' );
	$phone_2nd = userlocations_get_field( $args['id'], 'phone_2' );
	$fax       = userlocations_get_field( $args['id'], 'fax' );
	$email     = userlocations_get_field( $args['id'], 'user_email' );
	$url       = userlocations_get_field( $args['id'], 'user_url' );

	if ( empty( $url ) ) {
		$url = get_author_posts_url( $args['id'] );
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

	$contact_details = apply_filters( 'userlocations_contact_details', $contact_details );

	$tag_title_open  = '';
	$tag_title_close = '';
	if ( ! $args['oneline'] ) {
		if ( ! $args['from_widget'] ) {
			$tag_name        = apply_filters( 'userlocations_title_tag_name', 'h3' );
			$tag_title_open  = '<' . esc_html( $tag_name ) . '>';
			$tag_title_close = '</' . esc_html( $tag_name ) . '>';
		}
		else if ( $args['from_widget'] && $args['widget_title'] == '' ) {
			$tag_title_open  = $args['before_title'];
			$tag_title_close = $args['after_title'];
		}
	}

	$output = '<div id="userlocations_location-' . esc_attr( $args['id'] ) . '" class="userlocations-location" itemscope itemtype="http://schema.org/' . ( ( $is_postal ) ? 'PostalAddress' : esc_attr( $type ) ) . '">';

	if ( false == $args['hide_name'] ) {
		$output .= $tag_title_open . ( ( $args['from_sl'] ) ? '<a href="' . esc_url( $url ) . '">' : '' ) . '<span class="userlocations-business-name" itemprop="name">' . esc_html( $name ) . '</span>' . ( ( $args['from_sl'] ) ? '</a>' : '' ) . $tag_title_close;
	}

	$output .= '<' . ( ( $args['oneline'] ) ? 'span' : 'div' ) . ' ' . ( ( $is_postal ) ? '' : 'itemprop="address" itemscope itemtype="http://schema.org/PostalAddress"' ) . ' class="userlocations-address-wrapper">';

	// Output city/state/zipcode in right format.
	$street_format_output = userlocations_get_address_format( $street, $args['oneline'], $postcode, $city, $state, $args['show_state'] );

	// Remove first comma from oneline addresses when business name is hidden.
	if ( ! empty( $street_format_output ) && true === $args['hide_name'] && true === $args['oneline'] ) {
		$street_format_output = substr( $street_format_output, 2 );
	}
	$output .= $street_format_output;

	// TODO: GET COUNTRY FROM COUNTRY CODE!!!!!
	if ( $args['show_country'] && ! empty( $country ) ) {
		$output .= ( $args['oneline'] ) ? ', ' : ' ';
		$output .= '<' . ( ( $args['oneline'] ) ? 'span' : 'div' ) . '  class="country-name" itemprop="addressCountry">' . WPSEO_Local_Frontend::get_country( $country ) . '</' . ( ( $args['oneline'] ) ? 'span' : 'div' ) . '>';
	}
	$output .= '</' . ( ( $args['oneline'] ) ? 'span' : 'div' ) . '>';

	$details_output = '';
	foreach ( $contact_details as $order => $details ) {

		if ( 'phone' == $details['key'] && $args['show_phone'] && ! empty( $phone ) ) {
			$details_output .= sprintf( '<span class="userlocations-phone">%s: <a href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ) . '" class="tel"><span itemprop="telephone">' . esc_html( $phone ) . '</span></a></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
		if ( 'phone_2' == $details['key'] && $args['show_phone_2'] && ! empty( $phone_2nd ) ) {
			$details_output .= sprintf( '<span class="userlocations-phone2nd">%s: <a href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone_2nd ) ) . '" class="tel">' . esc_html( $phone_2nd ) . '</a></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
		if ( 'fax' == $details['key'] && $args['show_fax'] && ! empty( $fax ) ) {
			$details_output .= sprintf( '<span class="userlocations-fax">%s: <span class="tel" itemprop="faxNumber">' . esc_html( $fax ) . '</span></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
		if ( 'email' == $details['key'] && $args['show_email'] && ! empty( $email ) ) {
			$details_output .= sprintf( '<span class="userlocations-email">%s: <a href="' . esc_url( 'mailto:' . $email ) . '" itemprop="email">' . esc_html( $email ) . '</a></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
		if ( 'url' == $details['key'] && $args['show_url'] ) {
			$details_output .= sprintf( '<span class="userlocations-url">%s: <a href="' . esc_url( $url ) . '" itemprop="url">' . esc_html( $url ) . '</a></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
	}

	if ( '' != $details_output && true == $args['oneline'] ) {
		$output .= ' - ';
	}

	$output .= $details_output;

	if ( $args['show_opening_hours'] ) {
		$args = array(
			'id'          => ( userlocations_has_multiple_locations() ) ? $args['id'] : '',
			'hide_closed' => $args['hide_closed'],
		);
		$output .= '<br/>' . userlocations_local_show_opening_hours( $args, true ) . '<br/>';
	}
	$output .= '</div>';

	if ( $args['comment'] != '' ) {
		$output .= '<div class="userlocations-extra-comment">' . wpautop( html_entity_decode( $args['comment'] ) ) . '</div>';
	}

	if ( $args['echo'] ) {
		echo $output;
	}

	return $output;
}

/**
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
function userlocations_get_address_format( $street = '', $oneline = false, $postcode = '', $city = '', $state = '', $show_state = false, $escape_output = false, $use_tags = true ) {
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
 * Checks whether array keys are meant to mean false but aren't set to false.
 *
 * @since	1.0.0
 *
 * @param   array $args Array to check.
 *
 * @return  array
 */
function userlocations_check_falses( $args ) {
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

/**
 * Helper function to check if viewing a single location package
 * For now, it's all author pages but may change to authors in a specific category/Gettext_Translations::nplurals_and_expression_from_header
 *
 * @since   1.0.0
 *
 * @return  boolean
 */
function userlocations_is_singular_location() {
	if ( is_author() ) {
		return true;
	}
	return false;
}

function userlocations_get_field( $user_id, $name ) {
	return User_Locations()->fields->get_field( $user_id, $name );
}

/**
 * Helper function to get template part
 *
 * userlocations_get_template_part( 'account', 'page' );
 *
 * This will try to load in the following order:
 * 1: wp-content/themes/theme-name/userlocations/account-page.php
 * 2: wp-content/themes/theme-name/userlocations/account.php
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
function userlocations_get_template_part( $slug, $name = null, $load = true, $data = '' ) {
    if ( is_array($data) ) {
	    User_Locations()->templates->set_template_data( $data );
	}
    User_Locations()->templates->get_template_part( $slug, $name, $load );
}