<?php
/**
 * Plugin Name: Advanced Custom Fields: SVG Icon Picker
 * Plugin URI: https://github.com/smithfield-studio/acf-svg-icon-picker
 * Description: Allows you to pick an icon from a predefined list
 * Version: 3.0.0
 * Author: Houke de Kwant
 * Author URI: https://github.com/houke/
 * Text Domain: acf-svg-icon-picker
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/smithfield-studio/acf-svg-icon-picker
 * GitHub Branch: main
 * Requires at least: 6.4
 * Requires PHP: 7.4
 *
 * @package Advanced Custom Fields: SVG Icon Picker
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Acf_Plugin_Svg_Icon_Picker' ) ) {
	/**
	 * Plugin class.
	 */
	class Acf_Plugin_Svg_Icon_Picker {

		/**
		 * Plugin settings.
		 *
		 * @var array
		 */
		public static $settings = array();

		/**
		 * Constructor.
		 */
		public function __construct() {
			self::$settings = array(
				'version' => '3.0.0',
				'url'     => plugin_dir_url( __FILE__ ),
				'path'    => plugin_dir_path( __FILE__ ),
			);

			add_action( 'init', array( $this, 'include_field_types' ) );
		}

		/**
		 * Include SVG Icon Picker field type.
		 */
		public function include_field_types() {
			if ( ! function_exists( 'acf_register_field_type' ) ) {
				return;
			}

			require_once __DIR__ . '/class-acf-field-svg-icon-picker.php';
			acf_register_field_type( 'ACF_Field_Svg_Icon_Picker' );
		}
	}
}

new Acf_Plugin_Svg_Icon_Picker();
