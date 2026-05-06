<?php

/**
 * Public API helpers for resolving saved icon values to URLs/paths/markup, plus
 * the internal filter-normalisation, group expansion, and disk-scanning used by
 * the field class. Single source of truth for how an icon name maps to a file.
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
 * Append `-2`, `-3`, … to a base key until it doesn't collide with any
 * already-used key.
 *
 * @internal
 * @param list<string> $used_keys
 */
function disambiguate_group_key(string $base_key, array $used_keys): string {
    if (!in_array($base_key, $used_keys, true)) {
        return $base_key;
    }
    $n = 2;
    while (in_array("{$base_key}-{$n}", $used_keys, true)) {
        $n++;
    }
    return "{$base_key}-{$n}";
}

/**
 * Expand the filter result into a flat list of resolved groups. Each entry
 * points at a single concrete folder with a stable, sanitised, disambiguated
 * group key — the field class and the public helpers consume this same list
 * so saved values resolve identically in either path.
 *
 *  - Single `{path, url}` → one group (key derived from name/key, or `group-0`).
 *  - List of locations → one group per entry.
 *  - Single location with `group_by_subdir => true` → one group per top-level subdir.
 *
 * Group keys collide-resolve via `-2`, `-3`, … so a saved `groupkey.slug`
 * always maps to one specific folder.
 *
 * @internal
 * @return list<array{key: string, name: string, path: string, url: string}>
 */
function expand_locations_to_groups(mixed $filter_result): array {
    $locations = normalize_custom_locations($filter_result);
    if ($locations === []) {
        return [];
    }

    $groups = [];
    /** @var list<string> $used_keys */
    $used_keys = [];

    foreach ($locations as $i => $location) {
        if (!empty($location['group_by_subdir'])) {
            $base_path = rtrim($location['path'], '/\\');
            $base_url = trailingslashit($location['url']);

            if (!is_dir($base_path)) {
                continue;
            }

            $entries = scandir($base_path);
            if ($entries === false) {
                continue;
            }

            foreach ($entries as $subdir) {
                if ($subdir === '.' || $subdir === '..') {
                    continue;
                }
                $sub_path = "{$base_path}/{$subdir}";
                if (!is_dir($sub_path)) {
                    continue;
                }

                $base_key = sanitize_title($subdir) ?: "group-{$i}";
                $key = disambiguate_group_key($base_key, $used_keys);
                $used_keys[] = $key;

                $groups[] = [
                    'key' => $key,
                    'name' => ucwords(str_replace(['-', '_'], ' ', $subdir)),
                    'path' => $sub_path,
                    'url' => "{$base_url}" . rawurlencode($subdir) . '/',
                ];
            }
            continue;
        }

        $name = $location['name'] ?? '';
        $raw_key = $location['key'] ?? $name;
        $base_key = sanitize_title($raw_key) ?: "group-{$i}";
        $key = disambiguate_group_key($base_key, $used_keys);
        $used_keys[] = $key;

        $groups[] = [
            'key' => $key,
            'name' => $name,
            'path' => rtrim($location['path'], '/\\'),
            'url' => trailingslashit($location['url']),
        ];
    }

    return $groups;
}

/**
 * Memoised wrapper around `expand_locations_to_groups()`. Caches against the
 * raw filter result so a test (or a request) that re-applies the filter with
 * a different config invalidates the cache automatically.
 *
 * @internal
 * @return list<array{key: string, name: string, path: string, url: string}>
 */
function get_resolved_groups(): array {
    /** @var list<array{key: string, name: string, path: string, url: string}>|null $memo */
    static $memo = null;
    static $cached_filter_result = null;
    static $cache_seeded = false;

    $filter_result = apply_filters('acf_svg_icon_picker_custom_location', false);

    if ($cache_seeded && $memo !== null && $cached_filter_result === $filter_result) {
        return $memo;
    }

    $cached_filter_result = $filter_result;
    $memo = expand_locations_to_groups($filter_result);
    $cache_seeded = true;
    return $memo;
}

/**
 * Whether a custom-location filter callback is registered. The filter is
 * authoritative when set: an unset filter (default `false`) signals theme-dir
 * fallback; anything else signals "use what the filter provides, even if that
 * resolves to no icons". Mirrors the field class's check_priority_dir() gate
 * so picker UI and public helpers agree on which path is active.
 *
 * @internal
 */
function is_custom_location_filter_active(): bool {
    return apply_filters('acf_svg_icon_picker_custom_location', false) !== false;
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
    if (is_custom_location_filter_active()) {
        $groups = get_resolved_groups();
        if ($groups === []) {
            return '';
        }
        $resolved = resolve_in_groups($groups, $icon_name);
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
    if (is_custom_location_filter_active()) {
        $groups = get_resolved_groups();
        if ($groups === []) {
            return '';
        }
        $resolved = resolve_in_groups($groups, $icon_name);
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
 * Resolve a saved value (composite `groupkey.slug` or bare slug) against the
 * resolved-groups list.
 *
 * Composite saves are strict: the prefix must match exactly one group's key.
 * If it doesn't (group renamed, location dropped, etc.) we return null rather
 * than substitute a same-slug icon from another group — the picker treats
 * that as the missing-asset state.
 *
 * Bare slugs are legacy data (pre-composite, or a flat-mode sibling field)
 * and still scan all groups first-match-wins.
 *
 * @internal
 * @param list<array{key: string, name: string, path: string, url: string}> $groups
 * @return array{path: string, url: string}|null
 */
function resolve_in_groups(array $groups, string $icon_name): ?array {
    if (str_contains($icon_name, '.')) {
        [$group_key, $slug] = explode('.', $icon_name, 2);
        foreach ($groups as $group) {
            if ($group['key'] !== $group_key) {
                continue;
            }
            $candidate = "{$group['path']}/{$slug}.svg";
            if (file_exists($candidate)) {
                return [
                    'path' => $candidate,
                    'url' => $group['url'] . rawurlencode($slug) . '.svg',
                ];
            }
            return null;
        }
        return null;
    }

    foreach ($groups as $group) {
        $candidate = "{$group['path']}/{$icon_name}.svg";
        if (file_exists($candidate)) {
            return [
                'path' => $candidate,
                'url' => $group['url'] . rawurlencode($icon_name) . '.svg',
            ];
        }
    }

    return null;
}

/**
 * Walk a directory and turn its `.svg` files into the picker's slug-keyed dict.
 *
 * Path may be supplied with or without a trailing slash; URL is assumed to be
 * trailing-slashed by the caller. Per-request memoisation keeps multi-field
 * admin pages from re-scanning the same folder once per field constructor.
 *
 * @internal
 * @param  string $path The path to scan for SVG files.
 * @param  string $url The URL prefix for the icons (trailing-slashed).
 * @return array<string, array<string, mixed>>
 */
function svg_collector(string $path, string $url): array {
    /** @var array<string, array<string, array<string, mixed>>> $cache */
    static $cache = [];

    $path = rtrim($path, '/\\');
    $cache_key = "{$path}|{$url}";
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    if (!is_dir($path)) {
        $cache[$cache_key] = [];
        return [];
    }

    $entries = scandir($path);
    if ($entries === false) {
        $cache[$cache_key] = [];
        return [];
    }

    $found_files = array_filter($entries, static fn($file) => pathinfo((string) $file, PATHINFO_EXTENSION) === 'svg');

    if ($found_files === []) {
        $cache[$cache_key] = [];
        return [];
    }

    $svg_files = [];
    foreach ($found_files as $file) {
        $name = explode('.', $file)[0];
        $legacy_key = str_replace(['-', '_'], ' ', $name);
        $title = ucwords($legacy_key);
        $key = sanitize_key($name);

        // Skip files whose name doesn't survive sanitize_key intact. The saved
        // value is the sanitised slug, but the helpers reconstruct the on-disk
        // filename as `{slug}.svg` — so listing `My Icon.svg` or `café.svg`
        // would let an editor pick an icon that get_svg_icon_path() then 404s.
        // Better to hide them from the picker entirely than to ship a value
        // that resolves in the admin tile but breaks at render time.
        if ($key === '' || $key !== $name) {
            continue;
        }

        // rawurlencode handles edge-case characters that survive sanitize_key
        // (e.g. dashes/underscores already fine, but esc_url alone doesn't add
        // %-encoding for valid-ish chars). The on-disk path stays literal so
        // file_exists() / file_get_contents() still resolve against the real file.
        $svg_files[$key] = [
            'key' => $key,
            'legacy_key' => $legacy_key,
            'title' => $title,
            'url' => esc_url($url . rawurlencode($file)),
            'path' => "{$path}/{$file}",
        ];
    }

    $cache[$cache_key] = $svg_files;
    return $svg_files;
}

/**
 * Scan the active theme dirs (parent + child) for SVG icons. Used as the
 * fallback when no `acf_svg_icon_picker_custom_location` filter is set.
 *
 * Child-theme entries overwrite parent entries on slug collision because
 * `array_merge` is keyed by slug and the child run goes second.
 *
 * @internal
 * @return array<string, array<string, mixed>>
 */
function check_theme_dirs(string $path_suffix): array {
    $parent_path = get_template_directory() . '/' . $path_suffix;
    $child_path = get_stylesheet_directory() . '/' . $path_suffix;
    $parent_url = get_template_directory_uri() . '/' . $path_suffix;
    $child_url = get_stylesheet_directory_uri() . '/' . $path_suffix;

    $svgs = svg_collector($parent_path, $parent_url);

    if ($parent_path !== $child_path) {
        return array_merge($svgs, svg_collector($child_path, $child_url));
    }

    return $svgs;
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

/**
 * Resolve a saved value to a structured array — slug, url, path, title, and
 * group context where applicable. Returns null when the icon cannot be
 * resolved (file deleted, group renamed, etc.) so a caller can short-circuit
 * with a single `if ($icon = get_svg_icon_data(...))` check.
 *
 * The SVG markup is intentionally omitted: it requires a disk read and would
 * make this function unsafe to use on long lists. Call `get_svg_icon($slug)`
 * yourself when you actually need the markup, or use `return_format = 'icon'`.
 *
 * @api
 * @since 5.0.0
 * @param string $icon_name The saved value (composite `groupkey.slug` or bare slug).
 * @return array{slug: string, url: string, path: string, title: string, group_key: ?string, group_name: ?string}|null
 */
function get_svg_icon_data(string $icon_name): ?array {
    $path = get_svg_icon_path($icon_name);
    if ($path === '') {
        return null;
    }

    $url = get_svg_icon_uri($icon_name);
    $slug_part = str_contains($icon_name, '.') ? explode('.', $icon_name, 2)[1] : $icon_name;
    $title = ucwords(str_replace(['-', '_'], ' ', $slug_part));

    $group_key = null;
    $group_name = null;
    if (str_contains($icon_name, '.')) {
        $prefix = explode('.', $icon_name, 2)[0];
        foreach (get_resolved_groups() as $group) {
            if ($group['key'] === $prefix) {
                $group_key = $group['key'];
                $group_name = $group['name'] !== '' ? $group['name'] : null;
                break;
            }
        }
    }

    return [
        'slug' => $icon_name,
        'url' => $url,
        'path' => $path,
        'title' => $title,
        'group_key' => $group_key,
        'group_name' => $group_name,
    ];
}
