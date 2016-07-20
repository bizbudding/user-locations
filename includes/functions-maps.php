<?php

/**
 * Maps shortcode handler
 *
 * @since 0.1
 *
 * @param array $atts Array of shortcode parameters.
 *
 * @return string
 */
function wpseo_local_show_map( $atts ) {
	global $map_counter, $wpseo_enqueue_geocoder, $wpseo_map;

	$options   = '';
	$tax_query = array();

	// // Backwards compatibility for scrollable / zoomable functions.
	// if ( is_array( $atts ) && ! array_key_exists( 'zoomable', $atts ) ) {
	// 	$atts['zoomable'] = ( isset( $atts['scrollable'] ) ) ? $atts['scrollable'] : true;
	// }

	$atts = ul_check_falses( shortcode_atts( array(
		'id'               => '',
		'term_id'          => '',
		'center'           => '',
		'width'            => 400,
		'height'           => 300,
		'zoom'             => -1,
		'show_route'       => true,
		'show_state'       => true,
		'show_country'     => false,
		'show_url'         => false,
		'show_email'       => false,
		'map_style'        => ( isset( $options['map_view_style'] ) ) ? $options['map_view_style'] : 'ROADMAP',
		'scrollable'       => true,
		'draggable'        => true,
		'show_route_label' => ( isset( $options['show_route_label'] ) && ! empty( $options['show_route_label'] ) ) ? $options['show_route_label'] : __( 'Get directions', 'user-locations' ),
		'from_sl'          => false,
		'echo'             => false,
	), $atts, 'wpseo_local_show_map' ) );

	if ( ! isset( $map_counter ) ) {
		$map_counter = 0;
	}
	else {
		$map_counter++;
	}

	$location_array     = $lats = $longs = array();
	$location_array_str = '';

	$default_custom_marker = '';

	if ( get_post_type() == 'location_page' ) {
		if ( ( $atts['id'] == '' || $atts['id'] == 'current' ) && ! is_post_type_archive() ) {
			$atts['id'] = get_queried_object_id();
		}

		if ( is_post_type_archive() && ( $atts['id'] == '' || $atts['id'] == 'current' ) ) {
			return '';
		}
	}
	else if ( $atts['id'] != 'all' && empty( $atts['id'] ) ) {
		return ( true == is_singular( 'location_page' ) ) ? __( 'Please provide a post ID when using this shortcode outside a Locations singular page', 'yoast-local-seo' ) : '';
	}

	$location_ids = explode( ',', $atts['id'] );
	if ( $atts['id'] == 'all' || ( $atts['id'] != 'all' && count( $location_ids ) > 1 ) ) {
		$args = array(
			'post_type'      => 'location_page',
			'posts_per_page' => ( $atts['id'] == 'all' ) ? -1 : count( $location_ids ),
			'post_parent'	 => 0,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'location',
					'value'   => '',
					'compare' => '!=',
				),
			),
		);

		if ( count( $location_ids ) > 1 ) {
			$args['post__in'] = $location_ids;
		}

		$location_ids = get_posts( $args );
	}

	foreach ( $location_ids as $location_id ) {

		$location = get_field( 'location', $location_id );

		$tmp_array = array(
			'location_name'      => get_the_title( $location_id ),
			'location_url'       => get_permalink( $location_id ),
			'location_email'     => ul_get_field( $location_id, 'email' ),
			// 'location_address'   => ul_get_field( $location_id, 'address_street' ),
			'location_address'   => $location['address'],
			'location_city'      => ul_get_field( $location_id, 'address_city' ),
			'location_state'     => ul_get_field( $location_id, 'address_state' ),
			'location_zipcode'   => ul_get_field( $location_id, 'address_postcode' ),
			'location_country'   => ul_get_field( $location_id, 'address_country' ),
			'location_phone'     => ul_get_field( $location_id, 'phone' ),
			'location_phone_2nd' => ul_get_field( $location_id, 'phone_2' ),
			'coordinates_lat'    => $location['lat'],
			'coordinates_long'   => $location['lng'],
			'custom_marker'      => $default_custom_marker,
		);

		$location_array[] = $tmp_array;
	}

	$noscript_output = '<ul>';
	foreach ( $location_array as $key => $location ) {

		if ( $location['coordinates_lat'] != '' && $location['coordinates_long'] != '' ) {
			$location_array_str .= "location_data.push( {
				'name': '" . wpseo_cleanup_string( $location['location_name'] ) . "',
				'url': '" . wpseo_cleanup_string( $location['location_url'] ) . "',
				'zip_city': '" . wpseo_cleanup_string( $location['location_address'] ) . "',
				'country': '" . User_Locations()->fields->get_country( $location['location_country'] ) . "',
				'show_country': " . ( ( $atts['show_country'] ) ? 'true' : 'false' ) . ",
				'url': '" . esc_url( $location['location_url'] ) . "',
				'show_url': " . ( ( $atts['show_url'] ) ? 'true' : 'false' ) . ",
				'email': '" . $location['location_email'] . "',
				'show_email': " . ( ( $atts['show_email'] ) ? 'true' : 'false' ) . ",
				'phone': '" . wpseo_cleanup_string( $location['location_phone'] ) . "',
				'phone_2nd': '" . wpseo_cleanup_string( $location['location_phone_2nd'] ) . "',
				'lat': " . wpseo_cleanup_string( $location['coordinates_lat'] ) . ",
				'long': " . wpseo_cleanup_string( $location['coordinates_long'] ) . ",
				'custom_marker': '" . wpseo_cleanup_string( $location['custom_marker'] ) . "'
			} );\n";
		}

		$noscript_output .= '<li><a href="' . $location['location_url'] . '">' . $location['location_name'] . '</a></li>';
		$noscript_output .= '<li><a href="mailto:' . $location['location_email'] . '">' . $location['location_email'] . '</a></li>';

		$full_address = $location['location_address'] . ', ' . $location['location_city'] . ( ( strtolower( $location['location_country'] ) == 'us' ) ? ', ' . $location['location_state'] : '' ) . ', ' . $location['location_zipcode'] . ', ' . User_Locations()->fields->get_country( $location['location_country'] );

		$location_array[ $key ]['full_address'] = $full_address;

		$lats[]  = $location['coordinates_lat'];
		$longs[] = $location['coordinates_long'];
	}
	$noscript_output .= '</ul>';

	$map                    = '';
	$wpseo_enqueue_geocoder = true;


	if ( ! is_array( $lats ) || empty( $lats ) || ! is_array( $longs ) || empty( $longs ) ) {
		return;
	}

	// if ( $atts['center'] === '' ) {
		$center_lat  = ( min( $lats ) + ( ( max( $lats ) - min( $lats ) ) / 2 ) );
		$center_long = ( min( $longs ) + ( ( max( $longs ) - min( $longs ) ) / 2 ) );
	// }
	// else {
	// 	$center_lat  = get_post_meta( $atts['center'], '_wpseo_coordinates_lat', true );
	// 	$center_long = get_post_meta( $atts['center'], '_wpseo_coordinates_long', true );
	// }

	// Default to zoom 10 if there's only one location as a center + bounds would zoom in far too much.
	if ( -1 == $atts['zoom'] && 1 === count( $location_array ) ) {
		$atts['zoom'] = 10;
	}

	if ( $location_array_str != '' ) {

		$wpseo_map .= '<script type="text/javascript">
			var map_' . $map_counter . ';
			var directionsDisplay_' . $map_counter . ';

			function wpseo_map_init' . ( ( $map_counter != 0 ) ? '_' . $map_counter : '' ) . '() {
				var location_data = new Array();' . PHP_EOL . $location_array_str . '
				map_' . $map_counter . ' = wpseo_show_map( location_data, ' . $map_counter . ', ' . $center_lat . ', ' . $center_long . ', ' . $atts['zoom'] . ', "' . $atts['map_style'] . '", "' . $atts['scrollable'] . '", "' . $atts['draggable'] . '" );
				directionsDisplay_' . $map_counter . ' = wpseo_get_directions(map_' . $map_counter . ', location_data, ' . $map_counter . ', "' . $atts['show_route'] . '");
			}

			if( window.addEventListener )
				window.addEventListener( "load", wpseo_map_init' . ( ( $map_counter != 0 ) ? '_' . $map_counter : '' ) . ', false );
			else if(window.attachEvent )
				window.attachEvent( "onload", wpseo_map_init' . ( ( $map_counter != 0 ) ? '_' . $map_counter : '' ) . ');
		</script>' . PHP_EOL;

		// Override(reset) the setting for images inside the map.
		$map .= '<div id="map_canvas' . ( ( $map_counter != 0 ) ? '_' . $map_counter : '' ) . '" class="wpseo-map-canvas" style="max-width: 100%; width: ' . $atts['width'] . 'px; height: ' . $atts['height'] . 'px;">' . $noscript_output . '</div>';

		$route_tag = apply_filters( 'wpseo_local_location_route_title_name', 'h3' );

		if ( $atts['show_route'] && ( ( $atts['id'] != 'all' && strpos( $atts['id'], ',' ) === false ) || $atts['from_sl'] ) ) {
			$map .= '<div id="wpseo-directions-wrapper"' . ( ( $atts['from_sl'] ) ? ' style="display: none;"' : '' ) . '>';
			$map .= '<' . esc_html( $route_tag ) . ' id="wpseo-directions" class="wpseo-directions-heading">' . __( 'Route', 'yoast-local-seo' ) . '</' . esc_html( $route_tag ) . '>';
			$map .= '<form action="" method="post" class="wpseo-directions-form" id="wpseo-directions-form' . ( ( $map_counter != 0 ) ? '_' . $map_counter : '' ) . '" onsubmit="wpseo_calculate_route( map_' . $map_counter . ', directionsDisplay_' . $map_counter . ', ' . $location_array[0]['coordinates_lat'] . ', ' . $location_array[0]['coordinates_long'] . ', ' . $map_counter . '); return false;">';
			$map .= '<p>';
			$map .= __( 'Your location', 'yoast-local-seo' ) . ': <input type="text" size="20" id="origin' . ( ( $map_counter != 0 ) ? '_' . $map_counter : '' ) . '" value="' . ( ! empty( $_REQUEST['wpseo-sl-search'] ) ? esc_attr( $_REQUEST['wpseo-sl-search'] ) : '' ) . '" />';
			$map .= '<input type="submit" class="wpseo-directions-submit" value="' . $atts['show_route_label'] . '">';
			$map .= '<span id="wpseo-noroute" style="display: none;">' . __( 'No route could be calculated.', 'yoast-local-seo' ) . '</span>';
			$map .= '</p>';
			$map .= '</form>';
			$map .= '<div id="directions' . ( ( $map_counter != 0 ) ? '_' . $map_counter : '' ) . '"></div>';
			$map .= '</div>';
		}
	}

	if ( $atts['echo'] ) {
		echo $map;
	}

	return $map;
}

/**
 * Geocode the given address.
 *
 * @param string $address The address that needs to be geocoded.
 *
 * @return array|WP_Error
 */
function wpseo_geocode_address( $address ) {
	$geocode_url = 'https://maps.google.com/maps/api/geocode/json?address=' . urlencode( $address ) . '&oe=utf8&sensor=false';
	$options     = '';
	if ( isset($options['api_key']) && ! empty( $options['api_key'] ) ) {
		$geocode_url .= '&key=' . $options['api_key'];
	}

	$response = wp_remote_get( $geocode_url );

	if ( is_wp_error( $response ) || $response['response']['code'] != 200 || empty( $response['body'] ) ) {
		return new WP_Error( 'wpseo-no-response', "Didn't receive a response from Maps API" );
	}

	$response_body = json_decode( $response['body'] );

	if ( 'OK' != $response_body->status ) {
		$error_code = 'wpseo-zero-results';
		if ( $response_body->status == 'OVER_QUERY_LIMIT' ) {
			$error_code = 'wpseo-query-limit';
		}

		return new WP_Error( $error_code, $response_body->status );
	}

	return $response_body;
}

// Set the global to false, if the script is needed, the global will be set to true.
$wpseo_enqueue_geocoder = false;
/**
 * Places scripts in footer for Google Maps use.
 */
function wpseo_enqueue_geocoder() {
	global $wpseo_enqueue_geocoder, $wpseo_map;

	if ( is_admin() && 'location_page' == get_post_type() ) {
		global $wpseo_enqueue_geocoder;

		$wpseo_enqueue_geocoder = true;
	}

	if ( $wpseo_enqueue_geocoder ) {
		$locale = get_locale();
		$locale = explode( '_', $locale );

		// Check if it might be a language spoken in more than one country.
		if ( isset( $locale[1] ) && in_array( $locale[0], array(
				'en',
				'de',
				'es',
				'it',
				'pt',
				'ro',
				'ru',
				'sv',
				'nl',
				'zh',
				'fr',
			) )
		) {
			$language = $locale[0] . '-' . $locale[1];
		}
		else if ( isset( $locale[1] ) ) {
			$language = $locale[1];
		}
		else {
			$language = $locale[0];
		}

		$options         = get_option( 'wpseo_local' );
		$default_country = isset( $options['default_country'] ) ? $options['default_country'] : '';
		if ( '' != $default_country ) {
			$default_country = User_Locations()->fields->get_country( $default_country );
		}

		wp_enqueue_script( 'maps-geocoder', '//maps.google.com/maps/api/js?sensor=false' . ( ! empty( $language ) ? '&language=' . strtolower( $language ) : '' ), array(), null, true );

		$script_name = 'wp-seo-local-frontend.min.js';
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$script_name = 'wp-seo-local-frontend.js';
		}
		wp_enqueue_script( 'wpseo-local-frontend', plugins_url( 'js/' . $script_name, dirname( __FILE__ ) ), '', WPSEO_LOCAL_VERSION, true );

		wp_localize_script( 'wpseo-local-frontend', 'wpseo_local_data', array(
			'ajaxurl'                => 'admin-ajax.php',
			'has_multiple_locations' => wpseo_has_multiple_locations(),
			'unit_system'            => ! empty( $options['unit_system'] ) ? $options['unit_system'] : 'METRIC',
			'default_country'        => $default_country,
		) );

		echo '<style type="text/css">.wpseo-map-canvas img { max-width: none !important; }</style>' . PHP_EOL;
	}

	echo $wpseo_map;
}

/**
 * This function will clean up the given string and remove all unwanted characters.
 *
 * @param string $string String that has to be cleaned.
 *
 * @uses wpseo_utf8_to_unicode() to convert string to array of unicode characters.
 * @uses wpseo_unicode_to_utf8() to convert the unicode array back to a regular string.
 * @return string The clean string.
 */
function wpseo_cleanup_string( $string ) {
	$string = esc_attr( $string );

	// First generate array of all unicodes of this string.
	$unicode_array = wpseo_utf8_to_unicode( $string );
	foreach ( $unicode_array as $key => $unicode_item ) {
		// Remove unwanted unicode characters.
		if ( in_array( $unicode_item, array( 8232 ) ) ) {
			unset( $unicode_array[ $key ] );
		}
	}

	// Revert back to normal string.
	$string = wpseo_unicode_to_utf8( $unicode_array );

	return $string;
}

/**
 * Converts a string to array of unicode characters.
 *
 * @param string $str String that has to be converted to unicde array.
 *
 * @return array Array of unicode characters.
 */
function wpseo_utf8_to_unicode( $str ) {
	$unicode     = array();
	$values      = array();
	$looking_for = 1;

	for ( $i = 0; $i < strlen( $str ); $i++ ) {
		$this_value = ord( $str[ $i ] );

		if ( $this_value < 128 ) {
			$unicode[] = $this_value;
		}
		else {
			if ( count( $values ) == 0 ) {
				$looking_for = ( $this_value < 224 ) ? 2 : 3;
			}

			$values[] = $this_value;
			if ( count( $values ) == $looking_for ) {
				$number = ( $looking_for == 3 ) ? ( ( ( $values[0] % 16 ) * 4096 ) + ( ( $values[1] % 64 ) * 64 ) + ( $values[2] % 64 ) ) : ( ( ( $values[0] % 32 ) * 64 ) + ( $values[1] % 64 ) );

				$unicode[]   = $number;
				$values      = array();
				$looking_for = 1;
			}
		}
	}

	return $unicode;
}

/**
 * Converts unicode character array back to regular string.
 *
 * @param array $string_array Array of unicode characters.
 *
 * @return string Converted string.
 */
function wpseo_unicode_to_utf8( $string_array ) {
	$utf8 = '';

	foreach ( $string_array as $unicode ) {
		if ( $unicode < 128 ) {
			$utf8 .= chr( $unicode );
		}
		elseif ( $unicode < 2048 ) {
			$utf8 .= chr( 192 + ( ( $unicode - ( $unicode % 64 ) ) / 64 ) );
			$utf8 .= chr( 128 + ( $unicode % 64 ) );
		}
		else {
			$utf8 .= chr( 224 + ( ( $unicode - ( $unicode % 4096 ) ) / 4096 ) );
			$utf8 .= chr( 128 + ( ( ( $unicode % 4096 ) - ( $unicode % 64 ) ) / 64 ) );
			$utf8 .= chr( 128 + ( $unicode % 64 ) );
		}
	}

	return $utf8;
}

/**
 * Get the custom marker from categories or general Local SEO settings.
 *
 * @param int    $post_id  The post id.
 * @param string $taxonomy String The taxonomy name from the location category.
 *
 * @return false|string
 */
function wpseo_local_get_custom_marker( $post_id = null, $taxonomy = '' ) {

	$custom_marker = '';

	if ( ! empty( $post_id ) && ! empty( $taxonomy ) ) {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( false !== $terms ) {
			$terms = wp_list_pluck( $terms, 'term_id' );
		}
		$terms = apply_filters( 'wpseo_local_custom_marker_order', $terms );

		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term_id ) {
				$tax_meta = WPSEO_Taxonomy_Meta::get_term_meta( (int) $term_id, $taxonomy );

				if ( isset( $tax_meta['wpseo_local_custom_marker'] ) && ! empty( $tax_meta['wpseo_local_custom_marker'] ) ) {
					$custom_marker = wp_get_attachment_url( $tax_meta['wpseo_local_custom_marker'] );
				}

				break;
			}
		}
	}
	else {
		$options = get_option( 'wpseo_local' );
		if ( isset( $options['custom_marker'] ) && intval( $options['custom_marker'] ) ) {
			$custom_marker = wp_get_attachment_url( $options['custom_marker'] );
		}
	}

	return $custom_marker;
}
