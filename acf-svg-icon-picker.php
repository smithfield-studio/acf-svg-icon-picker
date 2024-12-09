<?php

/**
 * Plugin Name: Advanced Custom Fields: SVG Icon Picker
 * Plugin URI: https://github.com/smithfield-studio/acf-svg-icon-picker
 * Description: Allows you to pick an icon from a predefined list
 * Version: 4.1.0
 * Author: Smithfield & Studio Lemon
 * Author URI: https://github.com/smithfield-studio/acf-svg-icon-picker/
 * Text Domain: acf-svg-icon-picker
 * License: MIT
 * License URI: https://opensource.org/license/mit
 * GitHub Plugin URI: https://github.com/smithfield-studio/acf-svg-icon-picker
 * GitHub Branch: main
 * Requires PHP: 8.1
 *
 * @package Advanced Custom Fields: SVG Icon Picker
 **/

namespace SmithfieldStudio\AcfSvgIconPicker;

defined('ABSPATH') || exit;

/**
 * Change this version number and the version in the
 * docblock above when releasing a new version of this plugin.
 */
define('ACF_SVG_ICON_PICKER_VERSION', '4.1.0');

define('ACF_SVG_ICON_PICKER_URL', plugin_dir_url(__FILE__));
define('ACF_SVG_ICON_PICKER_PATH', plugin_dir_path(__FILE__));

/**
 * Include SVG Icon Picker field type.
 */
function include_field_types(): void
{
	if (! function_exists('acf_register_field_type')) {
		return;
	}

	require_once __DIR__ . '/class-acf-field-svg-icon-picker.php';
	acf_register_field_type('SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker');
}

add_action('init', __NAMESPACE__ . '\\include_field_types');


/**
 * Get the URI of an SVG icon.
 *
 * @api
 * @since 4.0.0
 * @param string $icon_name The name of the icon we want to get the URI for.
 * @return string The URI of the icon, empty string if the icon does not exist.
 */
function get_svg_icon_uri(string $icon_name): string
{
	if (false == get_svg_icon_path($icon_name)) {
		return '';
	}

	$location = apply_filters('acf_svg_icon_picker_folder', 'icons/');

	return get_theme_file_uri("{$location}{$icon_name}.svg");
}

/**
 * Get the path of an SVG icon.
 *
 * @api
 * @param string $icon_name The name of the icon we want to get the path for.
 * @return string The path of the icon, empty string if the icon does not exist.
 */
function get_svg_icon_path(string $icon_name): string
{
	$location = apply_filters('acf_svg_icon_picker_folder', 'icons/');

	$path = get_theme_file_path("{$location}{$icon_name}.svg");

	if (! file_exists($path)) {
		return '';
	}

	return $path;
}

/**
 * Get the SVG icon.
 *
 * @api
 * @param string $icon_name The name of the icon we want to get.
 * @return string The SVG icon file, empty string if the icon does not exist.
 */
function get_svg_icon(string $icon_name): string
{
	$path = get_svg_icon_path($icon_name);

	if (! $path) {
		return '';
	}

	return file_get_contents($path);
}
