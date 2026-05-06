<?php

/**
 * Field class for the SVG Icon Picker field.
 *
 * @package Advanced Custom Fields: SVG Icon Picker
 */

namespace SmithfieldStudio\AcfSvgIconPicker;

defined('ABSPATH') || exit();

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

        // A custom location filter is authoritative when set: the picker uses
        // it even when it resolves to no icons, so misconfigured paths surface
        // as "no icons" rather than silently falling back to theme dirs and
        // hiding the bug. We only fall back when the filter is unset.
        $svgs = $this->check_priority_dir();
        $this->svgs = $svgs === null ? check_theme_dirs($this->path_suffix) : $svgs;

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
     * Group disambiguation lives in `expand_locations_to_groups()` (helpers.php)
     * so the public API helpers resolve the same `groupkey.slug` shape.
     *
     * Returns `null` when no custom-location filter is set (signal to the
     * caller to fall back to theme dirs). Returns an empty array when a
     * filter is set but resolves to no icons — that's an authoritative
     * "no icons" verdict, not a fallback trigger.
     *
     * @return array<string, array<string, mixed>>|null
     */
    private function check_priority_dir(): ?array {
        $filter_result = apply_filters('acf_svg_icon_picker_custom_location', false);

        if ($filter_result === false) {
            return null;
        }

        // Wrong-usage signal: filter returned a shape we can't interpret
        // (string, non-list array missing path/url, etc.). A *valid* filter
        // that resolves to no concrete folders (e.g. group_by_subdir on a
        // path with no subdirs, or all-empty list entries) is a legitimate
        // "no icons" state — caller surfaces that via the no-icons message,
        // no notice fired.
        if (normalize_custom_locations($filter_result) === []) {
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

        // Group rendering is opt-in per the filter shape: a single { path, url }
        // renders flat (BC); a list of locations renders with group headings,
        // even if only one location ends up populated. A single location with
        // 'group_by_subdir' => true also opts into group rendering, with one
        // group per top-level subdirectory of `path`.
        $is_grouped =
            is_array($filter_result) && (array_is_list($filter_result) || !empty($filter_result['group_by_subdir']));

        $resolved_groups = get_resolved_groups();

        $svgs = [];
        /** @var list<array{key: string, name: string, icons: list<string>}> $groups */
        $groups = [];

        foreach ($resolved_groups as $group) {
            $found = svg_collector($group['path'], $group['url']);

            if ($found === []) {
                continue;
            }

            $icon_keys = [];
            foreach ($found as $bare_key => $entry) {
                $key = $is_grouped ? "{$group['key']}.{$bare_key}" : $bare_key;
                if (isset($svgs[$key])) {
                    continue;
                }
                $svgs[$key] = $entry;
                $icon_keys[] = $key;
            }

            $groups[] = [
                'key' => $group['key'],
                'name' => $group['name'],
                'icons' => $icon_keys,
            ];
        }

        if ($is_grouped) {
            $this->groups = $groups;
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
        // view's docblock can declare `string` and skip defensive is_string()
        // checks on render-side data.
        $raw = $field['value'] !== '' ? $field['value'] : $field['initial_value'];
        $saved_value = is_string($raw) ? $raw : '';
        $icon = $saved_value !== '' ? $this->get_icon_data($saved_value) : null;

        // A non-empty saved value that doesn't resolve to icon data means the
        // asset is gone (file deleted, group renamed, etc.). The view renders
        // a distinct warning state so editors can spot stale data instead of
        // mistaking it for an unset field.
        $is_missing = $saved_value !== '' && empty($icon);

        $allowed_groups = isset($field['allowed_groups']) && is_array($field['allowed_groups'])
            ? array_values(array_filter($field['allowed_groups'], is_string(...)))
            : [];

        // Enforce allowed_groups on render: a saved value whose source group
        // isn't in this field's allowlist renders as missing-asset, surfacing
        // the inconsistency for the editor to re-pick. The picker UI restricts
        // *new* picks; this catches values written outside the picker
        // (imports, REST, copied post meta, legacy data).
        //
        // Mirror the picker's fail-open: if the allowlist is fully stale (no
        // matches in live groups), skip enforcement entirely so the field
        // still shows its data. Save-time canonicalisation in update_value()
        // uses the same rule, so the two paths agree.
        if (!$is_missing && is_array($icon) && $icon !== [] && $allowed_groups !== [] && $this->groups !== []) {
            $live_keys = array_column($this->groups, 'key');
            if (array_intersect($allowed_groups, $live_keys) !== []) {
                $group_key = $this->resolve_group_key($saved_value, $icon);
                if ($group_key !== null && !in_array($group_key, $allowed_groups, true)) {
                    $is_missing = true;
                    $icon = null;
                }
            }
        }

        $this->render_view('acf-field', [
            'field' => $field,
            'saved_value' => $saved_value,
            'icon' => $icon,
            'is_missing' => $is_missing,
            'allowed_groups' => $allowed_groups,
        ]);
    }

    /**
     * Identify which configured group a saved value's resolved icon came from.
     * Returns null when no group context is derivable (no groups configured,
     * or a bare slug whose entry isn't tracked under any composite key).
     *
     * Composite values carry their group key as the prefix; bare values walk
     * the group list looking for the matching `groupkey.bareslug` composite.
     *
     * @param array<string, mixed> $icon Icon data as returned by get_icon_data().
     */
    private function resolve_group_key(string $saved_value, array $icon): ?string {
        if (str_contains($saved_value, '.')) {
            return explode('.', $saved_value, 2)[0];
        }
        $bare = $icon['key'] ?? '';
        if (!is_string($bare) || $bare === '') {
            return null;
        }
        foreach ($this->groups as $group) {
            if (in_array("{$group['key']}.{$bare}", $group['icons'], true)) {
                return $group['key'];
            }
        }
        return null;
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
                'array' => __('Array', 'acf-svg-icon-picker'),
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
        if (!is_string($value) || $value === '') {
            return $value;
        }

        if ($field['return_format'] === 'icon') {
            return get_svg_icon($value);
        }

        if ($field['return_format'] === 'array') {
            return get_svg_icon_data($value);
        }

        return $value;
    }

    /**
     * Canonicalise legacy bare-slug saves to their composite form when the
     * site is now in grouped mode and exactly one configured group claims
     * the slug. Lets old data drain to the new format gradually as fields
     * are re-saved, without a one-shot migration.
     *
     * No-ops when:
     *   - the value isn't a string or is empty,
     *   - the value is already composite (contains a '.'),
     *   - no groups are configured (flat mode),
     *   - or the slug is ambiguous across multiple groups (we'd need an
     *     editor decision and silently picking one is the wrong default).
     *
     * Composite values pass through unchanged even when they violate the
     * field's `allowed_groups`. Enforcement happens at render time
     * (render_field() marks disallowed groups as missing-asset) so editors
     * see and can re-pick stale or imported data instead of having it
     * silently dropped on save.
     *
     * @param  mixed                $value   The value sent for this field.
     * @param  mixed                $post_id The post id.
     * @param  array<string, mixed> $field   The field array.
     */
    public function update_value(mixed $value, mixed $post_id, $field): mixed {
        if (!is_string($value) || $value === '' || str_contains($value, '.')) {
            return $value;
        }

        if ($this->groups === []) {
            return $value;
        }

        // When the field declares an allowlist that's still active (at least
        // one of its keys matches a live group), restrict canonicalisation
        // candidates to allowed groups only. Stale allowlists fall through to
        // the unrestricted scan — same fail-open semantic the picker UI uses.
        $allowed = isset($field['allowed_groups']) && is_array($field['allowed_groups'])
            ? array_values(array_filter($field['allowed_groups'], is_string(...)))
            : [];
        $live_keys = array_column($this->groups, 'key');
        $allowlist_active = $allowed !== [] && array_intersect($allowed, $live_keys) !== [];

        $matches = [];
        foreach ($this->groups as $group) {
            if ($allowlist_active && !in_array($group['key'], $allowed, true)) {
                continue;
            }
            foreach ($group['icons'] as $composite) {
                if (str_ends_with($composite, '.' . $value)) {
                    $matches[] = $composite;
                    break;
                }
            }
        }

        return count($matches) === 1 ? $matches[0] : $value;
    }

    /**
     * Enqueue assets for the field.
     */
    public function input_admin_enqueue_scripts(): void {
        $url = plugin_dir_url(PLUGIN_FILE);
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
            // Trigger aria-label strings kept in sync with resources/views/acf-field.php.
            // JS uses these after pick/remove so the accessible name reflects the
            // current value instead of the initial server-rendered state.
            'chooseIconLabel' => __('Choose icon', 'acf-svg-icon-picker'),
            'selectedIconLabel' => __('Selected icon: %s. Click to change.', 'acf-svg-icon-picker'),
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
				aria-label="<?php esc_attr_e('Close', 'acf-svg-icon-picker'); ?>"
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
        $path = plugin_dir_path(PLUGIN_FILE) . "resources/views/{$view}.php";

        if (!file_exists($path)) {
            return;
        }

        extract($data);
        include $path;
    }
}
