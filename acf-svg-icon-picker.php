<?php

/**
 * Plugin Name: Advanced Custom Fields: SVG Icon Picker
 * Plugin URI: https://github.com/smithfield-studio/acf-svg-icon-picker
 * Description: Allows you to pick an icon from a predefined list
 * Version: 5.0.0
 * Author: Smithfield & Studio Lemon
 * Author URI: https://github.com/smithfield-studio/acf-svg-icon-picker/
 * Text Domain: acf-svg-icon-picker
 * License: MIT
 * License URI: https://opensource.org/license/mit
 * GitHub Plugin URI: https://github.com/smithfield-studio/acf-svg-icon-picker
 * GitHub Branch: main
 * Requires PHP: 8.2
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
require_once __DIR__ . '/src/Field.php';

/**
 * Include SVG Icon Picker field type.
 */
function include_field_types(): void {
    if (!function_exists('acf_register_field_type')) {
        return;
    }

    acf_register_field_type(ACF_Field_Svg_Icon_Picker::class);
}

add_action('acf/include_field_types', __NAMESPACE__ . '\\include_field_types');

/**
 * Register the field with WPGraphQL when the wp-graphql-acf bridge is active.
 *
 * Exposes the saved slug as a `SvgIcon` object with the resolved URL and inline
 * SVG markup so headless consumers can render directly without a second round
 * trip. Resolution mirrors the PHP helpers — a missing icon resolves to empty
 * `url`/`svg` strings, never an exception.
 *
 * Registers unconditionally; both hooks only fire if WPGraphQL is active, and
 * the inner function_exists() guards keep us safe if the wp-graphql-acf bridge
 * is missing while WPGraphQL itself is present.
 */
add_action('graphql_register_types', static function (): void {
    if (!function_exists('register_graphql_object_type')) {
        return;
    }

    register_graphql_object_type('SvgIcon', [
        'description' => __('An SVG icon picked from the configured icon set.', 'acf-svg-icon-picker'),
        'fields' => [
            'slug' => [
                'type' => 'String',
                'description' => __(
                    'The saved slug. Bare slug in flat mode, "groupkey.slug" in grouped mode.',
                    'acf-svg-icon-picker',
                ),
            ],
            'url' => [
                'type' => 'String',
                'description' => __('Public URL of the resolved SVG file.', 'acf-svg-icon-picker'),
            ],
            'svg' => [
                'type' => 'String',
                'description' => __('Inline SVG markup for the resolved icon.', 'acf-svg-icon-picker'),
            ],
        ],
    ]);
});

add_action('wpgraphql/acf/registry_init', static function (): void {
    if (!function_exists('register_graphql_acf_field_type')) {
        return;
    }

    register_graphql_acf_field_type('svg_icon_picker', [
        'graphql_type' => 'SvgIcon',
        'resolve' => static function ($root, $args, $context, $info, $field_config) {
            $slug = is_object($field_config) && method_exists($field_config, 'resolve_field')
                ? $field_config->resolve_field($root, $args, $context, $info)
                : null;

            if (!is_string($slug) || $slug === '') {
                return null;
            }

            return [
                'slug' => $slug,
                'url' => get_svg_icon_uri($slug),
                'svg' => get_svg_icon($slug),
            ];
        },
    ]);
});
