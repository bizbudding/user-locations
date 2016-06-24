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
function userlocations_local_show_address( $args ) {
	$args = user_locations_check_falses( shortcode_atts( array(
		'id'                 => '',
		'hide_name'          => false,
		'show_state'         => true,
		'show_country'       => true,
		'show_phone'         => true,
		'show_phone_2'       => true,
		'show_fax'           => true,
		'show_email'         => true,
		'show_url'           => false,
		'show_vat'           => false,
		'show_tax'           => false,
		'show_coc'           => false,
		'show_logo'          => false,
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
	), $args, 'userlocations_local_show_address' ) );

	$options = get_option( 'userlocations_local' );
	if ( isset( $options['hide_opening_hours'] ) && $options['hide_opening_hours'] == 'on' ) {
		$args['show_opening_hours'] = false;
	}

	$is_postal_address = false;

	if ( userlocations_has_multiple_locations() ) {
		if ( get_post_type() == 'userlocations_locations' ) {
			if ( ( $args['id'] == '' || $args['id'] == 'current' ) && ! is_post_type_archive() ) {
				$args['id'] = get_queried_object_id();
			}

			if ( is_post_type_archive() && ( $args['id'] == '' || $args['id'] == 'current' ) ) {
				return '';
			}
		}
		else if ( $args['id'] == '' ) {
			return is_singular() ? __( 'Please provide a post ID if you want to show an address outside a Locations singular page', 'yoast-local-seo' ) : '';
		}

		// Get the location data if its already been entered.
		$business_name          = get_the_title( $args['id'] );
		$business_type          = get_post_meta( $args['id'], '_userlocations_business_type', true );
		$business_address       = get_post_meta( $args['id'], '_userlocations_business_address', true );
		$business_city          = get_post_meta( $args['id'], '_userlocations_business_city', true );
		$business_state         = get_post_meta( $args['id'], '_userlocations_business_state', true );
		$business_zipcode       = get_post_meta( $args['id'], '_userlocations_business_zipcode', true );
		$business_country       = get_post_meta( $args['id'], '_userlocations_business_country', true );
		$business_phone         = get_post_meta( $args['id'], '_userlocations_business_phone', true );
		$business_phone_2nd     = get_post_meta( $args['id'], '_userlocations_business_phone_2nd', true );
		$business_fax           = get_post_meta( $args['id'], '_userlocations_business_fax', true );
		$business_email         = get_post_meta( $args['id'], '_userlocations_business_email', true );
		$business_vat           = get_post_meta( $args['id'], '_userlocations_business_vat_id', true );
		$business_tax           = get_post_meta( $args['id'], '_userlocations_business_tax_id', true );
		$business_coc           = get_post_meta( $args['id'], '_userlocations_business_coc_id', true );
		$business_url           = get_post_meta( $args['id'], '_userlocations_business_url', true );
		$business_location_logo = get_post_meta( $args['id'], '_userlocations_business_location_logo', true );
		$is_postal_address      = get_post_meta( $args['id'], '_userlocations_is_postal_address', true );
		$is_postal_address      = $is_postal_address == '1';

		if ( empty( $business_url ) ) {
			$business_url = get_permalink( $args['id'] );
		}
	}
	else {
		$business_name      = isset( $options['location_name'] ) ? $options['location_name'] : '';
		$business_type      = isset( $options['business_type'] ) ? $options['business_type'] : '';
		$business_address   = isset( $options['location_address'] ) ? $options['location_address'] : '';
		$business_city      = isset( $options['location_city'] ) ? $options['location_city'] : '';
		$business_state     = isset( $options['location_state'] ) ? $options['location_state'] : '';
		$business_zipcode   = isset( $options['location_zipcode'] ) ? $options['location_zipcode'] : '';
		$business_country   = isset( $options['location_country'] ) ? $options['location_country'] : '';
		$business_phone     = isset( $options['location_phone'] ) ? $options['location_phone'] : '';
		$business_phone_2nd = isset( $options['location_phone_2nd'] ) ? $options['location_phone_2nd'] : '';
		$business_fax       = isset( $options['location_fax'] ) ? $options['location_fax'] : '';
		$business_email     = isset( $options['location_email'] ) ? $options['location_email'] : '';
		$business_url       = ! empty( $options['location_url'] ) ? $options['location_url'] : userlocations_xml_sitemaps_base_url( '' );
		$business_vat       = isset( $options['location_vat_id'] ) ? $options['location_vat_id'] : '';
		$business_tax       = isset( $options['location_tax_id'] ) ? $options['location_tax_id'] : '';
		$business_coc       = isset( $options['location_coc_id'] ) ? $options['location_coc_id'] : '';
	}

	if ( '' == $business_type ) {
		$business_type = 'LocalBusiness';
	}

	/*
	* This array can be used in a filter to change the order and the labels of contact details
	*/
	$business_contact_details = array(
		array(
			'key'   => 'phone',
			'label' => __( 'Phone', 'yoast-local-seo' ),
		),
		array(
			'key'   => 'phone_2',
			'label' => __( 'Secondary phone', 'yoast-local-seo' ),
		),
		array(
			'key'   => 'fax',
			'label' => __( 'Fax', 'yoast-local-seo' ),
		),
		array(
			'key'   => 'email',
			'label' => __( 'Email', 'yoast-local-seo' ),
		),
		array(
			'key'   => 'url',
			'label' => __( 'URL', 'yoast-local-seo' ),
		),
		array(
			'key'   => 'vat',
			'label' => __( 'VAT ID', 'yoast-local-seo' ),
		),
		array(
			'key'   => 'tax',
			'label' => __( 'Tax ID', 'yoast-local-seo' ),
		),
		array(
			'key'   => 'coc',
			'label' => __( 'Chamber of Commerce ID', 'yoast-local-seo' ),
		),
	);

	$business_contact_details = apply_filters( 'userlocations_local_contact_details', $business_contact_details );

	$tag_title_open  = '';
	$tag_title_close = '';
	if ( ! $args['oneline'] ) {
		if ( ! $args['from_widget'] ) {
			$tag_name        = apply_filters( 'userlocations_local_location_title_tag_name', 'h3' );
			$tag_title_open  = '<' . esc_html( $tag_name ) . '>';
			$tag_title_close = '</' . esc_html( $tag_name ) . '>';
		}
		else if ( $args['from_widget'] && $args['widget_title'] == '' ) {
			$tag_title_open  = $args['before_title'];
			$tag_title_close = $args['after_title'];
		}
	}

	$output = '<div id="userlocations_location-' . esc_attr( $args['id'] ) . '" class="userlocations-location" itemscope itemtype="http://schema.org/' . ( ( $is_postal_address ) ? 'PostalAddress' : esc_attr( $business_type ) ) . '">';

	if ( false == $args['hide_name'] ) {
		$output .= $tag_title_open . ( ( $args['from_sl'] ) ? '<a href="' . esc_url( $business_url ) . '">' : '' ) . '<span class="userlocations-business-name" itemprop="name">' . esc_html( $business_name ) . '</span>' . ( ( $args['from_sl'] ) ? '</a>' : '' ) . $tag_title_close;
	}

	if ( $args['show_logo'] ) {
		if ( empty( $business_location_logo ) ) {
			$userlocations_options          = get_option( 'userlocations' );
			$business_location_logo = $userlocations_options['company_logo'];
		}

		if ( ! empty( $business_location_logo ) ) {
			$output .= '<figure itemprop="logo" itemscope itemtype="http://schema.org/ImageObject">';
			$output .= '<img itemprop="url" src="' . $business_location_logo . '" />';
			$output .= '</figure>';
		}
	}

	$output .= '<' . ( ( $args['oneline'] ) ? 'span' : 'div' ) . ' ' . ( ( $is_postal_address ) ? '' : 'itemprop="address" itemscope itemtype="http://schema.org/PostalAddress"' ) . ' class="userlocations-address-wrapper">';

	// Output city/state/zipcode in right format.
	$address_format_output = userlocations_local_get_address_format( $business_address, $args['oneline'], $business_zipcode, $business_city, $business_state, $args['show_state'] );

	// Remove first comma from oneline addresses when business name is hidden.
	if ( ! empty( $address_format_output ) && true === $args['hide_name'] && true === $args['oneline'] ) {
		$address_format_output = substr( $address_format_output, 2 );
	}
	$output .= $address_format_output;

	if ( $args['show_country'] && ! empty( $business_country ) ) {
		$output .= ( $args['oneline'] ) ? ', ' : ' ';
	}

	if ( $args['show_country'] && ! empty( $business_country ) ) {
		$output .= '<' . ( ( $args['oneline'] ) ? 'span' : 'div' ) . '  class="country-name" itemprop="addressCountry">' . WPSEO_Local_Frontend::get_country( $business_country ) . '</' . ( ( $args['oneline'] ) ? 'span' : 'div' ) . '>';
	}
	$output .= '</' . ( ( $args['oneline'] ) ? 'span' : 'div' ) . '>';

	$details_output = '';
	foreach ( $business_contact_details as $order => $details ) {

		if ( 'phone' == $details['key'] && $args['show_phone'] && ! empty( $business_phone ) ) {
			$details_output .= sprintf( '<span class="userlocations-phone">%s: <a href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $business_phone ) ) . '" class="tel"><span itemprop="telephone">' . esc_html( $business_phone ) . '</span></a></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
		if ( 'phone_2' == $details['key'] && $args['show_phone_2'] && ! empty( $business_phone_2nd ) ) {
			$details_output .= sprintf( '<span class="userlocations-phone2nd">%s: <a href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $business_phone_2nd ) ) . '" class="tel">' . esc_html( $business_phone_2nd ) . '</a></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
		if ( 'fax' == $details['key'] && $args['show_fax'] && ! empty( $business_fax ) ) {
			$details_output .= sprintf( '<span class="userlocations-fax">%s: <span class="tel" itemprop="faxNumber">' . esc_html( $business_fax ) . '</span></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
		if ( 'email' == $details['key'] && $args['show_email'] && ! empty( $business_email ) ) {
			$details_output .= sprintf( '<span class="userlocations-email">%s: <a href="' . esc_url( 'mailto:' . $business_email ) . '" itemprop="email">' . esc_html( $business_email ) . '</a></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
		if ( 'url' == $details['key'] && $args['show_url'] ) {
			$details_output .= sprintf( '<span class="userlocations-url">%s: <a href="' . esc_url( $business_url ) . '" itemprop="url">' . esc_html( $business_url ) . '</a></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
		if ( 'vat' == $details['key'] && $args['show_vat'] && ! empty( $business_vat ) ) {
			$details_output .= sprintf( '<span class="userlocations-vat">%s: <span itemprop="vatID">' . esc_html( $business_vat ) . '</span></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
		if ( 'tax' == $details['key'] && $args['show_tax'] && ! empty( $business_tax ) ) {
			$details_output .= sprintf( '<span class="userlocations-tax">%s: <span itemprop="taxID">' . esc_html( $business_tax ) . '</span></span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
		}
		if ( 'coc' == $details['key'] && $args['show_coc'] && ! empty( $business_coc ) ) {
			$details_output .= sprintf( '<span class="userlocations-vat">%s: ' . esc_html( $business_coc ) . '</span>' . ( ( $args['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
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
