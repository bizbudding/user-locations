<?php
/**
 * @package User_Locations
 */

/**
 * Class User_Locations_Location_Info.
 *
 * Creates widget for showing the address.
 */
class User_Locations_Location_Info extends WP_Widget {

	/**
	 * User_Locations_Location_Info constructor.
	 */
	function __construct() {
		$widget_options = array(
			'classname'   => 'User_Locations_Location_Info',
			'description' => __( 'Shows the location info in Schema.org standards', 'user-locations' ),
		);
		parent::__construct( false, $name = ul_get_singular_name() . ' ' . __( 'Info', 'user-locations' ), $widget_options );
	}

	/** @see WP_Widget::widget
	 * Displays the store locator form.
	 *
	 * @param array $args     Array of options for this widget.
	 * @param array $instance Instance of the widget.
	 *
	 * @return string|void
	 */
	function widget( $args, $instance ) {

		if ( ! ul_is_location_content() ) {
			return '';
		}

		if ( is_singular('location_page') ) {
			$location_id = ul_get_location_parent_page_id();
		}
		elseif ( is_singular('post') ) {
			$location_id = ul_get_location_parent_page_id_from_post_id( get_the_ID() );
		}

		// Bail if no location ID
		if ( ! $location_id ) {
			return '';
		}

		wp_enqueue_style('user-locations');

		$instance['id'] = $location_id;

		if ( isset( $args['before_widget'] ) ) {
			echo $args['before_widget'];
		}

		$title = apply_filters( 'widget_title', $instance['title'] );

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		echo ul_get_info( $instance );

		if ( isset( $args['after_widget'] ) ) {
			echo $args['after_widget'];
		}

		return '';
	}


	/** @see WP_Widget::update
	 * @param array $new_instance New option values for this widget.
	 * @param array $old_instance Old, current option values for this widget.
	 *
	 * @return array
	 */
	function update( $new_instance, $old_instance ) {
		/**
		 *  Optional args
		 *  Taken from ul_get_info() in functions-display.php
		 *
	 	 *	'id'					=> '' ,
		 *	'show_name'				=> true,
		 *  'show_street'			=> true,
		 *	'show_street_2'			=> true,
		 *	'show_city'				=> true,
		 *	'show_state'			=> true,
		 *	'show_postcode'			=> true,
		 *	'show_country'			=> true,
		 *	'show_phone'			=> true,
		 *	'show_phone_2'			=> true,
		 *	'show_fax'				=> true,
		 *	'show_email'			=> true,
		 *	'show_url'				=> true,
		 *	'show_social'			=> true,
		 *	'comment'				=> '',
		 *	'show_opening_hours'	=> true,
		 *	'show_closed'			=> true,
		 */
		$instance						= $old_instance;
		$instance['title']				= esc_attr( $new_instance['title'] );
		$instance['show_name']			= esc_attr( $new_instance['show_name'] );
		$instance['show_street']		= esc_attr( $new_instance['show_street'] );
		$instance['show_street_2']		= esc_attr( $new_instance['show_street_2'] );
		$instance['show_city']			= esc_attr( $new_instance['show_city'] );
		$instance['show_state']			= esc_attr( $new_instance['show_state'] );
		$instance['show_postcode']		= esc_attr( $new_instance['show_postcode'] );
		$instance['show_country']		= esc_attr( $new_instance['show_country'] );
		$instance['show_phone']			= esc_attr( $new_instance['show_phone'] );
		$instance['show_phone_2']		= esc_attr( $new_instance['show_phone_2'] );
		$instance['show_fax']			= esc_attr( $new_instance['show_fax'] );
		$instance['show_email']			= esc_attr( $new_instance['show_email'] );
		$instance['show_url']			= esc_attr( $new_instance['show_url'] );
		$instance['show_social']		= esc_attr( $new_instance['show_social']);
		$instance['show_opening_hours']	= esc_attr( $new_instance['show_opening_hours'] );
		$instance['hide_closed']		= esc_attr( $new_instance['hide_closed'] );
		$instance['comment']			= esc_attr( $new_instance['comment'] );
		return $instance;
	}

	/** @see WP_Widget::form
	 * Displays the form for the widget options.
	 *
	 * @param array $instance Array with all the (saved) option values.
	 *
	 * @return string
	 */
	function form( $instance ) {
		$title				= ! empty( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$show_name			= ! empty( $instance['show_name'] ) && esc_attr( $instance['show_name'] ) == '1';
		$show_street		= ! empty( $instance['show_street'] ) && esc_attr( $instance['show_street'] ) == '1';
		$show_street_2		= ! empty( $instance['show_street_2'] ) && esc_attr( $instance['show_street_2'] ) == '1';
		$show_city			= ! empty( $instance['show_city'] ) && esc_attr( $instance['show_city'] ) == '1';
		$show_state			= ! empty( $instance['show_state'] ) && esc_attr( $instance['show_state'] ) == '1';
		$show_postcode		= ! empty( $instance['show_postcode'] ) && esc_attr( $instance['show_postcode'] ) == '1';
		$show_country		= ! empty( $instance['show_country'] ) && esc_attr( $instance['show_country'] ) == '1';
		$show_phone			= ! empty( $instance['show_phone'] ) && esc_attr( $instance['show_phone'] ) == '1';
		$show_phone_2		= ! empty( $instance['show_phone_2'] ) && esc_attr( $instance['show_phone_2'] ) == '1';
		$show_fax			= ! empty( $instance['show_fax'] ) && esc_attr( $instance['show_fax'] ) == '1';
		$show_email			= ! empty( $instance['show_email'] ) && esc_attr( $instance['show_email'] ) == '1';
		$show_url			= ! empty( $instance['show_url'] ) && esc_attr( $instance['show_url'] ) == '1';
		$show_social		= ! empty( $instance['show_social'] ) && esc_attr( $instance['show_social'] ) == '1';
		$show_opening_hours	= ! empty( $instance['show_opening_hours'] ) && esc_attr( $instance['show_opening_hours'] ) == '1';
		$hide_closed		= ! empty( $instance['hide_closed'] ) && esc_attr( $instance['hide_closed'] ) == '1';
		$comment			= ! empty( $instance['comment'] ) ? wp_kses_post( $instance['comment'] ) : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'user-locations' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'show_name' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_name' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_name' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_name ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show name', 'user-locations' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_street' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_street' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_street' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_street ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show street', 'user-locations' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_street_2' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_street_2' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_street_2' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_street_2 ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show street 2', 'user-locations' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_city' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_city' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_city' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_city ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show city', 'user-locations' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_state' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_state' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_state' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_state ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show state', 'user-locations' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_postcode' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_postcode' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_postcode' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_postcode ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show postcode', 'user-locations' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_country' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_country' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_country' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_country ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show country', 'user-locations' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_phone' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_phone' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_phone' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_phone ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show phone number', 'user-locations' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_phone_2' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_phone_2' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_phone_2' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_phone_2 ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show second phone number', 'user-locations' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_fax' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_fax' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_fax' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_fax ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show fax number', 'user-locations' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_email' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_email' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_email' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_email ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show email address', 'user-locations' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_url' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_url' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_url' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_url ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show url', 'user-locations' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_social' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_social' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_social' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_social ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show social links', 'user-locations' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_opening_hours' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_opening_hours' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_opening_hours' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_opening_hours ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show opening hours', 'user-locations' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'hide_closed' ); ?>">
				<input id="<?php echo $this->get_field_id( 'hide_closed' ); ?>"
				       name="<?php echo $this->get_field_name( 'hide_closed' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $hide_closed ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Hide closed days', 'user-locations' ); ?>
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'comment' ); ?>"><?php _e( 'Extra comment', 'user-locations' ); ?></label>
			<textarea id="<?php echo $this->get_field_id( 'comment' ); ?>" class="widefat" name="<?php echo $this->get_field_name( 'comment' ); ?>"><?php echo esc_attr( $comment ); ?></textarea>
		</p>
		<?php
		return '';
	}
}
