<?php
/**
 * @package User_Locations
 * @uses    Most code from Yoast Local SEO
 */

if ( ! class_exists( 'User_Locations_Frontend' ) ) {

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

		/**
		 * @var array $options Stores the options for this plugin.
		 */
		// var $options = array();

		/**
		 * @var boolean $options Whether to load external stylesheet or not.
		 */
		var $load_styles = false;

		/**
		 * Constructor.
		 */
		function init() {
			// Hook in the location menu ( not OOP so it can easily be removed/moved )
			add_action( 'genesis_after_header', 'ul_do_location_menu', 20 );

			// Create shortcode functionality. Functions are defined in includes/wpseo-local-functions.php because they're also used by some widgets.
			add_shortcode( 'user_locations_address',            'ul_show_address' );
			add_shortcode( 'user_locations_all_locations',      'ul_show_all_locations' );
			add_shortcode( 'user_locations_map',                'ul_show_map' );
			add_shortcode( 'user_locations_opening_hours',      'ul_show_openinghours_shortcode_cb' );

			add_action( 'user_locations_opengraph',       array( $this, 'opengraph_location' ) );
			add_filter( 'user_locations_opengraph_type',  array( $this, 'opengraph_type' ) );
			add_filter( 'user_locations_opengraph_title', array( $this, 'opengraph_title_filter' ) );

			// Genesis 2.0 specific, this filters the Schema.org output Genesis 2.0 comes with.
			add_filter( 'genesis_attr_body',  array( $this, 'genesis_contact_page_schema' ), 20, 1 );
			add_filter( 'genesis_attr_entry', array( $this, 'genesis_empty_schema' ), 20, 1 );

		}

		public function get_location_menu( $user_id ) {
			$output = '';
			$args = array(
				'post_type'              => 'location_page',
				'author'            	 => $user_id,
				'posts_per_page'         => 50,
				'post_status'            => 'publish',
				'post_parent'			 => ul_get_location_parent_page_id( $user_id ),
				'orderby'				 => 'menu_order',
				'order'					 => 'ASC',
				// 'no_found_rows'          => true,
				// 'update_post_meta_cache' => false,
				// 'update_post_term_cache' => false,
			);
			// Allow for filtering of the menu item args
			$args = apply_filters( 'userlocations_location_menu_args', $args );
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

			$output .= '<nav class="nav-location" itemscope="" itemtype="http://schema.org/SiteNavigationElement">';
				$output .= '<div class="wrap">';
					$output .= '<ul id="menu-location-menu" class="menu genesis-nav-menu">';

						// Force a home page as first menu item
						$output .= '<li class="menu-item first-menu-item"><a href="' . ul_get_location_parent_page_url() . '" itemprop="url"><span itemprop="name">Home</span></a></li>';

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
			if ( ul_is_location_page() ) {
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
}
