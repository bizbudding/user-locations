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
        add_filter( 'user_contactmethods', array( $this, 'add_user_contact_methods' ), 30, 1 );
		// Load location field select choices
		$this->load_fields();
		// Load values of fields
		$this->load_values();
		// Save values of fields
		$this->save_values();
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

	public function load_fields() {
		// Location/Business type
		add_filter( 'acf/load_field/name=location_type', array( $this, 'load_types' ) );
		// Country
		add_filter( 'acf/load_field/name=address_country', array( $this, 'load_countries' ) );
		// Hours fields
		$fields = $this->get_opening_hours_fields();
		foreach ( $fields as $field ) {
			add_filter( 'acf/load_field/name=' . $field, array( $this, 'load_hours' ) );
		}
	}

	public function load_types( $field ) {
		$field['choices'] = array();
		return $this->get_choices( $field, $this->get_location_types_array() );
	}

	public function load_countries( $field ) {
		$field['choices'] = array();
		return $this->get_choices( $field, $this->get_countries_array() );
	}

	public function load_hours( $field ) {
		$field['choices'] = array();
		return $this->get_choices( $field, $this->get_opening_hours_array() );
	}

	/**
	 * Helper method to get the formatted choices to load an ACF select field
	 *
	 * @param  array  $field   $field array from acf/load_field
	 * @param  array  $choices The unformatted choices array
	 *
	 * @return array
	 */
	public function get_choices( $field, $choices ) {
		foreach ( $choices as $key => $value ) {
			$field['choices'][ $key ] = $value;
		}
		return $field;
	}

	public function load_values() {
		// Get all fields
		$fields = $this->get_all_fields();
		foreach ( $fields as $field ) {
		    add_filter( 'acf/load_value/name=' . $field, array( $this, 'load_value' ), 10, 3 );
		}
	}

	public function load_value( $value, $post_id, $field ) {
		return $this->get_field( get_current_user_id(), $field['name'] );
	}

	public function save_values() {
		// Get all fields
		$fields = $this->get_all_fields_grouped();
		foreach ( $fields['data'] as $field ) {
		    add_filter( 'acf/update_value/name=' . $field, array( $this, 'save_data_value' ), 10, 3 );
		}
		foreach ( $fields['tax'] as $field ) {
		    add_filter( 'acf/update_value/name=' . $field, array( $this, 'save_tax_value' ), 10, 3 );
		}
		foreach ( $fields['meta'] as $field ) {
		    add_filter( 'acf/update_value/name=' . $field, array( $this, 'save_meta_value' ), 10, 3 );
		}
	}

	public function save_data_value( $value, $post_id, $field ) {
		// Sanitize value
		$this->sanitize_field($value);
		// Get the user ID
		$user_id = User_Locations()->get_admin_location_id();
		// Set the user data
		$user_data = array(
			'ID' 	       => $user_id,
			$field['name'] => $value,
		);
		// Special exception for nickname to also save as display_name
		if ( $field['name'] == 'nickname' ) {
			$user_data = array(
				'ID' 	       => $user_id,
				'nickname'     => $value,
				'display_name' => $value,
			);
		}
		wp_update_user( $user_data );
		// Save empty data since the form doesn't save data where we need it to on its own
		return '';
	}

	public function save_tax_value( $value, $post_id, $field ) {
		// Sanitize value
		$this->sanitize_field($value);
		// Get the user ID
		$user_id = User_Locations()->get_admin_location_id();
		wp_set_object_terms( $user_id, $value, $field['name'], false );
		// Save empty data since the form doesn't save data where we need it to on its own
		return '';
	}

	public function save_meta_value( $value, $post_id, $field ) {
		// Sanitize value
		$this->sanitize_field($value);
		// Get the user ID
		$user_id = User_Locations()->get_admin_location_id();
		update_user_meta( $user_id, $field['name'], $value );
		// Save empty data since the form doesn't save data where we need it to on its own
		return '';
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
			$post_id = null;
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
		$user   = get_userdata( $user_id );
		$fields = $this->get_all_fields_grouped();
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
			'data' => $this->get_user_data_fields(),
			'tax'  => $this->get_user_taxonomy_fields(),
			'meta' => $this->get_user_meta_fields(),
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
	 * Get user meta fields
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_user_meta_fields() {
		$fields = array(
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
			'location',
			'facebook',
			'twitter',
			'googleplus',
			'youtube',
			'linkedin',
			'instagram',
		);
		$hours = $this->get_opening_hours_fields();
		return array_merge($fields, $hours);
	}

	/**
	 * Get user data fields
	 * Some of these are actually meta, but work with wp_update_user
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_opening_hours_fields() {
		return array(
			'opening_hours_multiple',
			'opening_hours_monday_from',
			'opening_hours_monday_to',
			'opening_hours_monday_from_2',
			'opening_hours_monday_to_2',
			'opening_hours_tuesday_from',
			'opening_hours_tuesday_to',
			'opening_hours_tuesday_from_2',
			'opening_hours_tuesday_to_2',
			'opening_hours_wednesday_from',
			'opening_hours_wednesday_to',
			'opening_hours_wednesday_from_2',
			'opening_hours_wednesday_to_2',
			'opening_hours_thursday_from',
			'opening_hours_thursday_to',
			'opening_hours_thursday_from_2',
			'opening_hours_thursday_to_2',
			'opening_hours_friday_from',
			'opening_hours_friday_to',
			'opening_hours_friday_from_2',
			'opening_hours_friday_to_2',
			'opening_hours_saturday_from',
			'opening_hours_saturday_to',
			'opening_hours_saturday_from_2',
			'opening_hours_saturday_to_2',
			'opening_hours_sunday_from',
			'opening_hours_sunday_to',
			'opening_hours_sunday_from_2',
			'opening_hours_sunday_to_2',
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
	 * Return the location type name based on schema representation
	 *
	 * @since  1.0.0
	 *
	 * @param  string $time Time code. Ex: '08:00'
	 *
	 * @return string Readable time
	 */
	public function get_location_type( $type = '' ) {
		return $this->get_value_from_key( $type, $this->get_location_types_array() );
	}


	/**
	 * Return the formatted time based on time
	 *
	 * @since  1.0.0
	 *
	 * @param  string $time Time code. Ex: '08:00'
	 *
	 * @return string Readable time
	 */
	public function get_hour( $time = '' ) {
		return $this->get_value_from_key( $time, $this->get_opening_hours_array() );
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
		return $this->get_value_from_key( $country_code, $this->get_countries_array() );
	}

	public function get_value_from_key( $key = '', $names ) {
		if ( $key == '' || ! array_key_exists( $key, $names ) ) {
			return false;
		}
		return $names[$key];
	}

	public function get_location_types_array() {
		return array(
			'Organization'                => 'Organization',
			'Corporation'                 => 'Corporation',
			'GovernmentOrganization'      => 'Government Organization',
			'NGO'                         => 'NGO',
			'EducationalOrganization'     => 'Educational Organization',
			'CollegeOrUniversity'         => '&mdash; College or University',
			'ElementarySchool'            => '&mdash; Elementary School',
			'HighSchool'                  => '&mdash; High School',
			'MiddleSchool'                => '&mdash; Middle School',
			'Preschool'                   => '&mdash; Preschool',
			'School'                      => '&mdash; School',
			'PerformingGroup'             => 'Performing Group',
			'DanceGroup'                  => '&mdash; Dance Group',
			'MusicGroup'                  => '&mdash; Music Group',
			'TheaterGroup'                => '&mdash; Theater Group',
			'SportsTeam'                  => 'Sports Team',
			'LocalBusiness'               => 'Local Business',
			'AnimalShelter'               => 'Animal Shelter',
			'AutomotiveBusiness'          => 'Automotive Business',
			'AutoBodyShop'                => '&mdash; Auto Body Shop',
			'AutoDealer'                  => '&mdash; Auto Dealer',
			'AutoPartsStore'              => '&mdash; Auto Parts Store',
			'AutoRental'                  => '&mdash; Auto Rental',
			'AutoRepair'                  => '&mdash; Auto Repair',
			'AutoWash'                    => '&mdash; Auto Wash',
			'GasStation'                  => '&mdash; Gas Station',
			'MotorcycleDealer'            => '&mdash; Motorcycle Dealer',
			'MotorcycleRepair'            => '&mdash; Motorcycle Repair',
			'ChildCare'                   => 'Child Care',
			'DryCleaningOrLaundry'        => 'Dry Cleaning or Laundry',
			'EmergencyService'            => 'Emergency Service',
			'FireStation'                 => '&mdash; Fire Station',
			'Hospital'                    => '&mdash; Hospital',
			'PoliceStation'               => '&mdash; Police Station',
			'EmploymentAgency'            => 'Employment Agency',
			'EntertainmentBusiness'       => 'Entertainment Business',
			'AdultEntertainment'          => '&mdash; Adult Entertainment',
			'AmusementPark'               => '&mdash; Amusement Park',
			'ArtGallery'                  => '&mdash; Art Gallery',
			'Casino'                      => '&mdash; Casino',
			'ComedyClub'                  => '&mdash; Comedy Club',
			'MovieTheater'                => '&mdash; Movie Theater',
			'NightClub'                   => '&mdash; Night Club',
			'FinancialService'            => 'Financial Service',
			'AccountingService'           => '&mdash; Accounting Service',
			'AutomatedTeller'             => '&mdash; Automated Teller',
			'BankOrCreditUnion'           => '&mdash; Bank or Credit Union',
			'InsuranceAgency'             => '&mdash; Insurance Agency',
			'FoodEstablishment'           => 'Food Establishment',
			'Bakery'                      => '&mdash; Bakery',
			'BarOrPub'                    => '&mdash; Bar or Pub',
			'Brewery'                     => '&mdash; Brewery',
			'CafeOrCoffeeShop'            => '&mdash; Cafe or Coffee Shop',
			'FastFoodRestaurant'          => '&mdash; Fast Food Restaurant',
			'IceCreamShop'                => '&mdash; Ice Cream Shop',
			'Restaurant'                  => '&mdash; Restaurant',
			'Winery'                      => '&mdash; Winery',
			'GovernmentOffice'            => 'Government Office',
			'PostOffice'                  => '&mdash; Post Office',
			'HealthAndBeautyBusiness'     => 'Health And Beauty Business',
			'BeautySalon'                 => '&mdash; Beauty Salon',
			'DaySpa'                      => '&mdash; Day Spa',
			'HairSalon'                   => '&mdash; Hair Salon',
			'HealthClub'                  => '&mdash; Health Club',
			'NailSalon'                   => '&mdash; Nail Salon',
			'TattooParlor'                => '&mdash; Tattoo Parlor',
			'HomeAndConstructionBusiness' => 'Home And Construction Business',
			'Electrician'                 => '&mdash; Electrician',
			'GeneralContractor'           => '&mdash; General Contractor',
			'HVACBusiness'                => '&mdash; HVAC Business',
			'HousePainter'                => '&mdash; House Painter',
			'Locksmith'                   => '&mdash; Locksmith',
			'MovingCompany'               => '&mdash; Moving Company',
			'Plumber'                     => '&mdash; Plumber',
			'RoofingContractor'           => '&mdash; Roofing Contractor',
			'InternetCafe'                => 'Internet Cafe',
			'Library'                     => ' Library',
			'LodgingBusiness'             => 'Lodging Business',
			'BedAndBreakfast'             => '&mdash; Bed And Breakfast',
			'Hostel'                      => '&mdash; Hostel',
			'Hotel'                       => '&mdash; Hotel',
			'Motel'                       => '&mdash; Motel',
			'MedicalOrganization'         => 'Medical Organization',
			'Dentist'                     => '&mdash; Dentist',
			'DiagnosticLab'               => '&mdash; Diagnostic Lab',
			'Hospital'                    => '&mdash; Hospital',
			'MedicalClinic'               => '&mdash; Medical Clinic',
			'Optician'                    => '&mdash; Optician',
			'Pharmacy'                    => '&mdash; Pharmacy',
			'Physician'                   => '&mdash; Physician',
			'VeterinaryCare'              => '&mdash; Veterinary Care',
			'ProfessionalService'         => 'Professional Service',
			'AccountingService'           => '&mdash; Accounting Service',
			'Attorney'                    => '&mdash; Attorney',
			'Dentist'                     => '&mdash; Dentist',
			'Electrician'                 => '&mdash; Electrician',
			'GeneralContractor'           => '&mdash; General Contractor',
			'HousePainter'                => '&mdash; House Painter',
			'Locksmith'                   => '&mdash; Locksmith',
			'Notary'                      => '&mdash; Notary',
			'Plumber'                     => '&mdash; Plumber',
			'RoofingContractor'           => '&mdash; Roofing Contractor',
			'RadioStation'                => 'Radio Station',
			'RealEstateAgent'             => 'Real Estate Agent',
			'RecyclingCenter'             => 'Recycling Center',
			'SelfStorage'                 => 'Self Storage',
			'ShoppingCenter'              => 'Shopping Center',
			'SportsActivityLocation'      => 'Sports Activity Location',
			'BowlingAlley'                => '&mdash; Bowling Alley',
			'ExerciseGym'                 => '&mdash; Exercise Gym',
			'GolfCourse'                  => '&mdash; Golf Course',
			'HealthClub'                  => '&mdash; Health Club',
			'PublicSwimmingPool'          => '&mdash; Public Swimming Pool',
			'SkiResort'                   => '&mdash; Ski Resort',
			'SportsClub'                  => '&mdash; Sports Club',
			'StadiumOrArena'              => '&mdash; Stadium or Arena',
			'TennisComplex'               => '&mdash; Tennis Complex',
			'Store'                       => ' Store',
			'AutoPartsStore'              => '&mdash; Auto Parts Store',
			'BikeStore'                   => '&mdash; Bike Store',
			'BookStore'                   => '&mdash; Book Store',
			'ClothingStore'               => '&mdash; Clothing Store',
			'ComputerStore'               => '&mdash; Computer Store',
			'ConvenienceStore'            => '&mdash; Convenience Store',
			'DepartmentStore'             => '&mdash; Department Store',
			'ElectronicsStore'            => '&mdash; Electronics Store',
			'Florist'                     => '&mdash; Florist',
			'FurnitureStore'              => '&mdash; Furniture Store',
			'GardenStore'                 => '&mdash; Garden Store',
			'GroceryStore'                => '&mdash; Grocery Store',
			'HardwareStore'               => '&mdash; Hardware Store',
			'HobbyShop'                   => '&mdash; Hobby Shop',
			'HomeGoodsStore'              => '&mdash; HomeGoods Store',
			'JewelryStore'                => '&mdash; Jewelry Store',
			'LiquorStore'                 => '&mdash; Liquor Store',
			'MensClothingStore'           => '&mdash; Mens Clothing Store',
			'MobilePhoneStore'            => '&mdash; Mobile Phone Store',
			'MovieRentalStore'            => '&mdash; Movie Rental Store',
			'MusicStore'                  => '&mdash; Music Store',
			'OfficeEquipmentStore'        => '&mdash; Office Equipment Store',
			'OutletStore'                 => '&mdash; Outlet Store',
			'PawnShop'                    => '&mdash; Pawn Shop',
			'PetStore'                    => '&mdash; Pet Store',
			'ShoeStore'                   => '&mdash; Shoe Store',
			'SportingGoodsStore'          => '&mdash; Sporting Goods Store',
			'TireShop'                    => '&mdash; Tire Shop',
			'ToyStore'                    => '&mdash; Toy Store',
			'WholesaleStore'              => '&mdash; Wholesale Store',
			'TelevisionStation'           => 'Television Station',
			'TouristInformationCenter'    => 'Tourist Information Center',
			'TravelAgency'                => 'Travel Agency',
			'Airport'                     => 'Airport',
			'Aquarium'                    => 'Aquarium',
			'Beach'                       => 'Beach',
			'BusStation'                  => 'BusStation',
			'BusStop'                     => 'BusStop',
			'Campground'                  => 'Campground',
			'Cemetery'                    => 'Cemetery',
			'Crematorium'                 => 'Crematorium',
			'EventVenue'                  => 'Event Venue',
			'FireStation'                 => 'Fire Station',
			'GovernmentBuilding'          => 'Government Building',
			'CityHall'                    => '&mdash; City Hall',
			'Courthouse'                  => '&mdash; Courthouse',
			'DefenceEstablishment'        => '&mdash; Defence Establishment',
			'Embassy'                     => '&mdash; Embassy',
			'LegislativeBuilding'         => '&mdash; Legislative Building',
			'Hospital'                    => 'Hospital',
			'MovieTheater'                => 'Movie Theater',
			'Museum'                      => 'Museum',
			'MusicVenue'                  => 'Music Venue',
			'Park'                        => 'Park',
			'ParkingFacility'             => 'Parking Facility',
			'PerformingArtsTheater'       => 'Performing Arts Theater',
			'PlaceOfWorship'              => 'Place Of Worship',
			'BuddhistTemple'              => '&mdash; Buddhist Temple',
			'CatholicChurch'              => '&mdash; Catholic Church',
			'Church'                      => '&mdash; Church',
			'HinduTemple'                 => '&mdash; Hindu Temple',
			'Mosque'                      => '&mdash; Mosque',
			'Synagogue'                   => '&mdash; Synagogue',
			'Playground'                  => 'Playground',
			'PoliceStation'               => 'PoliceStation',
			'RVPark'                      => 'RVPark',
			'StadiumOrArena'              => 'StadiumOrArena',
			'SubwayStation'               => 'SubwayStation',
			'TaxiStand'                   => 'TaxiStand',
			'TrainStation'                => 'TrainStation',
			'Zoo'                         => 'Zoo',
			'Residence'                   => 'Residence',
			'ApartmentComplex'            => '&mdash; Apartment Complex',
			'GatedResidenceCommunity'     => '&mdash; Gated Residence Community',
			'SingleFamilyResidence'       => '&mdash; Single Family Residence',
		);
	}

	public function get_opening_hours_array() {
		return array(
			'closed'=> 'Closed',
			'00:00' => '12:00 AM',
			'00:15' => '12:15 AM',
			'00:30' => '12:30 AM',
			'00:45' => '12:45 AM',
			'01:00' => '1:00 AM',
			'01:15' => '1:15 AM',
			'01:30' => '1:30 AM',
			'01:45' => '1:45 AM',
			'02:00' => '2:00 AM',
			'02:15' => '2:15 AM',
			'02:30' => '2:30 AM',
			'02:45' => '2:45 AM',
			'03:00' => '3:00 AM',
			'03:15' => '3:15 AM',
			'03:30' => '3:30 AM',
			'03:45' => '3:45 AM',
			'04:00' => '4:00 AM',
			'04:15' => '4:15 AM',
			'04:30' => '4:30 AM',
			'04:45' => '4:45 AM',
			'05:00' => '5:00 AM',
			'05:15' => '5:15 AM',
			'05:30' => '5:30 AM',
			'05:45' => '5:45 AM',
			'06:00' => '6:00 AM',
			'06:15' => '6:15 AM',
			'06:30' => '6:30 AM',
			'06:45' => '6:45 AM',
			'07:00' => '7:00 AM',
			'07:15' => '7:15 AM',
			'07:30' => '7:30 AM',
			'07:45' => '7:45 AM',
			'08:00' => '8:00 AM',
			'08:15' => '8:15 AM',
			'08:30' => '8:30 AM',
			'08:45' => '8:45 AM',
			'09:00' => '9:00 AM',
			'09:15' => '9:15 AM',
			'09:30' => '9:30 AM',
			'09:45' => '9:45 AM',
			'10:00' => '10:00 AM',
			'10:15' => '10:15 AM',
			'10:30' => '10:30 AM',
			'10:45' => '10:45 AM',
			'11:00' => '11:00 AM',
			'11:15' => '11:15 AM',
			'11:30' => '11:30 AM',
			'11:45' => '11:45 AM',
			'12:00' => '12:00 PM',
			'12:15' => '12:15 PM',
			'12:30' => '12:30 PM',
			'12:45' => '12:45 PM',
			'13:00' => '1:00 PM',
			'13:15' => '1:15 PM',
			'13:30' => '1:30 PM',
			'13:45' => '1:45 PM',
			'14:00' => '2:00 PM',
			'14:15' => '2:15 PM',
			'14:30' => '2:30 PM',
			'14:45' => '2:45 PM',
			'15:00' => '3:00 PM',
			'15:15' => '3:15 PM',
			'15:30' => '3:30 PM',
			'15:45' => '3:45 PM',
			'16:00' => '4:00 PM',
			'16:15' => '4:15 PM',
			'16:30' => '4:30 PM',
			'16:45' => '4:45 PM',
			'17:00' => '5:00 PM',
			'17:15' => '5:15 PM',
			'17:30' => '5:30 PM',
			'17:45' => '5:45 PM',
			'18:00' => '6:00 PM',
			'18:15' => '6:15 PM',
			'18:30' => '6:30 PM',
			'18:45' => '6:45 PM',
			'19:00' => '7:00 PM',
			'19:15' => '7:15 PM',
			'19:30' => '7:30 PM',
			'19:45' => '7:45 PM',
			'20:00' => '8:00 PM',
			'20:15' => '8:15 PM',
			'20:30' => '8:30 PM',
			'20:45' => '8:45 PM',
			'21:00' => '9:00 PM',
			'21:15' => '9:15 PM',
			'21:30' => '9:30 PM',
			'21:45' => '9:45 PM',
			'22:00' => '10:00 PM',
			'22:15' => '10:15 PM',
			'22:30' => '10:30 PM',
			'22:45' => '10:45 PM',
			'23:00' => '11:00 PM',
			'23:15' => '11:15 PM',
			'23:30' => '11:30 PM',
			'23:45' => '11:45 PM',
		);
	}

	/**
	 * Retrieves array of all countries and their ISO country code.
	 * This needs to stay in sync with the ACF address_country field, if one changes, change the other
	 *
	 * @return array Array of countries.
	 */
	public function get_countries_array() {
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
