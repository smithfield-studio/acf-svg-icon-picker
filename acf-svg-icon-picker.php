<?php

/**
 * Plugin Name:         Advanced Custom Fields: SVG Icon Picker
 * Plugin URI:          https://github.com/smithfield-studio/acf-svg-icon-picker
 * Description:         Allows you to pick an icon from a predefined list
 * Version:             5.0.2
 * Author:              Smithfield & Studio Lemon
 * Author URI:          https://github.com/smithfield-studio/acf-svg-icon-picker/
 * Text Domain:         acf-svg-icon-picker
 * Domain Path:         /resources/languages
 * License:             MIT
 * License URI:         https://opensource.org/license/mit
 * GitHub Plugin URI:   https://github.com/smithfield-studio/acf-svg-icon-picker
 * GitHub Branch:       main
 * Requires PHP:        8.2
 *
 * @package Advanced Custom Fields: SVG Icon Picker
 **/

namespace SmithfieldStudio\AcfSvgIconPicker;

defined('ABSPATH') || exit();

/**
 * Captured at parse time so the field class can resolve plugin-relative URLs
 * and paths (assets, view templates) without each call site doing its own
 * `dirname(__DIR__)` dance from inside src/.
 */
const PLUGIN_FILE = __FILE__;

// Manual requires (no Composer autoloader at runtime). The plugin lives in
// wp-content/plugins/ on both zip-drop and Composer installs, where the host
// project's autoloader can't reach it — so the bootstrap always loads its own
// files. The composer.json `autoload` section is kept as IDE/PHPStan metadata
// only.
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/graphql.php';

/**
 * Include SVG Icon Picker field type.
 */
function include_field_types(): void {
    if (!function_exists('acf_register_field_type')) {
        return;
    }

    require_once __DIR__ . '/src/Field.php';
    acf_register_field_type(ACF_Field_Svg_Icon_Picker::class);
}

add_action('acf/include_field_types', __NAMESPACE__ . '\\include_field_types');
