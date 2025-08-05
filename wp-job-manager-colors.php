<?php
/**
 * Plugin Name: WP Job Manager - Job Type Colors
 * Plugin URI:  https://github.com/astoundify/wp-job-manager-colors
 * Description: Assign custom colors for each existing job type.
 * Author:      Astoundify
 * Author URI:  http://astoundify.com
 * Version:     1.0.5
 * Text Domain: job_manager_colors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Job_Manager_Colors {

	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function __construct() {
		$this->setup_actions();
		$this->sanitize_plugin_options();
	}

	private function setup_actions() {
		add_filter( 'job_manager_settings', array( $this, 'job_manager_settings' ) );
		add_action( 'wp_head', array( $this, 'output_colors' ) );

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'colorpickers' ) );
			add_action( 'admin_footer', array( $this, 'colorpickersjs' ) );
		}
	}

	// Sanitize options before saving — prevent script/HTML/invalid hex
	public function sanitize_plugin_options() {
		add_filter( 'pre_update_option', function( $value, $option ) {
			// Only affect plugin options
			if ( strpos( $option, 'job_manager_job_type_' ) === 0 ) {

				// Handle "what color" dropdown safely
				if ( $option === 'job_manager_job_type_what_color' ) {
					return in_array( $value, [ 'background', 'text' ], true ) ? $value : 'background';
				}

				// All other color fields: sanitize as hex
				if ( strpos( $option, '_color' ) !== false ) {
					return sanitize_hex_color( $value ) ?: '';
				}
			}
			return $value;
		}, 10, 2 );
	}

	public function job_manager_settings( $settings ) {
		$settings['job_colors'] = array(
			__( 'Job Colors', 'job_manager_colors' ),
			$this->create_options()
		);
		return $settings;
	}

	private function create_options() {
		$terms   = get_terms( 'job_listing_type', array( 'hide_empty' => false ) );
		$options = array();

		$options[] = array(
			'name'        => 'job_manager_job_type_what_color',
			'std'         => 'background',
			'placeholder' => '',
			'label'       => esc_html__( 'What', 'job_manager_colors' ),
			'desc'        => esc_html__( 'Should these colors be applied to the text color, or background color?', 'job_manager_colors' ),
			'type'        => 'select',
			'options'     => array(
				'background' => esc_html__( 'Background', 'job_manager_colors' ),
				'text'       => esc_html__( 'Text', 'job_manager_colors' )
			)
		);

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_slug = isset( $term->slug ) ? sanitize_title( $term->slug ) : '';
				$term_name = isset( $term->name ) ? esc_html( $term->name ) : '';

				$options[] = array(
					'name'        => 'job_manager_job_type_' . $term_slug . '_color',
					'std'         => '',
					'placeholder' => '#',
					'label'       => $term_name,
					'desc'        => esc_html__( 'Hex value for the color of this job type.', 'job_manager_colors' ),
					'attributes'  => array(
						'data-default-color' => '#fff',
						'data-type'          => 'colorpicker'
					)
				);
			}
		}

		return $options;
	}

	public function output_colors() {
		$terms = get_terms( 'job_listing_type', array( 'hide_empty' => false ) );

		echo "<style id='job_manager_colors'>\n";

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$what_setting = get_option( 'job_manager_job_type_what_color' );
			$what = $what_setting === 'text' ? 'color' : 'background-color';

			foreach ( $terms as $term ) {
				$term_id = esc_attr( $term->term_id );
				$term_slug = esc_attr( $term->slug );
				$color_raw = get_option( 'job_manager_job_type_' . $term_slug . '_color', '#fff' );
				$color = sanitize_hex_color( $color_raw );

				if ( $color ) {
					printf(
						".job-type.term-%s, .job-type.%s { %s: %s; } \n",
						$term_id,
						$term_slug,
						esc_attr( $what ),
						esc_attr( $color )
					);
				}
			}
		}

		echo "</style>\n";
	}

	public function colorpickers( $hook ) {
		$screen = get_current_screen();
		if ( 'job_listing_page_job-manager-settings' !== $screen->id ) {
			return;
		}
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}

	public function colorpickersjs() {
		$screen = get_current_screen();
		if ( 'job_listing_page_job-manager-settings' !== $screen->id ) {
			return;
		}
		?>
		<script>
			jQuery(document).ready(function($){
				$('input[data-type="colorpicker"]').wpColorPicker();
			});
		</script>
		<?php
	}
}

add_action( 'init', array( 'WP_Job_Manager_Colors', 'instance' ) );
