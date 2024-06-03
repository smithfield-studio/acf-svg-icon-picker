<?php
/**
 * Field class for the SVG Icon Picker field.
 *
 * @package Advanced Custom Fields: SVG Icon Picker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ACF_Field_Svg_Icon_Picker' ) ) {

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
		public function __construct() {
			$this->name        = 'icon-picker';
			$this->label       = __( 'SVG Icon Picker', 'acf-svg-icon-picker' );
			$this->category    = 'content';
			$this->defaults    = array( 'initial_value' => '' );
			$this->l10n        = array( 'error' => __( 'Error!', 'acf-svg-icon-picker' ) );
			$this->path_suffix = apply_filters( 'acf_icon_path_suffix', 'assets/img/acf/' );
			$this->path        = apply_filters( 'acf_icon_path', acf_plugin_svg_icon_picker::$settings['path'] ) . $this->path_suffix;
			$this->url         = apply_filters( 'acf_icon_url', acf_plugin_svg_icon_picker::$settings['url'] ) . $this->path_suffix;

			$priority_dir_lookup = get_stylesheet_directory() . '/' . $this->path_suffix;

			if ( file_exists( $priority_dir_lookup ) ) {
				$this->path = $priority_dir_lookup;
				$this->url  = get_stylesheet_directory_uri() . '/' . $this->path_suffix;
			}

			$files = array_diff( scandir( $this->path ), array( '.', '..' ) );
			foreach ( $files as $file ) {
				if ( pathinfo( $file, PATHINFO_EXTENSION ) === 'svg' ) {
					$name     = explode( '.', $file )[0];
					$filename = pathinfo( $file, PATHINFO_FILENAME );
					$icon     = array(
						'name'     => $name,
						'filename' => $filename,
						'icon'     => $file,
					);
					array_push( $this->svgs, $icon );
				}
			}

			parent::__construct();
		}

		/**
		 * Method that renders the field in the admin.
		 *
		 * @param array $field The field array.
		 * @return void
		 */
		public function render_field( $field ) {
			$input_icon = '' !== $field['value'] ? $field['value'] : $field['initial_value'];
			$svg        = $this->path . $input_icon . '.svg';
			?>
			<div class="acf-svg-icon-picker">
				<div class="acf-svg-icon-picker__img">
					<?php
					if ( file_exists( $svg ) ) {
						$svg = $this->url . $input_icon . '.svg';
						echo '<div class="acf-svg-icon-picker__svg">';
						echo '<img src="' . esc_url( $svg ) . '" alt=""/>';
						echo '</div>';
					} else {
						echo '<div class="acf-svg-icon-picker__svg">';
						echo '<span class="acf-svg-icon-picker__svg--span">&plus;</span>';
						echo '</div>';
					}
					?>
					<input type="hidden" readonly name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( $input_icon ); ?>" />
				</div>
				<?php if ( false === $field['required'] ) { ?>
					<span class="acf-svg-icon-picker__remove">
						<?php esc_html_e( 'remove', 'acf-svg-icon-picker' ); ?>
					</span>
				<?php } ?>
			</div>
			<?php
		}

		/**
		 * Enqueue assets for the field.
		 *
		 * @return void
		 */
		public function input_admin_enqueue_scripts() {
			$url     = acf_plugin_svg_icon_picker::$settings['url'];
			$version = acf_plugin_svg_icon_picker::$settings['version'];

			wp_register_script( 'acf-input-svg-icon-picker', "{$url}assets/js/input.js", array( 'acf-input' ), $version, true );
			wp_enqueue_script( 'acf-input-svg-icon-picker' );

			wp_localize_script(
				'acf-input-svg-icon-picker',
				'acfSvgIconPicker',
				array(
					'path'         => $this->url,
					'svgs'         => $this->svgs,
					/* translators: %s: path_suffix */
					'no_icons_msg' => sprintf( esc_html__( 'To add icons, add your svg files in the /%s folder in your theme.', 'acf-svg-icon-picker' ), $this->path_suffix ),
				)
			);

			wp_register_style( 'acf-input-svg-icon-picker', "{$url}assets/css/input.css", array( 'acf-input' ), $version );
			wp_enqueue_style( 'acf-input-svg-icon-picker' );
		}
	}
}
