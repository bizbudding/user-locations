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

		add_filter( 'genesis_post_info', 			  array( $this, 'maybe_remove_post_info' ), 99 );
		add_filter( 'genesis_post_meta', 			  array( $this, 'maybe_remove_post_meta' ), 99 );

		add_action( 'user_locations_opengraph',       array( $this, 'opengraph_location' ) );
		add_filter( 'user_locations_opengraph_type',  array( $this, 'opengraph_type' ) );
		add_filter( 'user_locations_opengraph_title', array( $this, 'opengraph_title_filter' ) );

		// Genesis 2.0 specific, this filters the Schema.org output Genesis 2.0 comes with.
		add_filter( 'genesis_attr_body',  array( $this, 'genesis_contact_page_schema' ), 20, 1 );
		add_filter( 'genesis_attr_entry', array( $this, 'genesis_empty_schema' ), 20, 1 );

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
	 * Filter the Genesis schema for an attribute and empty them
	 *
	 * @since  1.0.0
	 *
	 * @link   https://yoast.com/schema-org-genesis-2-0/
	 *
	 * @param  array $attr The Schema.org attributes.
	 *
	 * @return array $attr
	 */
	function genesis_empty_schema( $attr ) {
		$attr['itemtype']  = '';
		$attr['itemprop']  = '';
		$attr['itemscope'] = '';
		return $attr;
	}

	/**
	 * Filter the Genesis schema for an attribute itemprop and set it to name
	 *
	 * @since  1.0.0
	 *
	 * @link   https://yoast.com/schema-org-genesis-2-0/
	 *
	 * @param  array $attr The Schema.org attributes.
	 *
	 * @return array $attr
	 */
	function genesis_itemprop_name_og( $attr ) {
		$attr['itemprop'] = 'name';
		return $attr;
	}

	/**
	 * Output opengraph location tags.
	 *
	 * @link  https://developers.facebook.com/docs/reference/opengraph/object-type/business.business
	 * @link  https://developers.facebook.com/docs/reference/opengraph/object-type/restaurant.restaurant
	 *
	 * @since 1.0.0
	 */
	function opengraph_location() {
        // TODO
	}

	/**
	 * Change the OpenGraph type when current post type is a location.
	 *
	 * @link   https://developers.facebook.com/docs/reference/opengraph/object-type/business.business
	 * @link   https://developers.facebook.com/docs/reference/opengraph/object-type/restaurant.restaurant
	 *
	 * @param  string $type The OpenGraph type to be altered.
	 *
	 * @return string
	 */
	function opengraph_type( $type ) {
        // TODO
	}

	/**
	 * Filter the OG title output
	 *
	 * @param  string $title The title to be filtered.
	 *
	 * @return string
	 */
	function opengraph_title_filter( $title ) {
        // TODO
	}

}
