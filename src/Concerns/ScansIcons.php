<?php

/**
 * Filesystem-scanning concern for the SVG Icon Picker field.
 *
 * Splits the disk-walking logic out of the main field class. The field decides
 * *what* to load; this trait knows *how* to walk a directory and turn .svg
 * files into the picker's slug-keyed dict.
 *
 * @package Advanced Custom Fields: SVG Icon Picker
 */

namespace SmithfieldStudio\AcfSvgIconPicker\Concerns;

defined('ABSPATH') || exit();

trait ScansIcons {
    /**
     * Per-request memo of svg_collector results. Multi-field admin pages
     * (e.g. a field group editor with several SVG-icon-picker fields) would
     * otherwise re-scan the same directories on each constructor invocation.
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    private static array $collector_cache = [];

    /**
     * Method that checks the theme directories for icons. Falls back to scanning
     * the active theme dirs (parent + child) when no custom location is set.
     *
     * @return array<string, array<string, mixed>>
     */
    private function check_theme_dirs(): array {
        $parent_theme_path = get_template_directory() . '/' . $this->path_suffix;
        $child_theme_path = get_stylesheet_directory() . '/' . $this->path_suffix;
        $parent_theme_url = get_template_directory_uri() . '/' . $this->path_suffix;
        $child_theme_url = get_stylesheet_directory_uri() . '/' . $this->path_suffix;

        $svgs = $this->svg_collector($parent_theme_path, $parent_theme_url);

        if ($parent_theme_path !== $child_theme_path) {
            // array_merge dedupes by slug because $svgs is slug-keyed and the
            // child entries (run second) overwrite parent entries on collision.
            return array_merge($svgs, $this->svg_collector($child_theme_path, $child_theme_url));
        }

        return $svgs;
    }

    /**
     * Walk the immediate subdirectories of a location's path and add one group
     * per subdir that contains SVGs. Subdir name (Title-Cased) becomes the
     * group name; sanitised slug becomes the group key.
     *
     * @param array{path: string, url: string, name?: string, key?: string, group_by_subdir?: bool} $location        Location config.
     * @param array<string, array<string, mixed>>                                                   $svgs            Reference: composite-keyed svgs dict (`subdirslug.iconslug`).
     * @param list<array{key: string, name: string, icons: list<string>}>                           $groups          Reference: groups list to append into.
     * @param list<string>                                                                          $used_group_keys Reference: tracked group keys for collision disambiguation.
     */
    private function collect_subdir_groups(
        array $location,
        array &$svgs,
        array &$groups,
        array &$used_group_keys,
    ): void {
        $base_path = rtrim($location['path'], '/\\');
        $base_url = trailingslashit($location['url']);

        if (!is_dir($base_path)) {
            return;
        }

        $scan = scandir($base_path);
        $entries = $scan === false
            ? []
            : array_filter(
                $scan,
                static fn($entry) => $entry !== '.' && $entry !== '..' && is_dir("{$base_path}/{$entry}"),
            );

        foreach ($entries as $subdir) {
            // rawurlencode the subdir for the URL only — filesystem path keeps
            // the literal name. Subdirs like "Brand Icons" otherwise leak a
            // raw space into the URL.
            $found = $this->svg_collector("{$base_path}/{$subdir}", "{$base_url}" . rawurlencode($subdir) . '/');

            if ($found === []) {
                continue;
            }

            // Disambiguate folder slugs that collide after sanitize_title
            // (e.g. "Brand Icons" and "brand-icons" both → "brand-icons").
            // Without this, the second folder would silently overwrite the
            // first via the composite-key path.
            $base_key = sanitize_title($subdir);
            $group_key = $this->disambiguate_group_key($base_key, $used_group_keys);
            $used_group_keys[] = $group_key;

            $composite_keys = [];
            foreach ($found as $bare_key => $entry) {
                $composite = "{$group_key}.{$bare_key}";
                $svgs[$composite] = $entry;
                $composite_keys[] = $composite;
            }

            $groups[] = [
                'key' => $group_key,
                'name' => ucwords(str_replace(['-', '_'], ' ', $subdir)),
                'icons' => $composite_keys,
            ];
        }
    }

    /**
     * Collects the icons from the specified path.
     *
     * @param  string $path The path to the icons to scan for SVG files.
     * @param  string $url The url to the icons.
     * @return array<string, array<string, mixed>>
     */
    private function svg_collector(string $path, string $url): array {
        $cache_key = "{$path}|{$url}";
        if (isset(self::$collector_cache[$cache_key])) {
            return self::$collector_cache[$cache_key];
        }

        $svg_files = [];
        if (!is_dir($path)) {
            self::$collector_cache[$cache_key] = [];
            return [];
        }

        $entries = scandir($path);
        if ($entries === false) {
            self::$collector_cache[$cache_key] = [];
            return [];
        }

        $found_files = array_filter(
            $entries,
            static fn($file) => pathinfo((string) $file, PATHINFO_EXTENSION) === 'svg',
        );

        if ($found_files === []) {
            self::$collector_cache[$cache_key] = [];
            return [];
        }

        foreach ($found_files as $key => $file) {
            $name = explode('.', $file)[0];
            $legacy_key = str_replace(['-', '_'], ' ', $name);
            $title = ucwords($legacy_key);
            $key = sanitize_key($name);

            // rawurlencode handles spaces/capitals/diacritics in the filename
            // (esc_url alone doesn't add %-encoding for valid-ish chars). The
            // on-disk path stays literal so file_exists() / file_get_contents()
            // still resolve against the real file.
            $svg_files[$key] = [
                'key' => $key,
                'legacy_key' => $legacy_key,
                'title' => $title,
                'url' => esc_url($url . rawurlencode($file)),
                'path' => "{$path}/{$file}",
            ];
        }

        self::$collector_cache[$cache_key] = $svg_files;
        return $svg_files;
    }
}
