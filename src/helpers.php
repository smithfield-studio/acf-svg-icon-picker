<?php

/**
 * Public API helpers for resolving saved icon values to URLs/paths/markup, plus
 * the internal filter-normalisation + per-location resolution used by both the
 * helpers and the field class.
 *
 * Loaded via Composer's `files` autoload (see composer.json) and manually
 * required from the plugin bootstrap when vendor/autoload.php is missing.
 *
 * @package Advanced Custom Fields: SVG Icon Picker
 */

namespace SmithfieldStudio\AcfSvgIconPicker;

defined('ABSPATH') || exit();

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
    if (!is_array($filter_result) || $filter_result === []) {
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

    if ($locations !== []) {
        $resolved = resolve_in_locations($locations, $icon_name);
        return $resolved === null ? '' : $resolved['url'];
    }

    if (get_svg_icon_path($icon_name) === '') {
        return '';
    }

    $folder = apply_filters('acf_svg_icon_picker_folder', 'icons/');
    if (!is_string($folder)) {
        $folder = 'icons/';
    }

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

    if ($locations !== []) {
        $resolved = resolve_in_locations($locations, $icon_name);
        return $resolved === null ? '' : $resolved['path'];
    }

    $folder = apply_filters('acf_svg_icon_picker_folder', 'icons/');
    if (!is_string($folder)) {
        $folder = 'icons/';
    }
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
 * Composite saves are strict: the group prefix must match a configured group
 * (or a subdir whose `sanitize_title()` matches), otherwise we 404 rather
 * than substitute a same-slug icon from a different group. Bare slugs are
 * legacy data (pre-composite, or a flat-mode sibling field) and still scan
 * all locations first-match-wins.
 *
 * @internal
 * @param list<array{path: string, url: string, name?: string, key?: string, group_by_subdir?: bool}> $locations
 * @return array{path: string, url: string}|null
 */
function resolve_in_locations(array $locations, string $icon_name): ?array {
    if (str_contains($icon_name, '.')) {
        // Composite save: strict. The group prefix records the editor's
        // explicit pick; if the group no longer matches any configured
        // location we 404 rather than silently substitute a same-slug icon
        // from a different group (which would change the visual intent).
        // Migrate stale data with a one-shot rebind in your theme/plugin
        // rather than relying on fallback resolution.
        [$group_key, $slug] = explode('.', $icon_name, 2);
        foreach ($locations as $location) {
            $resolved = resolve_icon_in_location($location, $slug, $group_key);
            if ($resolved !== null) {
                return $resolved;
            }
        }
        return null;
    }

    // Bare slug: legacy data (pre-composite saves, or a sibling field in
    // flat-mode). First-match-wins scan across all locations.
    foreach ($locations as $location) {
        $resolved = resolve_icon_in_location($location, $icon_name);
        if ($resolved !== null) {
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

    if ($base_path === '') {
        return null;
    }

    if ($group_key_filter !== null && empty($location['group_by_subdir'])) {
        // Top-level location: must declare a matching key (or slugified name).
        $raw_key = $location['key'] ?? $location['name'] ?? '';
        if (sanitize_title($raw_key) !== $group_key_filter) {
            return null;
        }
    }

    if (empty($location['group_by_subdir'])) {
        $flat_path = "{$base_path}/{$icon_name}.svg";
        if (file_exists($flat_path)) {
            return [
                'path' => $flat_path,
                'url' => "{$base_url}" . rawurlencode($icon_name) . '.svg',
            ];
        }
        return null;
    }

    if (!is_dir($base_path)) {
        return null;
    }

    $entries = scandir($base_path);
    if ($entries === false) {
        return null;
    }

    // Subdir mode: scan each subdir, comparing on sanitize_title($subdir) so
    // a saved key like 'brand-icons' resolves the literal `Brand Icons/`
    // folder. Without filter, scan all subdirs (legacy bare-slug fallback).
    foreach ($entries as $subdir) {
        if ($subdir === '.' || $subdir === '..') {
            continue;
        }
        $full = "{$base_path}/{$subdir}";
        if (!is_dir($full)) {
            continue;
        }
        if ($group_key_filter !== null && sanitize_title($subdir) !== $group_key_filter) {
            continue;
        }

        $candidate = "{$full}/{$icon_name}.svg";
        if (file_exists($candidate)) {
            return [
                'path' => $candidate,
                'url' => "{$base_url}" . rawurlencode($subdir) . '/' . rawurlencode($icon_name) . '.svg',
            ];
        }
    }

    return null;
}

/**
 * Get the SVG icon.
 *
 * Results are memoized per-request so the same icon rendered N times on a page
 * (e.g. via a repeater + 'icon' return format) only reads from disk once.
 *
 * @api
 * @param string $icon_name The name of the icon we want to get.
 * @return string The SVG icon file, empty string if the icon does not exist.
 */
function get_svg_icon(string $icon_name): string {
    /** @var array<string, string> $cache */
    static $cache = [];

    if (isset($cache[$icon_name])) {
        return $cache[$icon_name];
    }

    $path = get_svg_icon_path($icon_name);
    $cache[$icon_name] = $path === '' ? '' : (file_get_contents($path) ?: '');

    return $cache[$icon_name];
}
