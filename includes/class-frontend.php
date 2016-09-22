<?php
/**
 * @package User_Locations
 * @uses    Most code from Yoast Local SEO
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class User_Locations_Frontend
 *
 * Handles all frontend functionality.
 */
class User_Locations_Frontend {

    /**
	 * @var Parisi_Functions The one true Parisi_Functions
	 * @since 1.0.0
	 */
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new User_Locations_Frontend;
			// Methods
			self::$instance->init();
		}
		return self::$instance;
	}

	function init() {
		// Hook in the location menu ( not OOP so it can easily be removed/moved )
		add_action( 'genesis_after_header', 'ul_do_location_menu', 20 );

		// Hook in the location posts ( not OOP so it can easily be removed/moved )
		add_action( 'genesis_after_loop', 'ul_do_location_posts' );

		add_filter( 'body_class', 				array( $this, 'location_content_body_class' ) );
		add_filter( 'genesis_post_info', 		array( $this, 'maybe_remove_post_info' ), 99 );
		add_filter( 'genesis_post_meta', 		array( $this, 'maybe_remove_post_meta' ), 99 );

		add_action( 'wp_head',       			array( $this, 'opengraph_location' ) );
		add_filter( 'wpseo_opengraph_type',  	array( $this, 'opengraph_type' ) );
		add_filter( 'wpseo_opengraph_title',	array( $this, 'opengraph_title_filter' ) );

		// Genesis 2.0 specific, this filters the Schema.org output Genesis 2.0 comes with.
		add_filter( 'genesis_attr_body',  		array( $this, 'genesis_contact_page_schema' ), 20, 1 );
	}

	/**
	 * Add body class when location content
	 */
	public function location_content_body_class( $classes ) {
		if ( ul_is_location_content() ) {
			$classes[] = 'location-content';
		}
		return $classes;
	}

	/**
	 * Remove post info from location pages
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function maybe_remove_post_info( $post_info ) {
		if ( is_singular('location_page') ) {
			$post_info = '';
		}
		return $post_info;
	}

	/**
	 * Remove post footer meta from location pages
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function maybe_remove_post_meta( $post_meta ) {
		if ( is_singular('location_page') ) {
			$post_meta = '';
		}
		return $post_meta;
	}

	/**
	 * Filter the Genesis page schema and force it to ContactPage for Location pages
	 *
	 * @since  1.1.7
	 *
	 * @link   https://yoast.com/schema-org-genesis-2-0/
	 * @link   http://schema.org/ContactPage
	 *
	 * @param  array $attr The Schema.org attributes.
	 *
	 * @return array $attr
	 */
	function genesis_contact_page_schema( $attr ) {
		if ( ul_is_location_parent_page() ) {
			$attr['itemtype']  = 'http://schema.org/ContactPage';
			$attr['itemprop']  = '';
			$attr['itemscope'] = 'itemscope';
		}
		return $attr;
	}

	/**
	 * Output opengraph location tags.
	 *
	 * @link    https://developers.facebook.com/docs/reference/opengraph/object-type/business.business
	 * @link    https://developers.facebook.com/docs/reference/opengraph/object-type/restaurant.restaurant
	 *
	 * @since   1.2.0
	 *
	 * @return  null
	 */
	function opengraph_location() {
		if ( ! ul_is_location_parent_page() ) {
			return '';
		}

		$location_id = get_the_ID();

		if ( ! $location_id ) {
			return '';
		}

	    $location = get_field( 'location', $location_id );
	    if ( $location ) {
			echo '<meta property="place:location:latitude" content="' . esc_attr( $location['lat'] ) . '"/>' . "\n";
			echo '<meta property="place:location:longitude" content="' . esc_attr( $location['lng'] ) . '"/>' . "\n";
	    }

		$street = ul_get_field( $location_id, 'address_street' );
		if ( $street ) {
			echo '<meta property="business:contact_data:street_address" content="' . esc_attr( $street ) . '"/>' . "\n";
		}

		$city = ul_get_field( $location_id, 'address_city' );
		if ( $city ) {
			echo '<meta property="business:contact_data:locality" content="' . esc_attr( $city ) . '"/>' . "\n";
		}

		$state = ul_get_field( $location_id, 'address_state' );
		if ( $state ) {
			echo '<meta property="business:contact_data:region" content="' . esc_attr( $state ) . '"/>' . "\n";
		}

		$country = ul_get_field( $location_id, 'address_country' );
		if ( $country ) {
			$country_name = User_Locations()->fields->get_country($country);
			if ( $country_name ) {
				echo '<meta property="business:contact_data:country" content="' . esc_attr($country_name) . '"/>' . "\n";
			}
		}

		$postcode = ul_get_field( $location_id, 'address_postcode' );
		if ( $postcode ) {
			echo '<meta property="business:contact_data:postal_code" content="' . esc_attr( $postcode ) . '"/>' . "\n";
		}

		$url = ul_get_field( $location_id, 'location_url' );
		if ( $url ) {
			echo '<meta property="business:contact_data:website" content="' . trailingslashit( esc_url($url) ) . '"/>' . "\n";
		}

		$email = ul_get_field( $location_id, 'email' );
		if ( $email ) {
			echo '<meta property="business:contact_data:email" content="' . esc_attr( $email ) . '"/>' . "\n";
		}

		$phone = ul_get_field( $location_id, 'phone' );
		if ( $phone ) {
			echo '<meta property="business:contact_data:phone_number" content="' . esc_attr( $phone ) . '"/>' . "\n";
		}

		$fax = ul_get_field( $location_id, 'fax' );
		if ( $fax ) {
			echo '<meta property="business:contact_data:fax_number" content="' . esc_attr( $fax ) . '"/>' . "\n";
		}
	}

	/**
	 * Change the OpenGraph type when current post type is a location.
	 *
	 * @since  1.2.0
	 *
	 * @link   https://developers.facebook.com/docs/reference/opengraph/object-type/business.business
	 * @link   https://developers.facebook.com/docs/reference/opengraph/object-type/restaurant.restaurant
	 *
	 * @param  string $type The OpenGraph type to be altered.
	 *
	 * @return string
	 */
	function opengraph_type( $type ) {
		if ( ! ul_is_location_parent_page() ) {
			return $type;
		}
		$business_type = ul_get_field( get_the_ID(), 'location_type' );
		switch ( $business_type ) {
			case 'BarOrPub':
			case 'Winery':
			case 'Restaurant':
				$type = 'restaurant.restaurant';
				break;
			default:
				$type = 'business.business';
				break;
		}
		return $type;
	}

	/**
	 * Filter the OG title output
	 *
	 * @param  string $title The title to be filtered.
	 *
	 * @return string
	 */
	function opengraph_title_filter( $title ) {
		if ( ! ul_is_location_content() ) {
			return $title;
		}
		if ( ul_is_location_parent_page() ) {
			$title = get_the_title( get_the_ID() ) . ' - ' . $title;
		}
		elseif ( ul_is_location_child_page() ) {
			$parent_id = ul_get_location_parent_page_id();
			if ( $parent_id ) {
				$title = get_the_title( $parent_id ) . ' - ' . $title;
			}
		}
		elseif ( is_singular('post') ) {
			$location_id = ul_get_location_parent_page_id_from_post_id( get_the_ID() );
			if ( $location_id ) {
				$title = get_the_title( $location_id ) . ' - ' . $title;
			}
		}
		return $title;
	}

}
