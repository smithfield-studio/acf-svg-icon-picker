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
     * Plugin version. Used for asset cache-busting on registered scripts/styles.
     */
    public const VERSION = '5.0.0';

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
    private readonly string $path_suffix;

    /**
     * Stores the icons.
     *
     * @var array<string, array<string, mixed>> $svgs The icons, stored as an array.
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
        $path_suffix = apply_filters('acf_svg_icon_picker_folder', 'icons/');
        $this->path_suffix = is_string($path_suffix) ? $path_suffix : 'icons/';

        // Custom location takes precedence; fall back to scanning the active
        // theme dirs (parent + child).
        $svgs = $this->check_priority_dir();
        $this->svgs = $svgs === [] ? $this->check_theme_dirs() : $svgs;

        parent::__construct();
    }

    /**
     * Build $svgs (and $groups, when applicable) from the
     * `acf_svg_icon_picker_custom_location` filter result.
     *
     * Single `{path, url}` → flat, slug-keyed $svgs (back-compat).
     * List of locations → composite-keyed (`groupkey.slug`) $svgs + $groups.
     * Single location with `group_by_subdir => true` → composite-keyed,
     *   one group per top-level subdir.
     *
     * @return array<string, array<string, mixed>>
     */
    private function check_priority_dir(): array {
        $filter_result = apply_filters('acf_svg_icon_picker_custom_location', false);

        if ($filter_result === false) {
            return [];
        }

        // Group rendering is opt-in per the filter shape: a single { path, url }
        // renders flat (BC); a list of locations renders with group headings,
        // even if only one location ends up populated. A single location with
        // 'group_by_subdir' => true also opts into group rendering, with one
        // group per top-level subdirectory of `path`.
        $is_list_grouped = is_array($filter_result) && array_is_list($filter_result);
        $locations = $this->normalize_locations($filter_result);

        if ($locations === []) {
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
        /** @var list<array{key: string, name: string, icons: list<string>}> $groups */
        $groups = [];
        /** @var list<string> $used_group_keys */
        $used_group_keys = [];
        $has_subdir_mode = false;

        foreach ($locations as $i => $location) {
            if (!empty($location['group_by_subdir'])) {
                $has_subdir_mode = true;
                $this->collect_subdir_groups($location, $svgs, $groups, $used_group_keys);
                continue;
            }

            $found = $this->svg_collector($location['path'], $location['url']);

            if ($found === []) {
                continue;
            }

            $group_name = $location['name'] ?? '';
            // Run the key through sanitize_title so user-supplied keys can't
            // break HTML id/data attributes downstream. Then disambiguate
            // collisions with `-2`, `-3`, … rather than letting two locations
            // with the same slugified key silently merge into one group.
            $raw_key = $location['key'] ?? $group_name;
            $base_key = sanitize_title($raw_key) ?: "group-{$i}";
            $group_key = $this->disambiguate_group_key($base_key, $used_group_keys);
            $used_group_keys[] = $group_key;

            // Composite keys (`groupkey.slug`) only when we're actually
            // rendering groups — single `{path, url}` stays bare-keyed for
            // back-compat with values saved by older versions.
            $icon_keys = [];
            foreach ($found as $bare_key => $entry) {
                $key = $is_list_grouped ? "{$group_key}.{$bare_key}" : $bare_key;
                if (isset($svgs[$key])) {
                    continue;
                }
                $svgs[$key] = $entry;
                $icon_keys[] = $key;
            }

            $groups[] = [
                'key' => $group_key,
                'name' => $group_name,
                'icons' => $icon_keys,
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
            $found = $this->svg_collector("{$base_path}/{$subdir}", "{$base_url}{$subdir}/");

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
     * Append `-2`, `-3`, … to a group key until it doesn't collide with any
     * already-used key. Empty input is treated as already disambiguated by
     * the caller (which supplies a `group-{$i}` fallback).
     *
     * @param list<string> $used_group_keys
     */
    private function disambiguate_group_key(string $base_key, array $used_group_keys): string {
        if (!in_array($base_key, $used_group_keys, true)) {
            return $base_key;
        }
        $n = 2;
        while (in_array("{$base_key}-{$n}", $used_group_keys, true)) {
            $n++;
        }
        return "{$base_key}-{$n}";
    }

    /**
     * Thin wrapper over the shared {@see normalize_custom_locations()} helper
     * so the field class and the public API helpers (`get_svg_icon_path()`,
     * `get_svg_icon_uri()`) share one definition of the filter contract.
     *
     * @param  mixed $filter_result Raw value returned by the filter.
     * @return list<array{path: string, url: string, name?: string, key?: string, group_by_subdir?: bool}>
     */
    private function normalize_locations(mixed $filter_result): array {
        return normalize_custom_locations($filter_result);
    }

    /**
     * Method that checks the theme directories for icons.
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
     * Method that renders the field in the admin.
     *
     * @param array<string, mixed> $field the field array
     */
    public function render_field($field): void {
        // Narrow the raw mixed-typed field value to a string up front so the
        // view's docblock can honestly declare `string` and skip defensive
        // is_string() checks on render-side data.
        $raw = $field['value'] !== '' ? $field['value'] : $field['initial_value'];
        $saved_value = is_string($raw) ? $raw : '';
        $icon = $saved_value !== '' ? $this->get_icon_data($saved_value) : null;

        // A non-empty saved value that doesn't resolve to icon data means the
        // asset is gone (file deleted, group renamed, etc.). The view renders
        // a distinct warning state so editors can spot stale data — fields
        // that look identical to "no icon picked yet" are silent footguns.
        $is_missing = $saved_value !== '' && empty($icon);

        $allowed_groups = isset($field['allowed_groups']) && is_array($field['allowed_groups'])
            ? array_values(array_filter($field['allowed_groups'], is_string(...)))
            : [];

        $this->render_view('acf-field', [
            'field' => $field,
            'saved_value' => $saved_value,
            'icon' => $icon,
            'is_missing' => $is_missing,
            'allowed_groups' => $allowed_groups,
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

        // Only render the group filter when groups are actually configured.
        if (count($this->groups) > 0) {
            $choices = [];
            foreach ($this->groups as $group) {
                $choices[$group['key']] = $group['name'] !== '' ? $group['name'] : $group['key'];
            }

            acf_render_field_setting($field, [
                'label' => __('Allowed Groups', 'acf-svg-icon-picker'),
                'instructions' => __(
                    'Limit the picker to these groups for this field. Leave empty to show all.',
                    'acf-svg-icon-picker',
                ),
                'type' => 'checkbox',
                'name' => 'allowed_groups',
                'choices' => $choices,
                'allow_null' => 1,
                'layout' => 'horizontal',
            ]);
        }
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
        if ($field['return_format'] === 'icon' && is_string($value) && $value !== '') {
            return get_svg_icon($value);
        }

        return $value;
    }

    /**
     * Enqueue assets for the field.
     */
    public function input_admin_enqueue_scripts(): void {
        $url = plugin_dir_url(__FILE__);
        wp_register_script(
            'acf-input-svg-icon-picker',
            "{$url}resources/scripts/input.js",
            ['acf-input'],
            self::VERSION,
            true,
        );
        wp_enqueue_script('acf-input-svg-icon-picker');

        // Static markup (dialog shell, i18n strings) lives in the template
        // printed by render_dialog_template(). Only the dynamic data — the
        // icon dictionary, group list, and the empty-state message (rendered
        // client-side when svgs is empty) — needs to ride in the script var.
        // Empty-state debug hint depends on which path resolved (or didn't):
        // a custom-location filter pointing nowhere should send the user to
        // their filter callback, not at the default theme `icons/` folder.
        $has_custom_location = apply_filters('acf_svg_icon_picker_custom_location', false) !== false;

        $no_icons_msg = $has_custom_location
            ? __(
                'No icons found. Check the paths returned by your <code>acf_svg_icon_picker_custom_location</code> filter.',
                'acf-svg-icon-picker',
            )
            : sprintf(
                // translators: %s: theme folder path (default 'icons/')
                __(
                    'To add icons, add your SVG files in the <code>/%s</code> folder in your theme.',
                    'acf-svg-icon-picker',
                ),
                esc_attr($this->path_suffix),
            );

        $data = [
            'svgs' => $this->svgs,
            'groups' => $this->groups,
            'noIconsMsg' => $no_icons_msg,
        ];

        wp_add_inline_script(
            'acf-input-svg-icon-picker',
            'var acfSvgIconPicker = ' . wp_json_encode($data) . ';',
            'before',
        );

        wp_register_style(
            'acf-input-svg-icon-picker',
            "{$url}resources/styles/input.css",
            ['acf-input'],
            self::VERSION,
        );
        wp_enqueue_style('acf-input-svg-icon-picker');

        // add_action dedupes by callback identity, and a static guard inside
        // render_dialog_template() ensures the markup is only emitted once
        // even though input_admin_enqueue_scripts() runs per page-with-fields.
        add_action('admin_footer', $this->render_dialog_template(...));
    }

    /**
     * Print the picker dialog template into the admin footer once per page.
     * JS clones template.content on open instead of building the shell via
     * innerHTML, so static markup and i18n strings live in PHP.
     */
    public function render_dialog_template(): void {
        static $printed = false;
        if ($printed) {
            return;
        }
        $printed = true;
        ?>
<template id="acfsip-dialog-template">
	<dialog class="acf-svg-icon-picker__popup" aria-labelledby="acfsip-popup-title">
		<div class="acf-svg-icon-picker__popup-header">
			<h2 id="acfsip-popup-title"><?php esc_html_e('Select an icon', 'acf-svg-icon-picker'); ?></h2>
			<label class="screen-reader-text" for="acfsip-popup-filter">
				<?php esc_html_e('Start typing to filter icons', 'acf-svg-icon-picker'); ?>
			</label>
			<input
				class="acf-svg-icon-picker__filter"
				type="search"
				id="acfsip-popup-filter"
				placeholder="<?php esc_attr_e('Start typing to filter icons', 'acf-svg-icon-picker'); ?>"
				autocomplete="off"
			/>
			<button
				type="button"
				class="acf-svg-icon-picker__popup-close"
				aria-label="<?php esc_attr_e('close', 'acf-svg-icon-picker'); ?>"
			>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path></svg>
			</button>
		</div>
		<div class="acf-svg-icon-picker__popup-contents"></div>
	</dialog>
</template>
		<?php
    }

    /**
     * Collects the icons from the specified path.
     *
     * @param  string $path The path to the icons to scan for SVG files.
     * @param  string $url The url to the icons.
     * @return array<string, array<string, mixed>>
     */
    private function svg_collector(string $path, string $url): array {
        $svg_files = [];
        if (!is_dir($path)) {
            return [];
        }

        $entries = scandir($path);
        if ($entries === false) {
            return [];
        }

        $found_files = array_filter(
            $entries,
            static fn($file) => pathinfo((string) $file, PATHINFO_EXTENSION) === 'svg',
        );

        if ($found_files === []) {
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
     * @param  string $key The icon key.
     * @return array<string, mixed>
     */
    public function get_icon_data(string $key): array {
        if (isset($this->svgs[$key])) {
            return $this->svgs[$key];
        }

        // Bare-slug back-compat: in grouped mode $svgs is composite-keyed, but
        // values saved by older versions (or by a sibling field in flat mode)
        // are bare slugs. svg_collector keeps `entry['key']` as the bare slug
        // so we can still resolve them — first match wins, mirroring the
        // pre-grouping collision behavior.
        foreach ($this->svgs as $svg) {
            if (isset($svg['key']) && $svg['key'] === $key) {
                return $svg;
            }
        }

        // Legacy_key fallback handles much older values that stored the
        // human-readable "icon name" form ("arrow down" vs "arrow-down").
        foreach ($this->svgs as $svg) {
            if (isset($svg['legacy_key']) && $svg['legacy_key'] === $key) {
                return $svg;
            }
        }

        return [];
    }

    /**
     * Render a php/html view.
     *
     * @param string               $view The view to render.
     * @param array<string, mixed> $data The data to pass to the view.
     */
    private function render_view(string $view, array $data): void {
        $path = plugin_dir_path(__FILE__) . "resources/views/{$view}.php";

        if (!file_exists($path)) {
            return;
        }

        extract($data);
        include $path;
    }
}
