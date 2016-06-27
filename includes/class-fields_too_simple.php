<?php
/**
 * User_Locations
 *
 * @package   User_Locations
 * @author    Mike Hemberger <mike@bizbudding.com.com>
 * @link      https://github.com/JiveDig/user-locations/
 * @copyright 2016 Mike Hemberger
 * @license   GPL-2.0+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main plugin class.
 *
 * @package User_Locations
 */
final class User_Locations_Fields {

	/**
	 * @var User_Locations_Fields The one true User_Locations_Fields
	 * @since 1.0.0
	 */
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new User_Locations_Fields;
			// Methods
			self::$instance->init();
		}
		return self::$instance;
	}

	public function init() {
    	// Add new user profile contact fields
        // add_filter( 'user_contactmethods', array( $this, 'add_user_contact_methods' ), 30, 1 );

		$this->load_fields();
		$this->save_fields();
	    // Load user field values
        // add_filter( 'acf/load_field', array( $this, 'load_user_fields_fields' ) );
        // add_filter( 'acf/load_value', array( $this, 'load_user_fields' ), 10, 3 );
        // Save user field values
        // add_filter( 'acf/update_value', array( $this, 'save_user_fields' ), 10, 3 );
		// Disable default saving
		// add_action( 'acf/save_post', array( $this, 'disable_default_save' ), 1 );

		// add_filter( 'acf/load_value/name=opening_hours', array( $this, 'load_opening_hours' ), 10, 3 );
	}

	public function load_fields() {
		$fields = $this->get_all_fields();
		foreach ( $fields as $field ) {
			add_filter( 'acf/load_field/name=' . $field, array( $this, 'load_field' ) );
		}
	}

	public function load_field( $field ) {
		$field['value'] = get_field( $field['name'], 'user_' . get_current_user_id() );
		return $field;
	}

	public function save_fields() {
		$fields = $this->get_all_fields();
		foreach ( $fields as $field ) {
			add_filter( 'acf/update_value/name=' . $field, array( $this, 'save_field' ), 10, 3 );
		}
	}

	public function save_field( $value, $post_id, $field ) {
		$post_id = get_current_user_id();
	}

	public function add_user_contact_methods( $user_contact ) {
		$user_contact['twitter']          = __( 'Twitter Username (without @)', 'user-locations' );
		$user_contact['instagram']        = __( 'Instagram Username', 'user-locations' );
		$user_contact['youtube']          = __( 'YouTube URL', 'user-locations' );
		$user_contact['linkedin']         = __( 'LinkedIn URL', 'user-locations' );
		$user_contact['phone']            = __( 'Phone Number', 'user-locations' );
		$user_contact['address_street']   = __( 'Street', 'user-locations' );
		$user_contact['address_street_2'] = __( 'Street (2nd line)', 'user-locations' );
		$user_contact['address_city']     = __( 'City', 'user-locations' );
		$user_contact['address_state']    = __( 'State', 'user-locations' );
		$user_contact['address_postcode'] = __( 'Zip Code', 'user-locations' );
		return $user_contact;
	}

	public function sanitize_field( $value ) {
		if ( is_array($value) ) {
			return array_map('sanitize_fields', $value);
		}
		return wp_kses_post( $value );
	}

	public function disable_default_save( $post_id ) {
		// bail early if no ACF data
		if ( empty($_POST['acf']) ) {
			return;
		}
		$field_group_key = isset($_POST['acf']['field_576c3d1b190dc']) ? $_POST['acf']['field_576c3d1b190dc'] : '';
		// If hidden field value is the group field ID, set the $post_id to null so nothing gets saved
		if ( $field_group_key === 'group_57699cab27e89' ) {
			$post_id = get_current_user_id();
		}
	}

	/**
	 * Get field value regardless of where the data is save_user_fields
	 *
	 * @since  1.0.0
	 *
	 * @param  int     $user_id
	 * @param  string  $name     The field name/key
	 *
	 * @return mixed
	 */
	public function get_field( $user_id, $name ) {
		$user    = wp_get_current_user();
		$fields  = $this->get_all_fields_grouped();
		if ( in_array($name, $fields['data']) ) {
			return $user->$name;
		}
		if ( in_array($name, $fields['tax']) ) {
			$terms = wp_get_object_terms( $user->ID, $name, array( 'fields' => 'names' ) );
			if ( $terms ) {
				return $terms[0];
			}
		}
		if ( in_array($name, $fields['meta']) ) {
			// $single = true;
			// if ( in_array( $name, $this->get_complex_fields() ) ) {
			// 	$single = false;
			// }
			return get_user_meta( $user->ID, $name, true );
		}
		return false;
	}

	/**
	 * Get an  array of all field names
	 *
	 * @since  1.0.0.
	 *
	 * @return array
	 */
	public function get_all_fields() {
		$fields = $this->get_all_fields_grouped();
		$arrays = array(
			$fields['acf'],
			$fields['data'],
			$fields['tax'],
			$fields['meta'],
		);
		return call_user_func_array( 'array_merge', $arrays );
	}

	/**
	 * Get an associative array of all field names group by data type
	 *
	 * @since  1.0.0.
	 *
	 * @return array
	 */
	public function get_all_fields_grouped() {
		return array(
			'acf'  => $this->get_acf_fields(),
			'data' => $this->get_user_data_fields(),
			'tax'  => $this->get_user_taxonomy_fields(),
			'meta' => $this->get_user_meta_fields(),
		);
	}

	public function get_acf_fields() {
		return array(
			'location', 	 // ACF Google Map field
			'opening_hours', // ACF Repeater
		);
	}

	/**
	* Get user data fields
	* Some of these are actually meta, but work with wp_update_user
	*
	* @since  1.0.0
	*
	* @return array
	*/
	public function get_user_data_fields() {
		return array(
			'display_name',
			'user_email',
			'first_name',
			'last_name',
			'nickname',
			'description',
			'user_url',
		);
	}

	/**
	 * Get user data fields
	 * Some of these are actually meta, but work with wp_update_user
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_user_taxonomy_fields() {
		return array(
			'location_type',
			'user_type',
		);
	}

	/**
	 * Get user meta fields
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_user_meta_fields() {
		return array(
			'phone',
			'phone_2',
			'fax',
			'address_is_postal',
			'address_street',
			'address_street_2',
			'address_city',
			'address_state',
			'address_postcode',
			'address_country',
			'facebook',
			'twitter',
			'googleplus',
			'youtube',
			'linkedin',
			'instagram',
			'opening_hours',
		);
	}

	/**
	 * Get fields to be hidden
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_hidden_field_names() {
		return array(
			'_field_group_id',
		);
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
	public function get_country( $country_code = '' ) {
		$countries = $this->get_country_array();
		if ( $country_code == '' || ! array_key_exists( $country_code, $countries ) ) {
			return false;
		}
		return $countries[ $country_code ];
	}

	/**
	 * Retrieves array of all countries and their ISO country code.
	 * This needs to stay in sync with the ACF address_country field, if one changes, change the other
	 *
	 * @return array Array of countries.
	 */
	public function get_country_array() {
		return array(
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
	}

}
