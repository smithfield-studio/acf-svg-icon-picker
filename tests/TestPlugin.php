<?php

/**
 * Class SampleTest
 *
 * @package Acf_Icon_Picker
 */

/**
 * Sample test case.
 */
class TestPlugin extends \WP_UnitTestCase {
    /**
     * The full path to the main plugin file.
     *
     * @type string $plugin_file
     */
    protected $plugin_file;

    /**
     * Reset every filter the test suite touches so each test starts from a
     * clean slate. Avoids order-dependent flakes when one test's filter
     * registration leaks into another (anonymous-closure remove_filter()
     * calls can't reliably remove their own callbacks).
     */
    public function tearDown(): void {
        remove_all_filters('acf_svg_icon_picker_custom_location');
        remove_all_filters('acf_svg_icon_picker_folder');
        parent::tearDown();
    }

    /**
     * Test if the plugin is loaded.
     */
    public function test_plugin_class() {
        $this->assertTrue(class_exists(\SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker::class));
    }

    /**
     * Test if field type is active.
     */
    public function test_is_field_type_active() {
        $field_types = acf_get_field_types();
        $this->assertArrayHasKey('svg_icon_picker', $field_types);
    }

    /**
     * Test if the plugin collects the SVG files from the parent theme.
     */
    public function test_found_files_in_parent_theme() {
        switch_theme('test-theme');

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
        $svgs = $plugin->svgs;
        $this->assertNotEmpty($svgs);
        $count = count($svgs);
        $this->assertEquals(5, $count);
    }

    /**
     * Test if the plugin collects the SVG files from both the parent and child theme.
     * The parent theme has 4 SVG files, and the child theme has 2 SVG files but one of them is the same as the parent theme.
     * Thus the child theme icon should be used totalling to 5 icons.
     */
    public function test_found_files_in_child_theme() {
        switch_theme('test-child-theme');

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
        $svgs = $plugin->svgs;
        $this->assertNotEmpty($svgs);
        $count = count($svgs);
        $this->assertEquals(6, $count);
    }

    /**
     * Test if the plugin collects the SVG files from the parent theme and the child theme and if the correct paths are used.
     */
    public function test_found_files_override() {
        switch_theme('test-child-theme');

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
        $svgs = $plugin->svgs;

        // exists in both child and parent theme, thus child theme should be used
        $discord = $svgs['discord'];
        $this->assertEquals('http://example.org/wp-content/themes/test-child-theme/icons/discord.svg', $discord['url']);

        // exists only in parent theme, should be used
        $facebook = $svgs['linkedin'];
        $this->assertEquals('http://example.org/wp-content/themes/test-theme/icons/linkedin.svg', $facebook['url']);
    }

    /**
     * Test if the plugin collects the SVG files from the parent theme when a custom folder is set.
     */
    public function test_custom_theme_dirs() {
        switch_theme('test-child-theme');

        add_filter('acf_svg_icon_picker_folder', fn() => 'custom-icons/');

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
        $svgs = $plugin->svgs;

        $this->assertNotEmpty($svgs);
        $count = count($svgs);
        $this->assertEquals(4, $count);

        $chain = $svgs['chain'];
        $this->assertEquals(
            'http://example.org/wp-content/themes/test-child-theme/custom-icons/chain.svg',
            $chain['url'],
        );
    }

    public function test_get_svg_icon_uri_helper_function() {
        switch_theme('test-child-theme');
        $icon_uri = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_uri('amazon');
        $this->assertEquals('http://example.org/wp-content/themes/test-child-theme/icons/amazon.svg', $icon_uri);
    }

    public function test_get_svg_icon_path_helper_function() {
        switch_theme('test-child-theme');
        $icon_path = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_path('linkedin');
        $this->assertEquals(WP_CONTENT_DIR . '/themes/test-theme/icons/linkedin.svg', $icon_path);
    }

    public function test_get_svg_icon_uri_non_existent_icon() {
        switch_theme('test-child-theme');
        $icon_uri = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_uri('non-existent-icon');
        $this->assertEquals('', $icon_uri);
    }

    public function test_get_svg_icon_helper_function() {
        switch_theme('test-child-theme');
        $icon = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon('amazon');
        $this->assertStringContainsString('<svg', $icon);
    }

    public function test_get_svg_icon_helper_function_custom_path() {
        switch_theme('test-child-theme');
        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            'path' => WP_CONTENT_DIR . '/random-location-icons/',
            'url' => content_url() . '/random-location-icons/',
        ]);

        $icon = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon('bell');
        $this->assertStringContainsString('<svg', $icon);
    }

    /**
     * get_svg_icon_data() returns the structured array for the 'array'
     * return_format and for theme code that wants slug + url + path + title
     * + group context in one call.
     */
    public function test_get_svg_icon_data_returns_struct_for_composite() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            [
                'name' => 'Brand',
                'key' => 'brand',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/icons/',
                'url' => content_url() . '/themes/test-theme/icons/',
            ],
            [
                'name' => 'Social',
                'key' => 'social',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/custom-icons/',
                'url' => content_url() . '/themes/test-theme/custom-icons/',
            ],
        ]);

        $data = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_data('social.facebook');

        $this->assertIsArray($data);
        $this->assertSame('social.facebook', $data['slug']);
        $this->assertStringEndsWith('/test-theme/custom-icons/facebook.svg', $data['path']);
        $this->assertStringEndsWith('/test-theme/custom-icons/facebook.svg', $data['url']);
        $this->assertSame('Facebook', $data['title']);
        $this->assertSame('social', $data['group_key']);
        $this->assertSame('Social', $data['group_name']);
    }

    /**
     * Bare-slug saves keep the helpers' first-match-wins behaviour and have
     * no group context — group_key/group_name fall through to null.
     */
    public function test_get_svg_icon_data_bare_slug_has_no_group() {
        switch_theme('test-child-theme');
        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            'path' => WP_CONTENT_DIR . '/random-location-icons/',
            'url' => content_url() . '/random-location-icons/',
        ]);

        $data = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_data('bell');

        $this->assertIsArray($data);
        $this->assertSame('bell', $data['slug']);
        $this->assertSame('Bell', $data['title']);
        $this->assertNull($data['group_key']);
        $this->assertNull($data['group_name']);
    }

    /**
     * Missing icons return null so `if ($icon = get_svg_icon_data(...))` works.
     */
    public function test_get_svg_icon_data_returns_null_when_missing() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            [
                'name' => 'Brand',
                'key' => 'brand',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/icons/',
                'url' => content_url() . '/themes/test-theme/icons/',
            ],
        ]);

        // group_key prefix doesn't match any configured group
        $this->assertNull(SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_data('social.facebook'));

        // file doesn't exist within the configured group
        $this->assertNull(SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_data('brand.does-not-exist'));
    }

    /**
     * Per-field return_format 'array' resolves through ACF's get_field()
     * pipeline to the same struct as the helper.
     */
    public function test_field_return_format_array() {
        acf_add_local_field_group([
            'key' => 'group_svg_icon_picker_array',
            'title' => 'SVG Icon Picker (array format)',
            'fields' => [
                [
                    'key' => 'field_svg_icon_picker_array',
                    'label' => 'Icon',
                    'name' => 'icon',
                    'type' => 'svg_icon_picker',
                    'return_format' => 'array',
                ],
            ],
            'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'post']]],
        ]);

        $post_id = self::factory()->post->create();
        switch_theme('test-child-theme');
        update_field('icon', 'amazon', $post_id);

        $data = get_field('icon', $post_id);
        $this->assertIsArray($data);
        $this->assertSame('amazon', $data['slug']);
        $this->assertSame('Amazon', $data['title']);
        $this->assertStringContainsString('amazon.svg', $data['url']);
    }

    /**
     * Test if the _doing_it_wrong() function is called when the custom location filter is not used correctly.
     */
    public function test_custom_dir_override_wrong_filter_usage() {
        switch_theme('test-child-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => 'custom-icons/');

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
        $this->setExpectedIncorrectUsage('check_priority_dir');
    }

    /**
     * Test if the plugin collects the SVG files from a custom location.
     */
    public function test_custom_dir_override() {
        switch_theme('test-child-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            'path' => WP_CONTENT_DIR . '/random-location-icons/',
            'url' => content_url() . '/random-location-icons/',
        ]);

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
        $svgs = $plugin->svgs;

        $this->assertNotEmpty($svgs);
        $count = count($svgs);
        $this->assertEquals(1, $count);

        $bell = $svgs['bell'];
        $this->assertEquals('http://example.org/wp-content/random-location-icons/bell.svg', $bell['url']);
    }

    /**
     * Test if the plugin finds an icon using the sanitised and legacy (unsanitised) acf value
     */
    public function test_legacy_keys_still_find_icons() {
        switch_theme('test-theme');

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
        $icon_key = 'thunder-storm';
        $legacy_icon_key = 'thunder storm';

        $icon = $plugin->get_icon_data($icon_key);
        $this->assertNotEmpty($icon);

        $legacy_icon = $plugin->get_icon_data($legacy_icon_key);
        $this->assertNotEmpty($legacy_icon);
    }

    public function testACFFieldSaveAndReturnValue() {
        // create a new field group
        acf_add_local_field_group([
            'key' => 'group_svg_icon_picker',
            'title' => 'SVG Icon Picker',
            'fields' => [
                [
                    'key' => 'field_svg_icon_picker',
                    'label' => 'SVG Icon Picker',
                    'name' => 'svg_icon_picker',
                    'return_format' => 'value',
                    'type' => 'svg_icon_picker',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ],
                ],
            ],
        ]);

        // create a new post
        $post_id = $this->factory->post->create();

        // set the field value
        update_field('svg_icon_picker', 'bell', $post_id);

        // get the field value
        $field_value = get_field('svg_icon_picker', $post_id);
        $this->assertEquals('bell', $field_value);
    }

    /**
     * Multi-location filter: builds a group per location, in order, and keys
     * $svgs by composite `groupkey.slug` so cross-group slugs don't collide.
     */
    public function test_multi_location_groups_populated() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            [
                'name' => 'Brand',
                'key' => 'brand',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/icons/',
                'url' => content_url() . '/themes/test-theme/icons/',
            ],
            [
                'name' => 'Social',
                'key' => 'social',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/custom-icons/',
                'url' => content_url() . '/themes/test-theme/custom-icons/',
            ],
        ]);

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();

        $this->assertCount(2, $plugin->groups);
        $this->assertEquals('brand', $plugin->groups[0]['key']);
        $this->assertEquals('Brand', $plugin->groups[0]['name']);
        $this->assertContains('brand.discord', $plugin->groups[0]['icons']);
        $this->assertEquals('social', $plugin->groups[1]['key']);
        $this->assertContains('social.facebook', $plugin->groups[1]['icons']);
    }

    /**
     * Multi-location: $svgs is keyed by composite (`groupkey.slug`); each
     * entry still carries the bare slug under `entry['key']` for back-compat
     * lookups in get_icon_data().
     */
    public function test_multi_location_svgs_use_composite_keys() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            [
                'name' => 'Brand',
                'key' => 'brand',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/icons/',
                'url' => content_url() . '/themes/test-theme/icons/',
            ],
            [
                'name' => 'Social',
                'key' => 'social',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/custom-icons/',
                'url' => content_url() . '/themes/test-theme/custom-icons/',
            ],
        ]);

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();

        // 5 brand + 3 social = 8 total (no cross-group slug clashes here).
        $this->assertCount(8, $plugin->svgs);
        $this->assertArrayHasKey('brand.discord', $plugin->svgs);
        $this->assertArrayHasKey('social.facebook', $plugin->svgs);
        $this->assertSame('discord', $plugin->svgs['brand.discord']['key']);
    }

    /**
     * Same slug in two groups co-exists under distinct composite keys; both
     * appear in $svgs and each lands in its own group's icons[].
     */
    public function test_multi_location_same_slug_distinct_under_composite() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            [
                'name' => 'Parent',
                'key' => 'parent',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/icons/',
                'url' => content_url() . '/themes/test-theme/icons/',
            ],
            [
                'name' => 'Child',
                'key' => 'child',
                'path' => WP_CONTENT_DIR . '/themes/test-child-theme/icons/',
                'url' => content_url() . '/themes/test-child-theme/icons/',
            ],
        ]);

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();

        // Both `discord` icons are addressable via their composite keys.
        $this->assertArrayHasKey('parent.discord', $plugin->svgs);
        $this->assertArrayHasKey('child.discord', $plugin->svgs);
        $this->assertStringContainsString('/test-theme/icons/discord.svg', $plugin->svgs['parent.discord']['url']);
        $this->assertStringContainsString('/test-child-theme/icons/discord.svg', $plugin->svgs['child.discord']['url']);
        $this->assertContains('parent.discord', $plugin->groups[0]['icons']);
        $this->assertContains('child.discord', $plugin->groups[1]['icons']);
    }

    /**
     * Composite save value resolves to the matching group only — `parent.discord`
     * must not return the child theme's discord even though it also exists.
     */
    public function test_get_svg_icon_path_composite_targets_specific_group() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            [
                'name' => 'Parent',
                'key' => 'parent',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/icons/',
                'url' => content_url() . '/themes/test-theme/icons/',
            ],
            [
                'name' => 'Child',
                'key' => 'child',
                'path' => WP_CONTENT_DIR . '/themes/test-child-theme/icons/',
                'url' => content_url() . '/themes/test-child-theme/icons/',
            ],
        ]);

        $parent_path = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_path('parent.discord');
        $this->assertStringEndsWith('/test-theme/icons/discord.svg', $parent_path);

        $child_path = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_path('child.discord');
        $this->assertStringEndsWith('/test-child-theme/icons/discord.svg', $child_path);
    }

    /**
     * Bare-slug back-compat: values saved before composite keys existed (or
     * by a sibling field in flat-mode) still resolve. First match wins.
     */
    public function test_get_svg_icon_path_bare_slug_back_compat() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            [
                'name' => 'Parent',
                'key' => 'parent',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/icons/',
                'url' => content_url() . '/themes/test-theme/icons/',
            ],
            [
                'name' => 'Child',
                'key' => 'child',
                'path' => WP_CONTENT_DIR . '/themes/test-child-theme/icons/',
                'url' => content_url() . '/themes/test-child-theme/icons/',
            ],
        ]);

        $path = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_path('discord');
        $this->assertStringEndsWith('/test-theme/icons/discord.svg', $path);
    }

    /**
     * get_icon_data() resolves both composite and bare-slug forms.
     */
    public function test_get_icon_data_resolves_composite_and_bare() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            [
                'name' => 'Brand',
                'key' => 'brand',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/icons/',
                'url' => content_url() . '/themes/test-theme/icons/',
            ],
        ]);

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();

        $composite = $plugin->get_icon_data('brand.discord');
        $this->assertNotEmpty($composite);
        $this->assertSame('discord', $composite['key']);

        // Bare slug still finds the icon via the entry-key fallback.
        $bare = $plugin->get_icon_data('discord');
        $this->assertNotEmpty($bare);
        $this->assertSame('discord', $bare['key']);
    }

    /**
     * A custom-location filter is authoritative when set: an empty result is
     * surfaced as "no icons" rather than silently falling back to scanning
     * theme dirs and substituting whatever lives there.
     */
    public function test_custom_location_empty_does_not_fall_back_to_theme() {
        // The active theme has icons/ files that would populate svgs if the
        // fallback fired. The filter pointing at a missing path must override.
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            'path' => WP_CONTENT_DIR . '/this-path-does-not-exist/',
            'url' => content_url() . '/this-path-does-not-exist/',
        ]);

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();

        $this->assertSame([], $plugin->svgs);
    }

    /**
     * Locations whose dir has no SVGs are skipped from $groups entirely.
     */
    public function test_multi_location_empty_dir_skipped() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            [
                'name' => 'Brand',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/icons/',
                'url' => content_url() . '/themes/test-theme/icons/',
            ],
            [
                'name' => 'Empty',
                'path' => WP_CONTENT_DIR . '/this-path-does-not-exist/',
                'url' => content_url() . '/this-path-does-not-exist/',
            ],
        ]);

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();

        // Only the populated location should have a group.
        $this->assertCount(1, $plugin->groups);
        $this->assertEquals('Brand', $plugin->groups[0]['name']);
    }

    /**
     * Composite saves are strict: a value with a group prefix that no longer
     * matches any live group returns '' rather than silently substituting a
     * same-slug icon from a different group. Bare-slug saves (legacy data)
     * still resolve via the first-match scan — that's a separate path.
     */
    public function test_get_svg_icon_path_composite_404s_when_group_renamed() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            [
                'name' => 'Brand',
                'key' => 'brand', // saved value used 'social' before, group was renamed
                'path' => WP_CONTENT_DIR . '/themes/test-theme/icons/',
                'url' => content_url() . '/themes/test-theme/icons/',
            ],
        ]);

        // Strict: composite prefix `social` doesn't match the live `brand`
        // group, so the helper returns '' even though `discord.svg` exists in
        // brand. Editors get a missing-asset signal (rendered visually) and
        // can re-pick rather than getting a silently-wrong icon.
        $stale_composite = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_path('social.discord');
        $this->assertSame('', $stale_composite);

        // Bare slug still resolves — that's the legacy back-compat path,
        // never had a group context to violate.
        $bare = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_path('discord');
        $this->assertStringEndsWith('/test-theme/icons/discord.svg', $bare);
    }

    /**
     * Two locations with explicit `key => 'social'` get the second one
     * suffixed with `-2` rather than silently merging into the first group's
     * composite namespace (which would drop colliding slugs).
     */
    public function test_multi_location_collision_disambiguates_group_key() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            [
                'name' => 'Social Brand',
                'key' => 'social',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/icons/',
                'url' => content_url() . '/themes/test-theme/icons/',
            ],
            [
                'name' => 'Social UI',
                'key' => 'social', // collides on purpose
                'path' => WP_CONTENT_DIR . '/themes/test-theme/custom-icons/',
                'url' => content_url() . '/themes/test-theme/custom-icons/',
            ],
        ]);

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();

        $this->assertCount(2, $plugin->groups);
        $this->assertSame('social', $plugin->groups[0]['key']);
        $this->assertSame('social-2', $plugin->groups[1]['key']);
        $this->assertContains('social.discord', $plugin->groups[0]['icons']);
        $this->assertContains('social-2.facebook', $plugin->groups[1]['icons']);
        $this->assertArrayHasKey('social.discord', $plugin->svgs);
        $this->assertArrayHasKey('social-2.facebook', $plugin->svgs);
    }

    /**
     * Helpers must reproduce the same group-key disambiguation the picker
     * applies — otherwise a saved value like `social-2.facebook` renders fine
     * in the editor but 404s from get_svg_icon_path()/get_svg_icon_uri() and
     * the WPGraphQL resolver. Same fixture as the picker collision test.
     */
    public function test_get_svg_icon_path_resolves_disambiguated_group() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            [
                'name' => 'Social Brand',
                'key' => 'social',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/icons/',
                'url' => content_url() . '/themes/test-theme/icons/',
            ],
            [
                'name' => 'Social UI',
                'key' => 'social',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/custom-icons/',
                'url' => content_url() . '/themes/test-theme/custom-icons/',
            ],
        ]);

        // Original key on second location resolves through the disambiguated form.
        $path = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_path('social-2.facebook');
        $this->assertStringEndsWith('/test-theme/custom-icons/facebook.svg', $path);

        $uri = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_uri('social-2.facebook');
        $this->assertStringEndsWith('/test-theme/custom-icons/facebook.svg', $uri);

        // First group still resolves under its original key.
        $first = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_path('social.discord');
        $this->assertStringEndsWith('/test-theme/icons/discord.svg', $first);
    }

    /**
     * Single-location filter result leaves $groups empty so the picker UI
     * renders flat (back-compat with the original single-dir behaviour).
     */
    public function test_single_location_leaves_groups_empty() {
        switch_theme('test-child-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            'path' => WP_CONTENT_DIR . '/random-location-icons/',
            'url' => content_url() . '/random-location-icons/',
        ]);

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();

        $this->assertEmpty($plugin->groups);
        $this->assertNotEmpty($plugin->svgs);
    }

    /**
     * get_svg_icon_path() iterates locations to find an icon.
     */
    public function test_get_svg_icon_path_finds_in_second_location() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            [
                'name' => 'Brand',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/icons/',
                'url' => content_url() . '/themes/test-theme/icons/',
            ],
            [
                'name' => 'Social',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/custom-icons/',
                'url' => content_url() . '/themes/test-theme/custom-icons/',
            ],
        ]);

        // `facebook` only exists in the second location.
        $path = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_path('facebook');
        $this->assertStringEndsWith('/test-theme/custom-icons/facebook.svg', $path);
    }

    /**
     * get_svg_icon_uri() returns the URL of the matching location, not the
     * theme dir (regression: pre-multi-location it ignored custom locations).
     */
    public function test_get_svg_icon_uri_returns_matching_location_url() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            [
                'name' => 'Social',
                'path' => WP_CONTENT_DIR . '/themes/test-theme/custom-icons/',
                'url' => content_url() . '/themes/test-theme/custom-icons/',
            ],
        ]);

        $uri = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_uri('facebook');
        $this->assertEquals('http://example.org/wp-content/themes/test-theme/custom-icons/facebook.svg', $uri);
    }

    /**
     * Single location with group_by_subdir: each top-level subdir of the path
     * becomes its own group. Suits projects that already organise icons into
     * folders and want grouping without restructuring.
     */
    public function test_group_by_subdir_creates_group_per_subdir() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            'path' => WP_CONTENT_DIR . '/themes/test-theme/',
            'url' => content_url() . '/themes/test-theme/',
            'group_by_subdir' => true,
        ]);

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();

        // `icons/` (5 svgs) + `custom-icons/` (3 svgs) become two groups; the
        // theme files (functions.php, style.css) are skipped (not directories).
        $this->assertGreaterThanOrEqual(2, count($plugin->groups));
        $names = array_column($plugin->groups, 'name');
        $this->assertContains('Icons', $names);
        $this->assertContains('Custom Icons', $names);
    }

    /**
     * get_svg_icon_path() looks inside subdirs when group_by_subdir is set.
     */
    public function test_get_svg_icon_path_finds_in_subdir() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', fn() => [
            'path' => WP_CONTENT_DIR . '/themes/test-theme/',
            'url' => content_url() . '/themes/test-theme/',
            'group_by_subdir' => true,
        ]);

        $path = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_path('facebook');
        $this->assertStringEndsWith('/test-theme/custom-icons/facebook.svg', $path);
    }

    /**
     * Per-field allowed_groups: stored as an array of group keys.
     */
    public function test_field_setting_allowed_groups_saves() {
        acf_add_local_field_group([
            'key' => 'group_svg_icon_picker_allowed',
            'title' => 'SVG Icon Picker (allowed groups)',
            'fields' => [
                [
                    'key' => 'field_svg_icon_picker_allowed',
                    'label' => 'Icon',
                    'name' => 'icon',
                    'type' => 'svg_icon_picker',
                    'allowed_groups' => ['nucleo', 'social'],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ],
                ],
            ],
        ]);

        $field = acf_get_field('field_svg_icon_picker_allowed');

        $this->assertIsArray($field['allowed_groups']);
        $this->assertSame(['nucleo', 'social'], $field['allowed_groups']);
    }

    public function testACFFieldSaveAndReturnSVG() {
        switch_theme('test-theme');
        // create a new field group
        acf_add_local_field_group([
            'key' => 'group_svg_icon_picker_2',
            'title' => 'SVG Icon Picker',
            'fields' => [
                [
                    'key' => 'field_svg_icon_picker_svg_test',
                    'label' => 'SVG Icon Picker',
                    'name' => 'svg_icon_picker',
                    'return_format' => 'icon',
                    'type' => 'svg_icon_picker',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ],
                ],
            ],
        ]);

        // create a new post
        $post_id = $this->factory->post->create();

        // set the field value
        update_field('field_svg_icon_picker_svg_test', 'discord', $post_id);

        // get file in /assets/themes/test-theme/icons/discord.svg
        $discord_svg = file_get_contents(WP_CONTENT_DIR . '/themes/test-theme/icons/discord.svg');

        $field_value = get_field('field_svg_icon_picker_svg_test', $post_id);
        $this->assertEquals($discord_svg, $field_value);
    }
}
