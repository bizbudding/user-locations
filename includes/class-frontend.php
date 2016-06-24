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
		var $options = array();

		/**
		 * @var boolean $options Whether to load external stylesheet or not.
		 */
		var $load_styles = false;

		/**
		 * Constructor.
		 */
		function init() {
			$this->options = get_option( 'user_locations' );

			// Create shortcode functionality. Functions are defined in includes/wpseo-local-functions.php because they're also used by some widgets.
			add_shortcode( 'user_locations_address',            'user_locations_show_address' );
			add_shortcode( 'user_locations_all_locations',      'user_locations_show_all_locations' );
			add_shortcode( 'user_locations_map',                'user_locations_show_map' );
			add_shortcode( 'user_locations_opening_hours',      'user_locations_show_openinghours_shortcode_cb' );
			add_shortcode( 'user_locations_show_logo',          'user_locations_show_logo' );

			add_action( 'user_locations_opengraph',       array( $this, 'opengraph_location' ) );
			add_filter( 'user_locations_opengraph_type',  array( $this, 'opengraph_type' ) );
			add_filter( 'user_locations_opengraph_title', array( $this, 'opengraph_title_filter' ) );

			// Genesis 2.0 specific, this filters the Schema.org output Genesis 2.0 comes with.
			add_filter( 'genesis_attr_body',  array( $this, 'genesis_contact_page_schema' ), 20, 1 );
			add_filter( 'genesis_attr_entry', array( $this, 'genesis_empty_schema' ), 20, 1 );

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
			if ( userlocations_is_singular_location() ) {
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

		/**
		 * Return the country name based on country code
		 *
		 * @since  1.0.0
		 *
		 * @param  string $country_code Two char country code.
		 *
		 * @return string Country name.
		 */
		public static function get_country( $country_code = '' ) {
			$countries = User_Locations_Frontend::get_country_array();
			if ( $country_code == '' || ! array_key_exists( $country_code, $countries ) ) {
				return false;
			}
			return $countries[ $country_code ];
		}

		/**
		 * Retrieves array of all countries and their ISO country code.
		 *
		 * @return array Array of countries.
		 */
		public static function get_country_array() {
			$countries = array(
				'AX' => __( 'Åland Islands', 'user-locations' ),
				'AF' => __( 'Afghanistan', 'user-locations' ),
				'AL' => __( 'Albania', 'user-locations' ),
				'DZ' => __( 'Algeria', 'user-locations' ),
				'AD' => __( 'Andorra', 'user-locations' ),
				'AO' => __( 'Angola', 'user-locations' ),
				'AI' => __( 'Anguilla', 'user-locations' ),
				'AQ' => __( 'Antarctica', 'user-locations' ),
				'AG' => __( 'Antigua and Barbuda', 'user-locations' ),
				'AR' => __( 'Argentina', 'user-locations' ),
				'AM' => __( 'Armenia', 'user-locations' ),
				'AW' => __( 'Aruba', 'user-locations' ),
				'AU' => __( 'Australia', 'user-locations' ),
				'AT' => __( 'Austria', 'user-locations' ),
				'AZ' => __( 'Azerbaijan', 'user-locations' ),
				'BS' => __( 'Bahamas', 'user-locations' ),
				'BH' => __( 'Bahrain', 'user-locations' ),
				'BD' => __( 'Bangladesh', 'user-locations' ),
				'BB' => __( 'Barbados', 'user-locations' ),
				'BY' => __( 'Belarus', 'user-locations' ),
				'PW' => __( 'Belau', 'user-locations' ),
				'BE' => __( 'Belgium', 'user-locations' ),
				'BZ' => __( 'Belize', 'user-locations' ),
				'BJ' => __( 'Benin', 'user-locations' ),
				'BM' => __( 'Bermuda', 'user-locations' ),
				'BT' => __( 'Bhutan', 'user-locations' ),
				'BO' => __( 'Bolivia', 'user-locations' ),
				'BQ' => __( 'Bonaire, Sint Eustatius and Saba', 'user-locations' ),
				'BA' => __( 'Bosnia and Herzegovina', 'user-locations' ),
				'BW' => __( 'Botswana', 'user-locations' ),
				'BV' => __( 'Bouvet Island', 'user-locations' ),
				'BR' => __( 'Brazil', 'user-locations' ),
				'IO' => __( 'British Indian Ocean Territory', 'user-locations' ),
				'VG' => __( 'British Virgin Islands', 'user-locations' ),
				'BN' => __( 'Brunei', 'user-locations' ),
				'BG' => __( 'Bulgaria', 'user-locations' ),
				'BF' => __( 'Burkina Faso', 'user-locations' ),
				'BI' => __( 'Burundi', 'user-locations' ),
				'KH' => __( 'Cambodia', 'user-locations' ),
				'CM' => __( 'Cameroon', 'user-locations' ),
				'CA' => __( 'Canada', 'user-locations' ),
				'CV' => __( 'Cape Verde', 'user-locations' ),
				'KY' => __( 'Cayman Islands', 'user-locations' ),
				'CF' => __( 'Central African Republic', 'user-locations' ),
				'TD' => __( 'Chad', 'user-locations' ),
				'CL' => __( 'Chile', 'user-locations' ),
				'CN' => __( 'China', 'user-locations' ),
				'CX' => __( 'Christmas Island', 'user-locations' ),
				'CC' => __( 'Cocos (Keeling) Islands', 'user-locations' ),
				'CO' => __( 'Colombia', 'user-locations' ),
				'KM' => __( 'Comoros', 'user-locations' ),
				'CG' => __( 'Congo (Brazzaville)', 'user-locations' ),
				'CD' => __( 'Congo (Kinshasa)', 'user-locations' ),
				'CK' => __( 'Cook Islands', 'user-locations' ),
				'CR' => __( 'Costa Rica', 'user-locations' ),
				'HR' => __( 'Croatia', 'user-locations' ),
				'CU' => __( 'Cuba', 'user-locations' ),
				'CW' => __( 'Curaçao', 'user-locations' ),
				'CY' => __( 'Cyprus', 'user-locations' ),
				'CZ' => __( 'Czech Republic', 'user-locations' ),
				'DK' => __( 'Denmark', 'user-locations' ),
				'DJ' => __( 'Djibouti', 'user-locations' ),
				'DM' => __( 'Dominica', 'user-locations' ),
				'DO' => __( 'Dominican Republic', 'user-locations' ),
				'EC' => __( 'Ecuador', 'user-locations' ),
				'EG' => __( 'Egypt', 'user-locations' ),
				'SV' => __( 'El Salvador', 'user-locations' ),
				'GQ' => __( 'Equatorial Guinea', 'user-locations' ),
				'ER' => __( 'Eritrea', 'user-locations' ),
				'EE' => __( 'Estonia', 'user-locations' ),
				'ET' => __( 'Ethiopia', 'user-locations' ),
				'FK' => __( 'Falkland Islands', 'user-locations' ),
				'FO' => __( 'Faroe Islands', 'user-locations' ),
				'FJ' => __( 'Fiji', 'user-locations' ),
				'FI' => __( 'Finland', 'user-locations' ),
				'FR' => __( 'France', 'user-locations' ),
				'GF' => __( 'French Guiana', 'user-locations' ),
				'PF' => __( 'French Polynesia', 'user-locations' ),
				'TF' => __( 'French Southern Territories', 'user-locations' ),
				'GA' => __( 'Gabon', 'user-locations' ),
				'GM' => __( 'Gambia', 'user-locations' ),
				'GE' => __( 'Georgia', 'user-locations' ),
				'DE' => __( 'Germany', 'user-locations' ),
				'GH' => __( 'Ghana', 'user-locations' ),
				'GI' => __( 'Gibraltar', 'user-locations' ),
				'GR' => __( 'Greece', 'user-locations' ),
				'GL' => __( 'Greenland', 'user-locations' ),
				'GD' => __( 'Grenada', 'user-locations' ),
				'GP' => __( 'Guadeloupe', 'user-locations' ),
				'GT' => __( 'Guatemala', 'user-locations' ),
				'GG' => __( 'Guernsey', 'user-locations' ),
				'GN' => __( 'Guinea', 'user-locations' ),
				'GW' => __( 'Guinea-Bissau', 'user-locations' ),
				'GY' => __( 'Guyana', 'user-locations' ),
				'HT' => __( 'Haiti', 'user-locations' ),
				'HM' => __( 'Heard Island and McDonald Islands', 'user-locations' ),
				'HN' => __( 'Honduras', 'user-locations' ),
				'HK' => __( 'Hong Kong', 'user-locations' ),
				'HU' => __( 'Hungary', 'user-locations' ),
				'IS' => __( 'Iceland', 'user-locations' ),
				'IN' => __( 'India', 'user-locations' ),
				'ID' => __( 'Indonesia', 'user-locations' ),
				'IR' => __( 'Iran', 'user-locations' ),
				'IQ' => __( 'Iraq', 'user-locations' ),
				'IM' => __( 'Isle of Man', 'user-locations' ),
				'IL' => __( 'Israel', 'user-locations' ),
				'IT' => __( 'Italy', 'user-locations' ),
				'CI' => __( 'Ivory Coast', 'user-locations' ),
				'JM' => __( 'Jamaica', 'user-locations' ),
				'JP' => __( 'Japan', 'user-locations' ),
				'JE' => __( 'Jersey', 'user-locations' ),
				'JO' => __( 'Jordan', 'user-locations' ),
				'KZ' => __( 'Kazakhstan', 'user-locations' ),
				'KE' => __( 'Kenya', 'user-locations' ),
				'KI' => __( 'Kiribati', 'user-locations' ),
				'KW' => __( 'Kuwait', 'user-locations' ),
				'KG' => __( 'Kyrgyzstan', 'user-locations' ),
				'LA' => __( 'Laos', 'user-locations' ),
				'LV' => __( 'Latvia', 'user-locations' ),
				'LB' => __( 'Lebanon', 'user-locations' ),
				'LS' => __( 'Lesotho', 'user-locations' ),
				'LR' => __( 'Liberia', 'user-locations' ),
				'LY' => __( 'Libya', 'user-locations' ),
				'LI' => __( 'Liechtenstein', 'user-locations' ),
				'LT' => __( 'Lithuania', 'user-locations' ),
				'LU' => __( 'Luxembourg', 'user-locations' ),
				'MO' => __( 'Macao S.A.R., China', 'user-locations' ),
				'MK' => __( 'Macedonia', 'user-locations' ),
				'MG' => __( 'Madagascar', 'user-locations' ),
				'MW' => __( 'Malawi', 'user-locations' ),
				'MY' => __( 'Malaysia', 'user-locations' ),
				'MV' => __( 'Maldives', 'user-locations' ),
				'ML' => __( 'Mali', 'user-locations' ),
				'MT' => __( 'Malta', 'user-locations' ),
				'MH' => __( 'Marshall Islands', 'user-locations' ),
				'MQ' => __( 'Martinique', 'user-locations' ),
				'MR' => __( 'Mauritania', 'user-locations' ),
				'MU' => __( 'Mauritius', 'user-locations' ),
				'YT' => __( 'Mayotte', 'user-locations' ),
				'MX' => __( 'Mexico', 'user-locations' ),
				'FM' => __( 'Micronesia', 'user-locations' ),
				'MD' => __( 'Moldova', 'user-locations' ),
				'MC' => __( 'Monaco', 'user-locations' ),
				'MN' => __( 'Mongolia', 'user-locations' ),
				'ME' => __( 'Montenegro', 'user-locations' ),
				'MS' => __( 'Montserrat', 'user-locations' ),
				'MA' => __( 'Morocco', 'user-locations' ),
				'MZ' => __( 'Mozambique', 'user-locations' ),
				'MM' => __( 'Myanmar', 'user-locations' ),
				'NA' => __( 'Namibia', 'user-locations' ),
				'NR' => __( 'Nauru', 'user-locations' ),
				'NP' => __( 'Nepal', 'user-locations' ),
				'NL' => __( 'Netherlands', 'user-locations' ),
				'AN' => __( 'Netherlands Antilles', 'user-locations' ),
				'NC' => __( 'New Caledonia', 'user-locations' ),
				'NZ' => __( 'New Zealand', 'user-locations' ),
				'NI' => __( 'Nicaragua', 'user-locations' ),
				'NE' => __( 'Niger', 'user-locations' ),
				'NG' => __( 'Nigeria', 'user-locations' ),
				'NU' => __( 'Niue', 'user-locations' ),
				'NF' => __( 'Norfolk Island', 'user-locations' ),
				'KP' => __( 'North Korea', 'user-locations' ),
				'NO' => __( 'Norway', 'user-locations' ),
				'OM' => __( 'Oman', 'user-locations' ),
				'PK' => __( 'Pakistan', 'user-locations' ),
				'PS' => __( 'Palestinian Territory', 'user-locations' ),
				'PA' => __( 'Panama', 'user-locations' ),
				'PG' => __( 'Papua New Guinea', 'user-locations' ),
				'PY' => __( 'Paraguay', 'user-locations' ),
				'PE' => __( 'Peru', 'user-locations' ),
				'PH' => __( 'Philippines', 'user-locations' ),
				'PN' => __( 'Pitcairn', 'user-locations' ),
				'PL' => __( 'Poland', 'user-locations' ),
				'PT' => __( 'Portugal', 'user-locations' ),
				'QA' => __( 'Qatar', 'user-locations' ),
				'IE' => __( 'Republic of Ireland', 'user-locations' ),
				'RE' => __( 'Reunion', 'user-locations' ),
				'RO' => __( 'Romania', 'user-locations' ),
				'RU' => __( 'Russia', 'user-locations' ),
				'RW' => __( 'Rwanda', 'user-locations' ),
				'ST' => __( 'São Tomé and Príncipe', 'user-locations' ),
				'BL' => __( 'Saint Barthélemy', 'user-locations' ),
				'SH' => __( 'Saint Helena', 'user-locations' ),
				'KN' => __( 'Saint Kitts and Nevis', 'user-locations' ),
				'LC' => __( 'Saint Lucia', 'user-locations' ),
				'SX' => __( 'Saint Martin (Dutch part)', 'user-locations' ),
				'MF' => __( 'Saint Martin (French part)', 'user-locations' ),
				'PM' => __( 'Saint Pierre and Miquelon', 'user-locations' ),
				'VC' => __( 'Saint Vincent and the Grenadines', 'user-locations' ),
				'SM' => __( 'San Marino', 'user-locations' ),
				'SA' => __( 'Saudi Arabia', 'user-locations' ),
				'SN' => __( 'Senegal', 'user-locations' ),
				'RS' => __( 'Serbia', 'user-locations' ),
				'SC' => __( 'Seychelles', 'user-locations' ),
				'SL' => __( 'Sierra Leone', 'user-locations' ),
				'SG' => __( 'Singapore', 'user-locations' ),
				'SK' => __( 'Slovakia', 'user-locations' ),
				'SI' => __( 'Slovenia', 'user-locations' ),
				'SB' => __( 'Solomon Islands', 'user-locations' ),
				'SO' => __( 'Somalia', 'user-locations' ),
				'ZA' => __( 'South Africa', 'user-locations' ),
				'GS' => __( 'South Georgia/Sandwich Islands', 'user-locations' ),
				'KR' => __( 'South Korea', 'user-locations' ),
				'SS' => __( 'South Sudan', 'user-locations' ),
				'ES' => __( 'Spain', 'user-locations' ),
				'LK' => __( 'Sri Lanka', 'user-locations' ),
				'SD' => __( 'Sudan', 'user-locations' ),
				'SR' => __( 'Suriname', 'user-locations' ),
				'SJ' => __( 'Svalbard and Jan Mayen', 'user-locations' ),
				'SZ' => __( 'Swaziland', 'user-locations' ),
				'SE' => __( 'Sweden', 'user-locations' ),
				'CH' => __( 'Switzerland', 'user-locations' ),
				'SY' => __( 'Syria', 'user-locations' ),
				'TW' => __( 'Taiwan', 'user-locations' ),
				'TJ' => __( 'Tajikistan', 'user-locations' ),
				'TZ' => __( 'Tanzania', 'user-locations' ),
				'TH' => __( 'Thailand', 'user-locations' ),
				'TL' => __( 'Timor-Leste', 'user-locations' ),
				'TG' => __( 'Togo', 'user-locations' ),
				'TK' => __( 'Tokelau', 'user-locations' ),
				'TO' => __( 'Tonga', 'user-locations' ),
				'TT' => __( 'Trinidad and Tobago', 'user-locations' ),
				'TN' => __( 'Tunisia', 'user-locations' ),
				'TR' => __( 'Turkey', 'user-locations' ),
				'TM' => __( 'Turkmenistan', 'user-locations' ),
				'TC' => __( 'Turks and Caicos Islands', 'user-locations' ),
				'TV' => __( 'Tuvalu', 'user-locations' ),
				'UG' => __( 'Uganda', 'user-locations' ),
				'UA' => __( 'Ukraine', 'user-locations' ),
				'AE' => __( 'United Arab Emirates', 'user-locations' ),
				'GB' => __( 'United Kingdom (UK)', 'user-locations' ),
				'US' => __( 'United States (US)', 'user-locations' ),
				'UY' => __( 'Uruguay', 'user-locations' ),
				'UZ' => __( 'Uzbekistan', 'user-locations' ),
				'VU' => __( 'Vanuatu', 'user-locations' ),
				'VA' => __( 'Vatican', 'user-locations' ),
				'VE' => __( 'Venezuela', 'user-locations' ),
				'VN' => __( 'Vietnam', 'user-locations' ),
				'WF' => __( 'Wallis and Futuna', 'user-locations' ),
				'EH' => __( 'Western Sahara', 'user-locations' ),
				'WS' => __( 'Western Samoa', 'user-locations' ),
				'YE' => __( 'Yemen', 'user-locations' ),
				'ZM' => __( 'Zambia', 'user-locations' ),
				'ZW' => __( 'Zimbabwe', 'user-locations' ),
			);
			return $countries;
		}
	}
}
