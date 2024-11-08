<?php

/**
 * Field class for the SVG Icon Picker field.
 */

namespace SmithfieldStudio\AcfSvgIconPicker;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Field class for the SVG Icon Picker field.
 */
class ACF_Field_Svg_Icon_Picker extends \acf_field
{


	/**
	 * Controls field type visibility in REST requests.
	 *
	 * @var bool
	 */
	public $show_in_rest = true;

	/**
	 * Stores the path suffix to the icons.
	 */
	private string $path_suffix;

	/**
	 * Stores the path to the icons.
	 */
	private string $path;

	/**
	 * Stores the url to the icons.
	 */
	private string $url;

	/**
	 * Stores the icons.
	 */
	public array $svgs = [];

	/**
	 * Constructor.
	 *
	 * We set the field name, label, category, defaults and l10n.
	 */
	public function __construct()
	{
		$this->name        = 'svg_icon_picker';
		$this->label       = __('SVG Icon Picker', 'acf-svg-icon-picker');
		$this->category    = 'content';
		$this->defaults    = ['initial_value' => ''];
		$this->l10n        = ['error' => __('Error!', 'acf-svg-icon-picker')];
		$this->url         = get_stylesheet_directory_uri();
		$this->path_suffix = apply_filters('acf_svg_icon_picker_folder', 'icons/');
		$this->path_suffix = apply_filters_deprecated('acf_icon_path_suffix', [$this->path_suffix], '4.0.0', 'acf_svg_icon_picker_folder');


		apply_filters_deprecated('acf_icon_path', [''], '4.0.0', '', 'acf_icon_path filter is no longer in use, please check the docs of ACF SVG Icon Picker Field') . $this->path_suffix;
		apply_filters_deprecated('acf_icon_url', [''], '4.0.0', '', 'acf_icon_url filter is no longer in use, please check the docs of ACF SVG Icon Picker Field') . $this->path_suffix;

		/**
		 * Check if the custom icon location is set by filter and if not, check the theme directories for icons.
		 */
		$svgs       = $this->check_priority_dir();
		$this->svgs = empty($svgs) ? $this->check_theme_dirs() : $svgs;

		parent::__construct();
	}

	/**
	 * Method that checks if the custom icon location is set by filter.
	 * If it is, it sets the path and url to the custom location and collects the icons in that specific location.
	 *
	 * @throws \Exception if the path or url for the custom icon location are not set
	 * @return array
	 */
	private function check_priority_dir(): array
	{
		$priority_dir_settings = apply_filters('acf_svg_icon_picker_custom_location', false);

		if (false === $priority_dir_settings) {
			return [];
		}

		if (! is_array($priority_dir_settings) || ! isset($priority_dir_settings['path']) || ! isset($priority_dir_settings['url'])) {
			_doing_it_wrong(__FUNCTION__, __('The acf_svg_icon_picker_custom_location filter should contain an array with a path and url.', 'acf-svg-icon-picker'), '1.0.0');
			return [];
		}

		$this->path = $priority_dir_settings['path'];
		$this->url  = $priority_dir_settings['url'];

		return $this->svg_collector($this->path, $this->url);
	}

	/**
	 * Method that checks the theme directories for icons.
	 *
	 * @return array
	 */
	private function check_theme_dirs(): array
	{

		$parent_theme_path = get_template_directory() . '/' . $this->path_suffix;
		$child_theme_path  = get_stylesheet_directory() . '/' . $this->path_suffix;
		$parent_theme_url = get_template_directory_uri() . '/' . $this->path_suffix;
		$child_theme_url  = get_stylesheet_directory_uri() . '/' . $this->path_suffix;

		$svgs = $this->svg_collector($parent_theme_path, $parent_theme_url);

		if ($parent_theme_path !== $child_theme_path) {
			$child_svgs = $this->svg_collector($child_theme_path, $child_theme_url);
			$svgs       = array_merge($svgs, $child_svgs);
			$svgs       = array_unique($svgs, SORT_REGULAR);
		}

		return $svgs;
	}

	/**
	 * Method that renders the field in the admin.
	 *
	 * @param array $field the field array
	 */
	public function render_field($field)
	{
		$input_icon = '' !== $field['value'] ? $field['value'] : $field['initial_value'];
		$icon_data  = [];
		$button_ui  = '<span>&plus;</span>';

		if (!empty($input_icon)) {
			$icon_data  = !empty($this->svgs[$input_icon]) ? $this->svgs[$input_icon] : null;
			$svg_exists = file_exists($icon_data['path']);
			$button_ui = $svg_exists ? "<img src='{$icon_data['url']}' alt=''/>" : '<span>&plus;</span>';
		}
?>
		<div class="acf-svg-icon-picker">
			<div class="acf-svg-icon-picker__selector">
				<div class="acf-svg-icon-picker__icon">
					<?php echo $button_ui; ?>
				</div>
				<input type="hidden" readonly
					name="<?php echo esc_attr($field['name']); ?>"
					value="<?php echo esc_attr($input_icon); ?>" />
			</div>
			<?php if (! $field['required']) { ?>
				<button class="acf-svg-icon-picker__remove">
					<?php esc_html_e('Remove', 'acf-svg-icon-picker'); ?>
				</button>
			<?php } ?>
		</div>
<?php
	}

	/**
	 * Enqueue assets for the field.
	 */
	public function input_admin_enqueue_scripts()
	{
		$url = ACF_SVG_ICON_PICKER_URL;
		wp_register_script('acf-input-svg-icon-picker', "{$url}assets/js/input.js", ['acf-input'], ACF_SVG_ICON_PICKER_VERSION, true);
		wp_enqueue_script('acf-input-svg-icon-picker');

		wp_localize_script(
			'acf-input-svg-icon-picker',
			'acfSvgIconPicker',
			[
				'svgs'    => $this->svgs,
				'columns' => 4,
				'msgs'    => [
					'title'    => esc_html__('Select an icon', 'acf-svg-icon-picker'),
					'close'    => esc_html__('close', 'acf-svg-icon-picker'),
					'filter'   => esc_html__('Start typing to filter icons', 'acf-svg-icon-picker'),
					// translators: %s: path_suffix
					'no_icons' => sprintf(esc_html__('To add icons, add your svg files in the /%s folder in your theme.', 'acf-svg-icon-picker'), $this->path_suffix),
				],
			]
		);

		wp_register_style('acf-input-svg-icon-picker', "{$url}assets/css/input.css", ['acf-input'], ACF_SVG_ICON_PICKER_VERSION);
		wp_enqueue_style('acf-input-svg-icon-picker');
	}

	/**
	 * Collects the icons from the specified path.
	 *
	 * @param string $path The path to the icons to scan for SVG files.
	 * @param string $url The url to the icons.
	 */
	private function svg_collector(string $path, string $url): array
	{
		$svg_files = [];
		if (! is_dir($path)) {
			return [];
		}

		$found_files = array_filter(
			scandir($path),
			function ($file) {
				return 'svg' === pathinfo($file, PATHINFO_EXTENSION);
			}
		);

		if (empty($found_files)) {
			return [];
		}

		foreach ($found_files as $key => $file) {
			$name	= explode('.', $file)[0];
			$title	= ucwords(str_replace(['-', '_'], ' ', $name));
			$key	= sanitize_key($name);

			$svg_files[$key] = [
				'key'	=> $key,
				'title'	=> $title,
				'url'	=> esc_url("{$url}{$file}"),
				'path'	=> "{$path}/{$file}",
			];
		}

		return $svg_files;
	}
}
?>
