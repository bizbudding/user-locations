<?php
/**
 * @package WPSEO_Local\Frontend
 */

if ( ! class_exists( 'User_Locations_Locator' ) ) {

	/**
	 * Class User_Locations_Locator
	 *
	 * Adds all functionality for the store locator
	 */
	class User_Locations_Locator {

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
		function __construct() {
			$this->options = get_option( 'wpseo_local' );

			add_shortcode( 'wpseo_storelocator', array( &$this, 'show_storelocator' ) );

			add_action( 'wp_head', array( &$this, 'load_scripts' ), 99 );
		}

		/**
		 * Outputs HTML for the store locator.
		 *
		 * @param array $atts Array of attributes for the store locator shortcode.
		 *
		 * @return string
		 */
		function show_storelocator( $atts ) {
			global $wpseo_enqueue_geocoder, $wpseo_sl_load_scripts;

			// Don't show any output when you don't have multiple locations enabled.
			if ( false == wpseo_has_multiple_locations() ) {
				return '';
			}

			$wpseo_sl_load_scripts = true;
			$options               = get_option( 'wpseo_local' );

			$atts = wpseo_check_falses( shortcode_atts( array(
				'radius'                  => 10,
				'show_radius'             => false,
				'show_nearest_suggestion' => true,
				'show_map'                => true,
				'show_filter'             => false,
				'map_width'               => '100%',
				'scrollable'              => true,
				'show_country'            => false,
				'show_state'              => false,
				'show_phone'              => false,
				'show_phone_2'            => false,
				'show_fax'                => false,
				'show_email'              => false,
				'show_url'                => false,
				'map_style'               => isset( $options['map_view_style'] ) ? $options['map_view_style'] : 'ROADMAP',
				'show_route_label'        => isset( $options['show_route_label'] ) && ! empty( $options['show_route_label'] ) ? $options['show_route_label'] : __( 'Show route', 'yoast-local-seo' ),
				'oneline'                 => false,
				'show_opening_hours'      => false,
				'hide_closed'             => false,
				'from_widget'             => false,
				'widget_title'            => '',
				'before_title'            => '',
				'after_title'             => '',
				'echo'                    => false,
			), $atts ) );

			if ( $atts['show_map'] ) {
				$wpseo_enqueue_geocoder = true;
			}

			ob_start();
			?>
			<form action="" method="post" id="wpseo-storelocator-form">
				<fieldset>
					<?php
					$search_string    = isset( $_REQUEST['wpseo-sl-search'] ) ? esc_attr( $_REQUEST['wpseo-sl-search'] ) : '';
					$sl_category_term = ! empty( $_REQUEST['wpseo-sl-category'] ) ? $_REQUEST['wpseo-sl-category'] : '';
					?>
					<p>
						<label for="wpseo-sl-search"><?php _e( 'Enter your postal code or city', 'yoast-local-seo' ); ?></label>
						<input type="text" name="wpseo-sl-search" id="wpseo-sl-search" value="<?php echo $search_string; ?>">

						<?php
						if ( $atts['show_radius'] ) {

							_e( 'within', 'yoast-local-seo' );
							?>
							<select name="wpseo-sl-radius" id="wpseo-sl-radius">
								<?php
								$radius_array    = array( 1, 5, 10, 25, 50, 100, 250, 500, 1000 );
								$selected_radius = ! empty( $_REQUEST['wpseo-sl-radius'] ) ? esc_attr( $_REQUEST['wpseo-sl-radius'] ) : $atts['radius'];

								foreach ( $radius_array as $radius ) {
									echo '<option value="' . $radius . '" ' . selected( $selected_radius, $radius, false ) . '>' . $radius . ( ( $this->options['unit_system'] == 'METRIC' ) ? 'km' : 'mi' ) . '</option>';
								}
								?>
							</select>
							<?php
						}
						else {
							?>
							<input type="hidden" name="wpseo-sl-radius" id="wpseo-sl-radius-text" value="<?php echo esc_attr( $atts['radius'] ); ?>">
							<?php
						}
						?>
					</p>

					<?php if ( $atts['show_filter'] ) { ?>
						<?php
						$terms = get_terms( 'wpseo_locations_category' );
						?>
						<?php if ( count( $terms ) > 0 ) { ?>
							<p class="sl-filter">
								<label for="wpseo-sl-category"><?php _e( 'Filter by category', 'yoast-local-seo' ); ?></label>
								<select name="wpseo-sl-category" id="wpseo-sl-category">
									<option value=""></option>
									<?php
									foreach ( $terms as $term ) {
										echo '<option value="' . $term->term_id . '" ' . selected( $sl_category_term, $term->term_id, false ) . '>' . $term->name . '</option>';
									}
									?>
								</select>
							</p>
						<?php } ?>
					<?php } ?>

					<p class="sl-submit">
						<input type="submit" value="<?php _e( 'Search', 'yoast-local-seo' ); ?>">
					</p>

				</fieldset>
			</form>

			<div id="wpseo-storelocator-results">
				<?php
				$results = false;

				if ( false === empty( $_POST ) ) {
					$results = $this->get_results();
				}

				if ( $atts['show_map'] ) {
					$location_ids = array();
					if ( ! empty( $_POST ) && ! is_wp_error( $results ) ) {
						foreach ( $results['locations'] as $location ) {
							$location_ids[] = $location['ID'];
						}
						$ids = implode( ',', $location_ids );
					}
					else {
						$ids = 'all';
					}

					echo wpseo_local_show_map( array(
						'id'         => $ids,
						'width'      => $atts['map_width'],
						'from_sl'    => true,
						'show_route' => true,
						'scrollable' => $atts['scrollable'],
						'map_style'  => $atts['map_style'],
					) );
				}

				if ( false == empty( $_POST ) ) :
					if ( ! is_wp_error( $results ) ) {
						$show_suggestion = $results['in_radius'] <= 0 && true === $atts['show_nearest_suggestion'] && ! empty( $results['locations'] );

						if ( $results['in_radius'] > 0 ) {
							$number = count( $results['locations'] );
							echo '<h2>' . sprintf( _n( '%s result has been found', '%s results have been found', $number, 'yoast-local-seo' ), $number ) . '</h2>';

							foreach ( $results['locations'] as $key => $location ) {
								$this->get_location_details( $location['ID'], $atts );
							}
						}
						else {
							echo '<h2>' . __( 'No results found', 'yoast-local-seo' ) . '</h2>';

							if ( $show_suggestion ) {
								foreach ( $results['locations'] as $distance => $location ) {
									$text_mi = sprintf( __( 'The nearest location is %s miles away', 'yoast-local-seo' ), $distance );
									$text_km = sprintf( __( 'The nearest location is %s kilometers away', 'yoast-local-seo' ), $distance );

									echo '<p class="nearest_location">' . apply_filters( 'wpso_local_no_stores_in_radius', ( ( $this->options['unit_system'] == 'METRIC' ) ? $text_km : $text_mi ) ) . '</p>';

									$this->get_location_details( $location['ID'], $atts );
								}
							}
						}
					}
					else {
						echo '<h2>' . __( 'No results found', 'yoast-local-seo' ) . '</h2>';
					}

				endif;
				?>
			</div>

			<?php
			$output = ob_get_contents();
			ob_end_clean();

			return $output;
		}

		/**
		 * Retrieves the search results based on given search term (zipcode or city).
		 *
		 * @return array
		 */
		public function get_results() {
			global $wpdb;

			if ( empty( $_POST['wpseo-sl-search'] ) ) {
				return new WP_Error( 'wpseo-no-input', __( 'Please enter a zipcode or city', 'yoast-local-seo' ) );
			}

			$nr_results       = ( ! empty( $this->options['sl_num_results'] ) ) ? $this->options['sl_num_results'] : 10;
			$metric           = ( $this->options['unit_system'] == 'METRIC' ) ? 'km' : 'mi';
			$radius           = ( ! empty( $_REQUEST['wpseo-sl-radius'] ) ) ? $_REQUEST['wpseo-sl-radius'] : 99999;
			$sl_category_term = ( ! empty( $_REQUEST['wpseo-sl-category'] ) ) ? $_REQUEST['wpseo-sl-category'] : '';
			$distances        = array(
				'in_radius' => 0,
				'locations' => array(),
			);

			$search_string = isset( $_REQUEST['wpseo-sl-search'] ) ? esc_attr( $_REQUEST['wpseo-sl-search'] ) : '';
			if ( $search_string == '' ) {
				return $distances;
			}

			// Add country name to search to improve search results.
			$default_country = isset( $this->options['default_country'] ) ? $this->options['default_country'] : '';
			if ( $default_country != '' ) {
				$search_string .= ' ' . WPSEO_Local_Frontend::get_country( $default_country );
			}

			$response = wpseo_geocode_address( $search_string );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			// Get lat/long.
			if ( empty( $response->results[0] ) ) {
				return new WP_Error( 'wpseo-get-results-error', __( 'No valid JSON response. We cannot complete the search.', 'yoast-local-seo' ) );
			}

			$result      = $response->results[0];
			$coordinates = ! empty( $result->geometry->location ) ? $result->geometry->location : false;
			if ( ! $coordinates ) {
				return new WP_Error( 'wpseo-get-results-error', __( 'No valid coordinates. We cannot complete the search.', 'yoast-local-seo' ) );
			}

			// Extend SQL with category filter.
			$inner_join = '';
			if ( $sl_category_term != '' ) {
				$inner_join .= "
					INNER JOIN $wpdb->term_relationships AS term_rel ON p.ID = term_rel.object_id
					INNER JOIN $wpdb->term_taxonomy AS taxo ON term_rel.term_taxonomy_id = taxo.term_taxonomy_id
						AND taxo.taxonomy = 'wpseo_locations_category'
						AND taxo.term_id = $sl_category_term
				";
			}

			// Get all coordinates from posts.
			$sql = "SELECT p.ID, m1.meta_value as lat, m2.meta_value as lng
						FROM $wpdb->posts p
						INNER JOIN $wpdb->postmeta m1 ON p.ID = m1.post_id
						INNER JOIN $wpdb->postmeta m2 ON p.ID = m2.post_id
						$inner_join
						WHERE
							p.post_type = 'location_page' AND
						 	p.post_status = 'publish' AND
							m1.meta_key = '_wpseo_coordinates_lat' AND
							m2.meta_key = '_wpseo_coordinates_long'
						GROUP BY p.ID";

			$locations = $wpdb->get_results( $sql );

			// Calculate distance.
			$in_radius     = array();
			$out_of_radius = array();

			if ( 0 == $wpdb->num_rows ) {
				return $distances;
			}

			foreach ( $locations as $location ) {
				// Skip locations with empty lat/long coordinates.
				if ( empty( $location->lat ) || empty( $location->lng ) ) {
					continue;
				}

				$distance     = $this->get_distance( $coordinates->lat, $coordinates->lng, $location->lat, $location->lng );
				$distance_key = ( round( $distance[ $metric ], 4 ) * 10000 );

				// Filter on radius.
				if ( $distance[ $metric ] > $radius ) {
					$out_of_radius[ $distance[ $metric ] ] = array(
						'distance' => $distance_key,
						'ID'       => $location->ID,
					);
				}
				else {
					$in_radius[] = array(
						'distance' => $distance_key,
						'ID'       => $location->ID,
					);
				}
			}

			if ( 0 == count( $in_radius ) ) {
				// No results were found inside the given radius.
				ksort( $out_of_radius, SORT_NUMERIC );

				$distances['locations'] = array_slice( $out_of_radius, 0, 1, true );

				return $distances;
			}

			usort( $in_radius, array( $this, 'sort_distances' ) );
			$in_radius = array_slice( $in_radius, 0, $nr_results, true );

			$distances['in_radius'] = count( $in_radius );
			$distances['locations'] = $in_radius;

			return $distances;
		}

		/**
		 * Sort multidimensional array with distances
		 *
		 * @param float $a Distance A.
		 * @param float $b Distance B.
		 *
		 * @return mixed
		 */
		public function sort_distances( $a, $b ) {
			return ( $a['distance'] - $b['distance'] );
		}

		/**
		 * Calculates distance between two sets of coordinates. Used code from http://www.inkplant.com/code/calculate-the-distance-between-two-points.php
		 *
		 * @param float $latitude1  First latitude.
		 * @param float $longitude1 First longitude.
		 * @param float $latitude2  Second latitude.
		 * @param float $longitude2 Second longitude.
		 *
		 * @return array
		 */
		public function get_distance( $latitude1, $longitude1, $latitude2, $longitude2 ) {
			$theta = ( $longitude1 - $longitude2 );
			$miles = ( ( sin( deg2rad( $latitude1 ) ) * sin( deg2rad( $latitude2 ) ) ) + ( cos( deg2rad( $latitude1 ) ) * cos( deg2rad( $latitude2 ) ) * cos( deg2rad( $theta ) ) ) );
			$miles = acos( $miles );
			$miles = rad2deg( $miles );
			$miles = ( $miles * 60 * 1.1515 );
			$km    = ( $miles * 1.609344 );

			return array(
				'mi' => $miles,
				'km' => $km,
			);
		}

		/**
		 * Load jQuery script (if not already loaded before).
		 */
		public function load_scripts() {
			global $wpseo_sl_load_scripts;

			if ( ! wp_script_is( 'jquery', 'done' ) ) {
				wp_enqueue_script( 'jquery', '//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js' );
			}
		}

		/**
		 * Show all location information.
		 *
		 * @param int   $location_id Post ID of the location.
		 * @param array $atts        Array of attributes, used for displaying the address. These are matching attributes for the wpseo_local_show_address() method.
		 */
		public function get_location_details( $location_id, $atts ) {
			$location = get_field( 'location', $location_id );
			$coords_lat  = $location['lat'];
			$coords_long = $location['lng'];
			?>
			<div class="wpseo-result">
				<?php
				$location = ul_show_address( array(
					'id'                 => $location_id,
					'show_state'         => $atts['show_state'],
					'show_country'       => $atts['show_country'],
					'show_phone'         => $atts['show_phone'],
					'show_phone_2'       => $atts['show_phone_2'],
					'show_fax'           => $atts['show_fax'],
					'show_email'         => $atts['show_email'],
					'show_url'           => $atts['show_url'],
					'show_opening_hours' => $atts['show_opening_hours'],
					'hide_closed'        => $atts['hide_closed'],
					'oneline'            => $atts['oneline'],
					'from_sl'            => true,
					'echo'               => false,
				) );

				echo apply_filters( 'wpseo_local_sl_result', $location, $location_id );
				?>
				<div class="wpseo-sl-route">
					<a href="javascript:;" onclick="wpseo_sl_show_route( this, '<?php echo $coords_lat; ?>', '<?php echo $coords_long; ?>' );"><?php echo $atts['show_route_label']; ?></a>
				</div>
			</div>
			<?php
		}
	}
}

if ( ! function_exists( 'wpseo_local_storelocator' ) ) {
	/**
	 * @param array $atts Array of attributes for displaying the store locator.
	 *
	 * @return string
	 */
	function wpseo_local_storelocator( $atts ) {
		global $wpseo_local_storelocator;

		if ( null == $wpseo_local_storelocator ) {
			$wpseo_local_storelocator = new User_Locations_Locator();
		}

		return $wpseo_local_storelocator->show_storelocator( $atts );
	}
}
$wpseo_sl_load_scripts = false;
?>
