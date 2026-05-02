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
 * Requires PHP: 8.1
 *
 * @package Advanced Custom Fields: SVG Icon Picker
 **/

namespace SmithfieldStudio\AcfSvgIconPicker;

defined('ABSPATH') || exit();

/**
 * Change this version number and the version in the
 * docblock above when releasing a new version of this plugin.
 */
define('ACF_SVG_ICON_PICKER_VERSION', '5.0.0');

define('ACF_SVG_ICON_PICKER_URL', plugin_dir_url(__FILE__));
define('ACF_SVG_ICON_PICKER_PATH', plugin_dir_path(__FILE__));

/**
 * Include SVG Icon Picker field type.
 */
function include_field_types(): void {
    if (!function_exists('acf_register_field_type')) {
        return;
    }

    require_once __DIR__ . '/class-acf-field-svg-icon-picker.php';
    acf_register_field_type('SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker');
}

add_action('acf/include_field_types', __NAMESPACE__ . '\\include_field_types');

/**
 * Normalize the acf_svg_icon_picker_custom_location filter result into a
 * list of locations. Accepts either a single { path, url } array or a list
 * of them. Returns an empty list when the filter is unset or invalid.
 *
 * @internal
 * @param  mixed $filter_result Raw value returned by the filter.
 * @return list<array{path: string, url: string, name?: string, key?: string, group_by_subdir?: bool}>
 */
function normalize_custom_locations(mixed $filter_result): array {
    if (!is_array($filter_result) || empty($filter_result)) {
        return [];
    }

    if (
        isset($filter_result['path'], $filter_result['url'])
        && is_string($filter_result['path'])
        && is_string($filter_result['url'])
    ) {
        /** @var array{path: string, url: string, name?: string, key?: string, group_by_subdir?: bool} $single */
        $single = $filter_result;
        return [$single];
    }

    if (array_is_list($filter_result)) {
        $valid = [];
        foreach ($filter_result as $location) {
            if (
                !is_array($location)
                || !isset($location['path'], $location['url'])
                || !is_string($location['path'])
                || !is_string($location['url'])
            ) {
                continue;
            }

            /** @var array{path: string, url: string, name?: string, key?: string, group_by_subdir?: bool} $location */
            $valid[] = $location;
        }
        return $valid;
    }

    return [];
}

/**
 * Get the URI of an SVG icon.
 *
 * @api
 * @since 4.0.0
 * @param string $icon_name The name of the icon we want to get the URI for.
 * @return string The URI of the icon, empty string if the icon does not exist.
 */
function get_svg_icon_uri(string $icon_name): string {
    $locations = normalize_custom_locations(apply_filters('acf_svg_icon_picker_custom_location', false));

    if (!empty($locations)) {
        $resolved = resolve_in_locations($locations, $icon_name);
        return null === $resolved ? '' : $resolved['url'];
    }

    if ('' === get_svg_icon_path($icon_name)) {
        return '';
    }

    $folder = apply_filters('acf_svg_icon_picker_folder', 'icons/');

    return get_theme_file_uri("{$folder}{$icon_name}.svg");
}

/**
 * Get the path of an SVG icon.
 *
 * @api
 * @param string $icon_name The name of the icon we want to get the path for.
 * @return string The path of the icon, empty string if the icon does not exist.
 */
function get_svg_icon_path(string $icon_name): string {
    $locations = normalize_custom_locations(apply_filters('acf_svg_icon_picker_custom_location', false));

    if (!empty($locations)) {
        $resolved = resolve_in_locations($locations, $icon_name);
        return null === $resolved ? '' : $resolved['path'];
    }

    $folder = apply_filters('acf_svg_icon_picker_folder', 'icons/');
    $file_path = get_theme_file_path("{$folder}{$icon_name}.svg");

    if (!file_exists($file_path)) {
        return '';
    }

    return $file_path;
}

/**
 * Resolve a saved value (composite `groupkey.slug` or bare slug) to a
 * { path, url } pair across a list of locations.
 *
 * Composite is tried first against the matching group only; bare slugs (and
 * composites that don't resolve) fall back to a first-match scan across all
 * locations — preserving back-compat with values saved before composite keys
 * existed.
 *
 * @internal
 * @param list<array{path: string, url: string, name?: string, key?: string, group_by_subdir?: bool}> $locations
 * @return array{path: string, url: string}|null
 */
function resolve_in_locations(array $locations, string $icon_name): ?array {
    if (str_contains($icon_name, '.')) {
        [$group_key, $slug] = explode('.', $icon_name, 2);
        foreach ($locations as $location) {
            $resolved = resolve_icon_in_location($location, $slug, $group_key);
            if (null !== $resolved) {
                return $resolved;
            }
        }
    }

    foreach ($locations as $location) {
        $resolved = resolve_icon_in_location($location, $icon_name);
        if (null !== $resolved) {
            return $resolved;
        }
    }

    return null;
}

/**
 * Resolve an icon name to a { path, url } pair within a single location,
 * honouring `group_by_subdir`. Returns null when the icon is not found.
 *
 * When `$group_key_filter` is non-null the lookup is constrained to that
 * group: a top-level location must declare a matching `key` (or slugified
 * `name`); a subdir-mode location only checks the matching subfolder.
 *
 * @internal
 * @param array{path: string, url: string, name?: string, key?: string, group_by_subdir?: bool} $location          Location config.
 * @param string                                                                                 $icon_name        Icon slug (without group prefix).
 * @param string|null                                                                            $group_key_filter Optional group key to constrain the search to.
 * @return array{path: string, url: string}|null
 */
function resolve_icon_in_location(array $location, string $icon_name, ?string $group_key_filter = null): ?array {
    $base_path = rtrim($location['path'], '/\\');
    $base_url = trailingslashit($location['url']);

    if ('' === $base_path) {
        return null;
    }

    if (null !== $group_key_filter) {
        if (!empty($location['group_by_subdir'])) {
            $candidate = "{$base_path}/{$group_key_filter}/{$icon_name}.svg";
            if (file_exists($candidate)) {
                return [
                    'path' => $candidate,
                    'url' => "{$base_url}{$group_key_filter}/{$icon_name}.svg",
                ];
            }
            return null;
        }

        $raw_key = $location['key'] ?? $location['name'] ?? '';
        if (sanitize_title($raw_key) !== $group_key_filter) {
            return null;
        }
    }

    $flat_path = "{$base_path}/{$icon_name}.svg";
    if (file_exists($flat_path)) {
        return [
            'path' => $flat_path,
            'url' => "{$base_url}{$icon_name}.svg",
        ];
    }

    if (null !== $group_key_filter || empty($location['group_by_subdir']) || !is_dir($base_path)) {
        return null;
    }

    $entries = scandir($base_path);
    $subdirs = false === $entries
        ? []
        : array_filter($entries, fn($entry) => '.' !== $entry && '..' !== $entry && is_dir("{$base_path}/{$entry}"));

    foreach ($subdirs as $subdir) {
        $candidate = "{$base_path}/{$subdir}/{$icon_name}.svg";
        if (file_exists($candidate)) {
            return [
                'path' => $candidate,
                'url' => "{$base_url}{$subdir}/{$icon_name}.svg",
            ];
        }
    }

    return null;
}

/**
 * Get the SVG icon.
 *
 * @api
 * @param string $icon_name The name of the icon we want to get.
 * @return string The SVG icon file, empty string if the icon does not exist.
 */
function get_svg_icon(string $icon_name): string {
    $path = get_svg_icon_path($icon_name);

    if (!$path) {
        return '';
    }

    $contents = file_get_contents($path);

    return false === $contents ? '' : $contents;
}
