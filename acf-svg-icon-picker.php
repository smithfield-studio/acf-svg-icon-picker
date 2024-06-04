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
 * Requires PHP: 7.4
 *
 * @package Advanced Custom Fields: SVG Icon Picker
 **/

namespace SmithfieldStudio\AcfSvgIconPicker;

defined( 'ABSPATH' ) || exit;

/**
 * Change this version number and the version in the 
 * docblock above when releasing a new version of this plugin.
 */
define( 'ACF_SVG_ICON_PICKER_VERSION', '3.0.0' );

define( 'ACF_SVG_ICON_PICKER_URL', plugin_dir_url( __FILE__ ) );
define( 'ACF_SVG_ICON_PICKER_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Include SVG Icon Picker field type.
 */
function include_field_types() {
	if ( ! function_exists( 'acf_register_field_type' ) ) {
		return;
	}

	require_once __DIR__ . '/class-acf-field-svg-icon-picker.php';
	acf_register_field_type( 'SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker' );
}

add_action( 'init', __NAMESPACE__ .'\\include_field_types' );
