<?php

/**
 * Field class for the SVG Icon Picker field.
 *
 * @package Advanced Custom Fields: SVG Icon Picker
 */

namespace SmithfieldStudio\AcfSvgIconPicker;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Field class for the SVG Icon Picker field.
 */
class ACF_Field_Svg_Icon_Picker extends \acf_field {
    /**
     * Controls field type visibility in REST requests.
     *
     * @var bool
     */
    public $show_in_rest = true;

    /**
     * Stores the path suffix to the icons.
     *
     * @var string $path_suffix The path suffix to the icons.
     */
    private string $path_suffix;

    /**
     * Stores the icons.
     *
     * @var array $svgs The icons, stored as an array.
     */
    public array $svgs = [];

    /**
     * Optional grouping metadata for the picker UI. Populated when the
     * acf_svg_icon_picker_custom_location filter returns a list of locations.
     * Each entry: [ 'key' => string, 'name' => string, 'icons' => string[] ].
     *
     * @var array<int, array{key: string, name: string, icons: array<int, string>}>
     */
    public array $groups = [];

    /**
     * Constructor.
     *
     * We set the field name, label, category, defaults and l10n.
     */
    public function __construct() {
        $this->name = 'svg_icon_picker';
        $this->label = __('SVG Icon Picker', 'acf-svg-icon-picker');
        $this->category = 'content';
        $this->defaults = [
            'initial_value' => '',
            'return_format' => 'value',
        ];
        $this->l10n = ['error' => __('Error!', 'acf-svg-icon-picker')];
        $this->path_suffix = apply_filters('acf_svg_icon_picker_folder', 'icons/');
        $this->path_suffix = apply_filters_deprecated(
            'acf_icon_path_suffix',
            [$this->path_suffix],
            '4.0.0',
            'acf_svg_icon_picker_folder',
        );

        apply_filters_deprecated(
            'acf_icon_path',
            [''],
            '4.0.0',
            '',
            'acf_icon_path filter is no longer in use, please check the docs of ACF SVG Icon Picker Field',
        ) . $this->path_suffix;
        apply_filters_deprecated(
            'acf_icon_url',
            [''],
            '4.0.0',
            '',
            'acf_icon_url filter is no longer in use, please check the docs of ACF SVG Icon Picker Field',
        ) . $this->path_suffix;

        /**
         * Check if the custom icon location is set by filter and if not, check the theme directories for icons.
         */
        $svgs = $this->check_priority_dir();
        $this->svgs = empty($svgs) ? $this->check_theme_dirs() : $svgs;

        parent::__construct();
    }

    /**
     * Method that checks if the custom icon location is set by filter.
     *
     * The filter may return either a single location ([ 'path' => …, 'url' => … ])
     * or a list of locations to merge ([ [ 'path' => …, 'url' => …, 'name' => … ], … ]).
     * When multiple locations are provided the picker UI groups them by `name`.
     *
     * @return array
     */
    private function check_priority_dir(): array {
        $filter_result = apply_filters('acf_svg_icon_picker_custom_location', false);

        if (false === $filter_result) {
            return [];
        }

        // Group rendering is opt-in per the filter shape: a single { path, url }
        // renders flat (BC); a list of locations renders with group headings,
        // even if only one location ends up populated. A single location with
        // 'group_by_subdir' => true also opts into group rendering, with one
        // group per top-level subdirectory of `path`.
        $is_list_grouped = is_array($filter_result) && array_is_list($filter_result);
        $locations = $this->normalize_locations($filter_result);

        if (empty($locations)) {
            _doing_it_wrong(
                __FUNCTION__,
                esc_attr__(
                    'The acf_svg_icon_picker_custom_location filter should return an array with path and url, or a list of such arrays.',
                    'acf-svg-icon-picker',
                ),
                '4.0.0',
            );
            return [];
        }

        $svgs = [];
        $groups = [];
        $has_subdir_mode = false;

        foreach ($locations as $i => $location) {
            if (!empty($location['group_by_subdir'])) {
                $has_subdir_mode = true;
                $this->collect_subdir_groups($location, $svgs, $groups);
                continue;
            }

            $found = $this->svg_collector($location['path'], $location['url']);

            if (empty($found)) {
                continue;
            }

            // First-match wins on slug collisions across locations.
            $new_keys = [];
            foreach ($found as $key => $entry) {
                if (isset($svgs[$key])) {
                    continue;
                }

                $svgs[$key] = $entry;
                $new_keys[] = $key;
            }

            if (empty($new_keys)) {
                continue;
            }

            $group_name = $location['name'] ?? '';
            $group_key = $location['key'] ?? ('' !== $group_name ? sanitize_title($group_name) : "group-{$i}");

            $groups[] = [
                'key' => $group_key,
                'name' => $group_name,
                'icons' => $new_keys,
            ];
        }

        if ($is_list_grouped || $has_subdir_mode) {
            $this->groups = $groups;
        }

        return $svgs;
    }

    /**
     * Walk the immediate subdirectories of a location's path and add one group
     * per subdir that contains SVGs. Subdir name (Title-Cased) becomes the
     * group name; sanitised slug becomes the group key.
     *
     * @param array         $location Location config with `path` + `url` (and
     *                                optionally `name`/`key` — currently unused
     *                                in subdir mode).
     * @param array<string> $svgs     Reference: flat svgs dict (slug-keyed).
     * @param array<int>    $groups   Reference: groups list to append into.
     */
    private function collect_subdir_groups(array $location, array &$svgs, array &$groups): void {
        $base_path = rtrim($location['path'], '/\\');
        $base_url = trailingslashit($location['url']);

        if (!is_dir($base_path)) {
            return;
        }

        $scan = scandir($base_path);
        $entries = false === $scan
            ? []
            : array_filter(
                $scan,
                static fn($entry) => '.' !== $entry && '..' !== $entry && is_dir("{$base_path}/{$entry}"),
            );

        foreach ($entries as $subdir) {
            $found = $this->svg_collector("{$base_path}/{$subdir}", "{$base_url}{$subdir}/");

            if (empty($found)) {
                continue;
            }

            $new_keys = [];
            foreach ($found as $key => $entry) {
                if (isset($svgs[$key])) {
                    continue;
                }

                $svgs[$key] = $entry;
                $new_keys[] = $key;
            }

            if (empty($new_keys)) {
                continue;
            }

            $groups[] = [
                'key' => sanitize_title($subdir),
                'name' => ucwords(str_replace(['-', '_'], ' ', $subdir)),
                'icons' => $new_keys,
            ];
        }
    }

    /**
     * Normalize the acf_svg_icon_picker_custom_location filter result into a
     * list of locations. Accepts either a single { path, url } associative
     * array or a list of them. Invalid entries are dropped.
     *
     * @param mixed $filter_result Raw value returned by the filter.
     * @return array<int, array<string, mixed>>
     */
    private function normalize_locations(mixed $filter_result): array {
        if (!is_array($filter_result) || empty($filter_result)) {
            return [];
        }

        // Single location (associative with path + url).
        if (isset($filter_result['path'], $filter_result['url'])) {
            return [$filter_result];
        }

        // List of locations.
        if (array_is_list($filter_result)) {
            $valid = [];
            foreach ($filter_result as $location) {
                if (!(is_array($location) && isset($location['path'], $location['url']))) {
                    continue;
                }

                $valid[] = $location;
            }
            return $valid;
        }

        return [];
    }

    /**
     * Method that checks the theme directories for icons.
     *
     * @return array
     */
    private function check_theme_dirs(): array {
        $parent_theme_path = get_template_directory() . '/' . $this->path_suffix;
        $child_theme_path = get_stylesheet_directory() . '/' . $this->path_suffix;
        $parent_theme_url = get_template_directory_uri() . '/' . $this->path_suffix;
        $child_theme_url = get_stylesheet_directory_uri() . '/' . $this->path_suffix;

        $svgs = $this->svg_collector($parent_theme_path, $parent_theme_url);

        if ($parent_theme_path !== $child_theme_path) {
            $child_svgs = $this->svg_collector($child_theme_path, $child_theme_url);
            $svgs = array_merge($svgs, $child_svgs);
            $svgs = array_unique($svgs, SORT_REGULAR);
        }

        return $svgs;
    }

    /**
     * Method that renders the field in the admin.
     *
     * @param array $field the field array
     */
    public function render_field($field): void {
        $saved_value = '' !== $field['value'] ? $field['value'] : $field['initial_value'];
        $icon = !empty($saved_value) ? $this->get_icon_data($saved_value) : null;

        $this->render_view('acf-field', [
            'field' => $field,
            'saved_value' => $saved_value,
            'icon' => $icon,
        ]);
    }

    /**
     * Render_field_settings()
     *
     * @param array<string, mixed> $field An array holding all the field's data.
     */
    public function render_field_settings($field): void {
        acf_render_field_setting($field, [
            'label' => __('Return Format', 'acf-svg-icon-picker'),
            'instructions' => '',
            'type' => 'radio',
            'name' => 'return_format',
            'layout' => 'horizontal',
            'choices' => [
                'value' => __('Value', 'acf-svg-icon-picker'),
                'icon' => __('Icon', 'acf-svg-icon-picker'),
            ],
        ]);
    }

    /**
     * This filter is applied to the $value after it is loaded from the db and before it is returned to the template.
     *
     * @param mixed                $value          current value.
     * @param mixed                $post_id        The post id.
     * @param array<string, mixed> $field          The field array.
     * @return mixed                    $value we return.
     */
    public function format_value(mixed $value, mixed $post_id, $field) {
        if ('icon' === $field['return_format'] && !empty($value)) {
            return get_svg_icon($value);
        }

        return $value;
    }

    /**
     * Enqueue assets for the field.
     */
    public function input_admin_enqueue_scripts(): void {
        // @phpstan-ignore constant.notFound
        $url = ACF_SVG_ICON_PICKER_URL;
        wp_register_script(
            'acf-input-svg-icon-picker',
            "{$url}resources/scripts/input.js",
            ['acf-input'],
            ACF_SVG_ICON_PICKER_VERSION,
            true,
        );
        wp_enqueue_script('acf-input-svg-icon-picker');

        wp_localize_script('acf-input-svg-icon-picker', 'acfSvgIconPicker', [
            'svgs' => $this->svgs,
            'groups' => $this->groups,
            'columns' => 4,
            'msgs' => [
                'title' => esc_html__('Select an icon', 'acf-svg-icon-picker'),
                'close' => esc_html__('close', 'acf-svg-icon-picker'),
                'filter' => esc_html__('Start typing to filter icons', 'acf-svg-icon-picker'),
                // translators: %s: path_suffix
                'no_icons' => sprintf(
                    __(
                        'To add icons, add your svg files in the <code>/%s</code> folder in your theme.',
                        'acf-svg-icon-picker',
                    ),
                    esc_attr($this->path_suffix),
                ),
            ],
        ]);

        wp_register_style(
            'acf-input-svg-icon-picker',
            "{$url}resources/styles/input.css",
            ['acf-input'],
            ACF_SVG_ICON_PICKER_VERSION,
        );
        wp_enqueue_style('acf-input-svg-icon-picker');
    }

    /**
     * Collects the icons from the specified path.
     *
     * @param string $path The path to the icons to scan for SVG files.
     * @param string $url The url to the icons.
     */
    private function svg_collector(string $path, string $url): array {
        $svg_files = [];
        if (!is_dir($path)) {
            return [];
        }

        $found_files = array_filter(scandir($path), static function ($file) {
            return 'svg' === pathinfo($file, PATHINFO_EXTENSION);
        });

        if (empty($found_files)) {
            return [];
        }

        foreach ($found_files as $key => $file) {
            $name = explode('.', $file)[0];
            $legacy_key = str_replace(['-', '_'], ' ', $name);
            $title = ucwords($legacy_key);
            $key = sanitize_key($name);

            $svg_files[$key] = [
                'key' => $key,
                'legacy_key' => $legacy_key,
                'title' => $title,
                'url' => esc_url("{$url}{$file}"),
                'path' => "{$path}/{$file}",
            ];
        }

        return $svg_files;
    }

    /**
     * Get the icon data.
     *
     * @param string $key The icon key.
     */
    public function get_icon_data(string $key): array {
        $icon = !empty($this->svgs[$key]) ? $this->svgs[$key] : [];

        // if no icon found in array keys, check legacy_key field
        if (empty($icon)) {
            $icon = array_filter($this->svgs, static function ($svg) use ($key) {
                return $svg['legacy_key'] === $key;
            });

            if (empty($icon)) {
                return [];
            }

            $icon = reset($icon);
        }

        return $icon;
    }

    /**
     * Render a php/html view.
     *
     * @param string $view The view to render.
     * @param array  $data The data to pass to the view.
     */
    private function render_view(string $view, array $data) {
        // @phpstan-ignore constant.notFound
        $plugin_path = ACF_SVG_ICON_PICKER_PATH;
        $path = "{$plugin_path}resources/views/{$view}.php";

        if (!file_exists($path)) {
            return;
        }

        extract($data);
        include $path;
    }
}
