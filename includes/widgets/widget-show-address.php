<?php
/**
 * @package User_Locations
 */

/**
 * Class User_Locations_Show_Address.
 *
 * Creates widget for showing the address.
 */
class User_Locations_Show_Address extends WP_Widget {

	/**
	 * User_Locations_Show_Address constructor.
	 */
	function __construct() {
		$widget_options = array(
			'classname'   => 'User_Locations_Show_Address',
			'description' => __( 'Shows address of locations in Schema.org standards.', 'user-locations' ),
		);
		parent::__construct( false, $name = __( 'User Locations - Show Address', 'user-locations' ), $widget_options );
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
		$title              = apply_filters( 'widget_title', $instance['title'] );
		$show_country       = ! empty( $instance['show_country'] ) && $instance['show_country'] == '1';
		$show_state         = ! empty( $instance['show_state'] ) && $instance['show_state'] == '1';
		$show_phone         = ! empty( $instance['show_phone'] ) && $instance['show_phone'] == '1';
		$show_phone_2       = ! empty( $instance['show_phone_2'] ) && $instance['show_phone_2'] == '1';
		$show_fax           = ! empty( $instance['show_fax'] ) && $instance['show_fax'] == '1';
		$show_email         = ! empty( $instance['show_email'] ) && $instance['show_email'] == '1';
		$show_opening_hours = ! empty( $instance['show_opening_hours'] ) && $instance['show_opening_hours'] == '1';
		$hide_closed        = ! empty( $instance['hide_closed'] ) && $instance['hide_closed'] == '1';
		$show_oneline       = ! empty( $instance['show_oneline'] ) && $instance['show_oneline'] == '1';
		$comment            = ! empty( $instance['comment'] ) ? esc_attr( $instance['comment'] ) : '';

		if ( ! userlocations_is_singular_location() ) {
			return '';
		}

		$location_id = get_user_by( 'slug', get_query_var( 'author_name' ) )->ID;

		if ( isset( $args['before_widget'] ) ) {
			echo $args['before_widget'];
		}

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$shortcode_args = array(
			'id'                 => $location_id,
			'show_country'       => $show_country,
			'show_state'         => $show_state,
			'show_phone'         => $show_phone,
			'show_phone_2'       => $show_phone_2,
			'show_fax'           => $show_fax,
			'show_email'         => $show_email,
			'show_opening_hours' => $show_opening_hours,
			'hide_closed'        => $hide_closed,
			'oneline'            => $show_oneline,
			'comment'            => $comment,
			'from_widget'        => true,
			'widget_title'       => $title,
			'before_title'       => $args['before_title'],
			'after_title'        => $args['after_title'],
		);

		echo userlocations_show_address( $shortcode_args );

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
		$instance                       = $old_instance;
		$instance['title']              = esc_attr( $new_instance['title'] );
		$instance['show_country']       = esc_attr( $new_instance['show_country'] );
		$instance['show_state']         = esc_attr( $new_instance['show_state'] );
		$instance['show_phone']         = esc_attr( $new_instance['show_phone'] );
		$instance['show_phone_2']       = esc_attr( $new_instance['show_phone_2'] );
		$instance['show_fax']           = esc_attr( $new_instance['show_fax'] );
		$instance['show_email']         = esc_attr( $new_instance['show_email'] );
		$instance['show_opening_hours'] = esc_attr( $new_instance['show_opening_hours'] );
		$instance['hide_closed']        = esc_attr( $new_instance['hide_closed'] );
		$instance['show_oneline']       = esc_attr( $new_instance['show_oneline'] );
		$instance['comment']            = esc_attr( $new_instance['comment'] );

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
		$title              = ( ! empty( $instance['title'] ) ) ? esc_attr( $instance['title'] ) : '';
		$show_country       = ! empty( $instance['show_country'] ) && esc_attr( $instance['show_country'] ) == '1';
		$show_state         = ! empty( $instance['show_state'] ) && esc_attr( $instance['show_state'] ) == '1';
		$show_phone         = ! empty( $instance['show_phone'] ) && esc_attr( $instance['show_phone'] ) == '1';
		$show_phone_2       = ! empty( $instance['show_phone_2'] ) && esc_attr( $instance['show_phone_2'] ) == '1';
		$show_fax           = ! empty( $instance['show_fax'] ) && esc_attr( $instance['show_fax'] ) == '1';
		$show_email         = ! empty( $instance['show_email'] ) && esc_attr( $instance['show_email'] ) == '1';
		$show_opening_hours = ! empty( $instance['show_opening_hours'] ) && esc_attr( $instance['show_opening_hours'] ) == '1';
		$hide_closed        = ! empty( $instance['hide_closed'] ) && esc_attr( $instance['hide_closed'] ) == '1';
		$show_oneline       = ! empty( $instance['show_oneline'] ) && esc_attr( $instance['show_oneline'] ) == '1';
		$comment            = ! empty( $instance['comment'] ) ? esc_attr( $instance['comment'] ) : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'user-locations' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
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
			<label for="<?php echo $this->get_field_id( 'show_state' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_state' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_state' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_state ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show state', 'user-locations' ); ?>
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
			<label for="<?php echo $this->get_field_id( 'show_oneline' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_oneline' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_oneline' ); ?>" type="checkbox"
				       value="1" <?php echo ! empty( $show_oneline ) ? ' checked="checked"' : ''; ?> />
				<?php _e( 'Show address in one line', 'user-locations' ); ?>
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
