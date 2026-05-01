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
        remove_all_filters('acf_icon_path_suffix');
        parent::tearDown();
    }

    /**
     * Test if the plugin is loaded.
     */
    public function test_plugin_class() {
        $this->assertTrue(class_exists('SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker'));
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

        add_filter('acf_svg_icon_picker_folder', function () {
            return 'custom-icons/';
        });

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
        add_filter('acf_svg_icon_picker_custom_location', function () {
            return [
                'path' => WP_CONTENT_DIR . '/random-location-icons/',
                'url' => content_url() . '/random-location-icons/',
            ];
        });

        $icon = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon('bell');
        $this->assertStringContainsString('<svg', $icon);
    }

    /**
     * Test if the deprecated filters are correctly forwarded to the new filter.
     *
     * @expectedDeprecated acf_icon_path_suffix
     */
    public function test_deprecated_filters() {
        switch_theme('test-child-theme');

        add_filter('acf_icon_path_suffix', function () {
            return 'custom-icons/';
        });

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
        $svgs = $plugin->svgs;

        $this->assertNotEmpty($svgs);
        $count = count($svgs);
        $this->assertEquals(4, $count);

        // Filter is cleaned up by tearDown() — no need to remove it here.
    }

    /**
     * Test if the _doing_it_wrong() function is called when the custom location filter is not used correctly.
     */
    public function test_custom_dir_override_wrong_filter_usage() {
        switch_theme('test-child-theme');

        add_filter('acf_svg_icon_picker_custom_location', function () {
            return 'custom-icons/';
        });

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
        $svgs = $plugin->svgs;
        $this->setExpectedIncorrectUsage('check_priority_dir');
    }

    /**
     * Test if the plugin collects the SVG files from a custom location.
     */
    public function test_custom_dir_override() {
        switch_theme('test-child-theme');

        add_filter('acf_svg_icon_picker_custom_location', function () {
            return [
                'path' => WP_CONTENT_DIR . '/random-location-icons/',
                'url' => content_url() . '/random-location-icons/',
            ];
        });

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
     * Multi-location filter: builds a group per location, in order.
     */
    public function test_multi_location_groups_populated() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', function () {
            return [
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
            ];
        });

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();

        $this->assertCount(2, $plugin->groups);
        $this->assertEquals('brand', $plugin->groups[0]['key']);
        $this->assertEquals('Brand', $plugin->groups[0]['name']);
        $this->assertContains('discord', $plugin->groups[0]['icons']);
        $this->assertEquals('social', $plugin->groups[1]['key']);
        $this->assertContains('facebook', $plugin->groups[1]['icons']);
    }

    /**
     * Multi-location: $plugin->svgs stays a flat dict (BC for get_icon_data etc).
     */
    public function test_multi_location_flat_svgs_still_populated() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', function () {
            return [
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
            ];
        });

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();

        // 5 brand + 3 social = 8 total, no collisions across these dirs.
        $this->assertCount(8, $plugin->svgs);
        $this->assertArrayHasKey('discord', $plugin->svgs);
        $this->assertArrayHasKey('facebook', $plugin->svgs);
    }

    /**
     * Slug collisions across locations: first match wins; second occurrence
     * is not registered into either the flat dict or the second group.
     */
    public function test_multi_location_collision_first_wins() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', function () {
            return [
                [
                    'name' => 'Parent',
                    'path' => WP_CONTENT_DIR . '/themes/test-theme/icons/',
                    'url' => content_url() . '/themes/test-theme/icons/',
                ],
                [
                    'name' => 'Child',
                    'path' => WP_CONTENT_DIR . '/themes/test-child-theme/icons/',
                    'url' => content_url() . '/themes/test-child-theme/icons/',
                ],
            ];
        });

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();

        // `discord` exists in both. The first location's URL should win.
        $discord = $plugin->svgs['discord'];
        $this->assertStringContainsString('/test-theme/icons/discord.svg', $discord['url']);

        // First group contains discord; second group does not.
        $this->assertContains('discord', $plugin->groups[0]['icons']);
        $this->assertNotContains('discord', $plugin->groups[1]['icons']);
    }

    /**
     * Locations whose dir has no SVGs are skipped from $groups entirely.
     */
    public function test_multi_location_empty_dir_skipped() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', function () {
            return [
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
            ];
        });

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();

        // Only the populated location should have a group.
        $this->assertCount(1, $plugin->groups);
        $this->assertEquals('Brand', $plugin->groups[0]['name']);
    }

    /**
     * Single-location filter result leaves $groups empty so the picker UI
     * renders flat (back-compat with the original single-dir behaviour).
     */
    public function test_single_location_leaves_groups_empty() {
        switch_theme('test-child-theme');

        add_filter('acf_svg_icon_picker_custom_location', function () {
            return [
                'path' => WP_CONTENT_DIR . '/random-location-icons/',
                'url' => content_url() . '/random-location-icons/',
            ];
        });

        $plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();

        $this->assertEmpty($plugin->groups);
        $this->assertNotEmpty($plugin->svgs);
    }

    /**
     * get_svg_icon_path() iterates locations to find an icon.
     */
    public function test_get_svg_icon_path_finds_in_second_location() {
        switch_theme('test-theme');

        add_filter('acf_svg_icon_picker_custom_location', function () {
            return [
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
            ];
        });

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

        add_filter('acf_svg_icon_picker_custom_location', function () {
            return [
                [
                    'name' => 'Social',
                    'path' => WP_CONTENT_DIR . '/themes/test-theme/custom-icons/',
                    'url' => content_url() . '/themes/test-theme/custom-icons/',
                ],
            ];
        });

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

        add_filter('acf_svg_icon_picker_custom_location', function () {
            return [
                'path' => WP_CONTENT_DIR . '/themes/test-theme/',
                'url' => content_url() . '/themes/test-theme/',
                'group_by_subdir' => true,
            ];
        });

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

        add_filter('acf_svg_icon_picker_custom_location', function () {
            return [
                'path' => WP_CONTENT_DIR . '/themes/test-theme/',
                'url' => content_url() . '/themes/test-theme/',
                'group_by_subdir' => true,
            ];
        });

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
