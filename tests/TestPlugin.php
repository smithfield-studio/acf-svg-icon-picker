<?php

/**
 * Class SampleTest
 *
 * @package Acf_Icon_Picker
 */

/**
 * Sample test case.
 */
class TestPlugin extends \WP_UnitTestCase
{
	/**
	 * The full path to the main plugin file.
	 *
	 * @type string $plugin_file
	 */
	protected $plugin_file;

	/**
	 * Test if the plugin is loaded.
	 */
	public function test_plugin_class()
	{
		$this->assertTrue(class_exists('SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker'));
	}

	/**
	 * Test if the plugin is loaded.
	 */
	public function test_is_field_type_active()
	{
		$field_types = acf_get_field_types();
		$this->assertArrayHasKey('svg_icon_picker', $field_types);
	}

	/**
	 * Test if the plugin is loaded.
	 */
	public function test_found_files_in_parent_theme()
	{
		switch_theme('test-theme');

		$plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
		$svgs = $plugin->svgs;
		$this->assertNotEmpty($svgs);
		$count = count($svgs);
		$this->assertEquals(4, $count);
	}

	public function test_found_files_in_child_theme()
	{
		switch_theme('test-child-theme');

		$plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
		$svgs = $plugin->svgs;
		$this->assertNotEmpty($svgs);
		$count = count($svgs);
		$this->assertEquals(5, $count);
	}

	public function test_found_files_override()
	{
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

	public function test_custom_theme_dirs()
	{
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
		$this->assertEquals('http://example.org/wp-content/themes/test-child-theme/custom-icons/chain.svg', $chain['url']);
	}



	public function test_custom_dir_override_wrong_filter_usage()
	{
		switch_theme('test-child-theme');

		add_filter('acf_svg_icon_picker_custom_location', function () {
			return 'custom-icons/';
		});

		$plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
		$svgs = $plugin->svgs;
		$this->setExpectedIncorrectUsage('check_priority_dir');
	}

	public function test_custom_dir_override()
	{
		switch_theme('test-child-theme');

		add_filter('acf_svg_icon_picker_custom_location', function () {
			return [
				'path' => WP_CONTENT_DIR . '/random-location-icons/',
				'url' =>  content_url() . '/random-location-icons/',
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
}
