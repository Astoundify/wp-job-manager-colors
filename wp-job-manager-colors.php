<?php
/**
 * Plugin Name: WP Job Manager Job Type Colors
 * Plugin URI:  https://github.com/astoundify/wp-job-manager-colors
 * Description: Assign custom colors for each existing job type.
 * Author:      Spencer Finnell
 * Author URI:  http://spencerfinnell.com
 * Version:     0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

final class WP_Job_Manager_Colors {

	private static $instance;

	public static function instance() {
		if ( ! isset ( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function __construct() {
		$this->setup_actions();
	}

	private function setup_actions() {
		add_filter( 'job_manager_settings', array( $this, 'job_manager_settings' ) );
		add_action( 'wp_head', array( $this, 'output_colors' ) );
	}

	public function job_manager_settings( $settings ) {
		$settings[ 'job_colors' ] = array(
			__( 'Job Colors', 'job_manager_colors' ),
			$this->create_options()
		);

		return $settings;
	}

	private function create_options() {
		$terms   = get_terms( 'job_listing_type', array( 'hide_empty' => false ) );
		$options = array();

		$options[] = array(
			'name' 		  => 'job_manager_job_type_what_color',
			'std' 		  => 'background',
			'placeholder' => '',
			'label' 	  => __( 'What', 'job_manager_colors' ),
			'desc'        => __( 'Should these colors the the text color, or background color?', 'job_manager_colors' ),
			'type'        => 'select',
			'options'     => array(
				'background' => __( 'Background', 'job_manager_colors' ),
				'text'       => __( 'Text', 'job_manager_colors' )
			)
		);

		foreach ( $this->terms as $term ) {
			$options[] = array(
				'name' 		  => 'job_manager_job_type_' . $term->slug . '_color',
				'std' 		  => '',
				'placeholder' => '#',
				'label' 	  => '<strong>' . $term->name . '</strong>',
				'desc'		  => __( 'Hex value for the color of this job type.', 'job_manager_colors' )
			);
		}

		return $options;
	}

	function output_colors() {
		$terms   = get_terms( 'job_listing_type', array( 'hide_empty' => false ) );

		echo "<style id='job_manager_colors'>\n";

		foreach ( $terms as $term ) {
			$what = 'background' == get_option( 'job_manager_job_type_what_color' ) ? 'background-color' : 'color';

			printf( ".job-type.%s { %s: %s; } \n", $term->slug, $what, get_option( 'job_manager_job_type_' . $term->slug . '_color', '#fff' ) );
		}

		echo "</style>\n";
	}
}

add_action( 'init', array( 'WP_Job_Manager_Colors', 'instance' ) );