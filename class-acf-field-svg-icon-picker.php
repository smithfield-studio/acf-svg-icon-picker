<?php

/**
 * Field class for the SVG Icon Picker field.
 *
 * @package Advanced Custom Fields: SVG Icon Picker
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
	 * Stores the path suffix to the icons
	 *
	 * @var string
	 */
	private string $path_suffix;

	/**
	 * Stores the path to the icons
	 *
	 * @var string
	 */
	private string $path;

	/**
	 * Stores the url to the icons
	 *
	 * @var string
	 */
	private string $url;

	/**
	 * Stores the icons
	 *
	 * @var array
	 */
	private array $svgs = array();


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
		$this->defaults    = array('initial_value' => '');
		$this->l10n        = array('error' => __('Error!', 'acf-svg-icon-picker'));
		$this->path_suffix = apply_filters('acf_icon_path_suffix', 'resources/icons/');

		//$this->path        = apply_filters('acf_icon_path', ACF_SVG_ICON_PICKER_PATH) . $this->path_suffix;
		$this->url         = get_stylesheet_directory_uri();

		$priority_dir_settings = apply_filters('acf_svg_icon_picker_custom_location', false);

		if (is_array($priority_dir_settings)) {
			// see if path and url are set, if not throw an error
			if (! isset($priority_dir_settings['path']) || ! isset($priority_dir_settings['url'])) {
				wp_die(__('The path and url for the custom icon location must be set in the acf_svg_icon_picker_custom_location filter.', 'acf-svg-icon-picker'));
			}

			$this->path = $priority_dir_settings['path'];
			$this->url  = $priority_dir_settings['url'];

			$this->svgs = $this->svg_collector($this->path);
			return;
		}

		$parent_theme_path = get_template_directory() . '/' . $this->path_suffix;
		$child_theme_path  = get_stylesheet_directory() . '/' . $this->path_suffix;

		if ($parent_theme_path !== $child_theme_path) {
			$parent_svgs = $this->svg_collector($parent_theme_path, true);
			$child_svgs  = $this->svg_collector($child_theme_path);

			$this->svgs = array_merge($parent_svgs, $child_svgs);
		} else {
			$this->svgs = $this->svg_collector($parent_theme_path);
		}


		parent::__construct();
	}

	/**
	 * Collects the icons from the specified path.
	 * @var string $path The path to the icons to scan for SVG files.
	 * @return array
	 */
	private function svg_collector(string $path, mixed $uri_location = false): array
	{
		$svg_files = array();
		if (! is_dir($path)) {
			return array();
		}

		if (true === $uri_location) {
			$url = get_template_directory_uri() . '/' . $this->path_suffix;
		} else {
			$url = $this->url . '/' . $this->path_suffix;
		}

		$found_files = array_filter(scandir($path), function ($file) {
			return pathinfo($file, PATHINFO_EXTENSION) === 'svg';
		});

		if (empty($found_files)) {
			return array();
		}

		foreach ($found_files as $key => $file) {
			$name     = explode('.', $file)[0];
			$filename = pathinfo($file, PATHINFO_FILENAME);
			$name = str_replace(['-', '_'], ' ', $name);

			$svg_files[$name] = array(
				'name'     => $name,
				'filename' => $filename,
				'icon'     => $file,
				'url'      => $url . $file,
			);
		}

		return $svg_files;
	}

	/**
	 * Method that renders the field in the admin.
	 *
	 * @param array $field The field array.
	 * @return void
	 */
	public function render_field($field)
	{

		$input_icon = '' !== $field['value'] ? $field['value'] : $field['initial_value'];
		$svg        = locate_template($this->path_suffix . $input_icon . '.svg');
		$svg_exists = file_exists($svg);
		$svg_url    = esc_url($this->url . $input_icon . '.svg');

?>
		<div class="acf-svg-icon-picker">
			<div class="acf-svg-icon-picker__selector">
				<div class="acf-svg-icon-picker__icon">
					<?php echo $svg_exists ? '<img src="' . esc_url($svg_url) . '" alt=""/>' : '<span>&plus;</span>'; ?>
				</div>
				<input type="hidden" readonly name="<?php echo esc_attr($field['name']); ?>" value="<?php echo esc_attr($input_icon); ?>" />
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
	 *
	 * @return void
	 */
	public function input_admin_enqueue_scripts()
	{
		$url = ACF_SVG_ICON_PICKER_URL;
		wp_register_script('acf-input-svg-icon-picker', "{$url}assets/js/input.js", array('acf-input'), ACF_SVG_ICON_PICKER_VERSION, true);
		wp_enqueue_script('acf-input-svg-icon-picker');

		wp_localize_script(
			'acf-input-svg-icon-picker',
			'acfSvgIconPicker',
			array(
				'path'      => $this->url,
				'svgs'      => $this->svgs,
				'columns'   => 4,
				'msgs'      => array(
					'title'     => esc_html__('Select an icon', 'acf-svg-icon-picker'),
					'close'     => esc_html__('close', 'acf-svg-icon-picker'),
					'filter'     => esc_html__('Start typing to filter icons', 'acf-svg-icon-picker'),
					/* translators: %s: path_suffix */
					'no_icons'  => sprintf(esc_html__('To add icons, add your svg files in the /%s folder in your theme.', 'acf-svg-icon-picker'), $this->path_suffix),
				),
			)
		);

		wp_register_style('acf-input-svg-icon-picker', "{$url}assets/css/input.css", array('acf-input'), ACF_SVG_ICON_PICKER_VERSION);
		wp_enqueue_style('acf-input-svg-icon-picker');
	}
}
