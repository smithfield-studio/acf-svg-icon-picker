# Upgrading

## v5

Two things to be aware of:

- **PHP 8.2 minimum.** Composer install fails fast on PHP 8.1 (EOL Nov 2025).
- **Deprecated filters removed** — `acf_icon_path`, `acf_icon_url`, `acf_icon_path_suffix` (deprecated since 4.0.0). If anything in your project still listens for them, switch to `acf_svg_icon_picker_folder`.

If neither applies, upgrading is just a `composer update`.

## From the legacy `houke/acf-icon-picker` plugin

If you're still on the original ACF Icon Picker plugin (or pre-2.0 of this fork):

1. Deactivate the old plugin.
2. Install this plugin via Composer (`composer require smithfield-studio/acf-svg-icon-picker`) or manually into `wp-content/plugins`.
3. Activate the new plugin.
4. Configure your icon location via [`acf_svg_icon_picker_folder`](README.md#configuring-icon-locations) (or `acf_svg_icon_picker_custom_location` for paths outside the theme).
5. Update field configurations: change `icon-picker` to `svg_icon_picker` (note the underscores). Either edit field-group definitions in code or update the Field Type setting in the WP admin per field.
