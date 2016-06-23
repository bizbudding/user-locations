<?php
/**
 * Parisi
 *
 * @package   Parisi
 * @author    Mike Hemberger <mike@bizbudding.com.com>
 * @link      https://github.com/JiveDig/Parisi/
 * @copyright 2016 Mike Hemberger
 * @license   GPL-2.0+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main plugin class.
 *
 * @package Parisi
 */
final class Parisi_Content_Types {
	/** Singleton *************************************************************/

	/**
	 * @var Parisi_Content_Types The one true Parisi_Content_Types
	 * @since 1.0.0
	 */
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new Parisi_Content_Types;
			// Methods
			self::$instance->init();
		}
		return self::$instance;
	}

	public function __construct() {
	}

	public function init() {
		// Actions
		add_action( 'init', array( $this, 'register_post_types'), 0 );
	}

	/**
	 * Register custom post types
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function register_post_types() {

		// Programs
	    register_extended_post_type( 'user_page', array(
			'enter_title_here'    => 'Enter Page Name',
			'menu_icon'		      => 'dashicons-admin-page',
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_in_nav_menus'   => false,
			'show_ui'             => true,
		    'has_archive'         => false,
			'supports' 	          => array('title','editor'),
			'capability_type'		  => 'post',
	    ), array(
	        'singular' => 'Page',
	        'plural'   => 'Pages',
	        'slug'     => 'user_pages'
	    ) );

	    // Steps
	    // $step = self::STEP;
	    // register_extended_post_type( $step, array(
		// 	'enter_title_here'	=> 'Enter ' . $this->singular_name($step) . ' Name',
		// 	'menu_icon'			=> 'dashicons-feedback',
		//     'rewrite' 			=> array(
		//         // 'permastruct' => '/' . $this->get_program_base_slug() . '/%Parisi_step_program%/%Parisi_step%',
		//         'slug' => $this->get_program_base_slug() . '/%Parisi_step_program%',
		//     ),
		//     'has_archive' 		=> apply_filters( 'Parisi_step_has_archive', false ),
		// 	'supports'			=> apply_filters( 'Parisi_step_supports', array('title','editor','excerpt','thumbnail','genesis-cpt-archives-settings') ),
		  //   'admin_cols' 		=> array(
				// 'programs_to_steps' => array(
				//     'title'      => $this->plural_name(self::PROGRAM),
				//     'connection' => 'programs_to_steps',
				//     'link'       => 'edit',
				// ),
				// 'steps_to_resources' => array(
				//     'title'      => $this->plural_name(self::RESOURCE),
				//     'connection' => 'steps_to_resources',
				//     'link'       => 'edit',
				// ),
		  //   ),
	    // ), $this::default_names()[$step] );

	}

}
