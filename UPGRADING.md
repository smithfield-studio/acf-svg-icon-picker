# Upgrading

## v5

Every breaking change in [CHANGELOG.md](CHANGELOG.md#500), with what to do about it:

- **PHP 8.2 minimum.** Composer install fails fast on PHP 8.1 (EOL Nov 2025).
- **Deprecated filters removed:** `acf_icon_path`, `acf_icon_url`, `acf_icon_path_suffix` (deprecated since 4.0.0). If anything in your project still listens for them, switch to `acf_svg_icon_picker_folder`.
- **Global constants removed:** `ACF_SVG_ICON_PICKER_VERSION`, `ACF_SVG_ICON_PICKER_URL`, `ACF_SVG_ICON_PICKER_PATH`. Use `\SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker::VERSION` for the version; code that used the URL or path constants needs its own.
- **DOM classes renamed.** Update custom admin CSS that targets the picker:
  - `.acf-svg-icon-picker__popup-overlay` → `.acf-svg-icon-picker__popup::backdrop`
  - `.acf-svg-icon-picker__popup ul li[data-svg]` → `.acf-svg-icon-picker__option`
- **Browser baseline** for the picker in the WP admin: Chrome 111+, Firefox 113+, Safari 16.2+. The front end is unaffected.
- **Composite values.** Once a site moves to a list of locations or `group_by_subdir`, the field saves `groupkey.slug` (e.g. `brand.discord`) instead of a bare slug. `get_field()` plus the helper functions handles both forms. Custom code that maps a saved value to a file itself needs to handle `groupkey.slug`.
- **Custom-location filter is authoritative when it returns a value.** If `acf_svg_icon_picker_custom_location` returns a location that holds no icons (wrong path, empty folder), the picker shows "no icons" instead of falling back to the theme's `icons/` folder. Check the paths your filter returns. Returning `false`, `null`, `''` or `[]` still falls back to the theme folder.
- **`allowed_groups` is enforced in the editor and on the front end.** A saved value from a group outside a field's `allowed_groups` shows as missing in the editor, and `get_field()` returns a missing icon for it (`''`, or `null` for the `array` return format). Re-pick those values, or add the group to the field's allowlist.
- **Icon values are validated.** The helper functions resolve only a bare slug (`a-z`, `0-9`, `_`, `-`) or `groupkey.slug`, and return `''` / `null` for anything else. If theme code passes subfolder paths (`get_svg_icon('brand/logo')`) or uppercase names to the helpers, use a list of locations or `group_by_subdir` for the subfolders and lowercase the file names. Saving any other value through the field stores `''`, except the legacy `arrow down` form, which is saved as its slug.

If none of these apply, upgrading is just a `composer update`.

## From the legacy `houke/acf-icon-picker` plugin

If you're still on the original ACF Icon Picker plugin (or pre-2.0 of this fork):

1. Deactivate the old plugin.
2. Install this plugin via Composer (`composer require smithfield-studio/acf-svg-icon-picker`) or manually into `wp-content/plugins`.
3. Activate the new plugin.
4. Configure your icon location via [`acf_svg_icon_picker_folder`](README.md#configuring-icon-locations) (or `acf_svg_icon_picker_custom_location` for paths outside the theme).
5. Update field configurations: change `icon-picker` to `svg_icon_picker` (note the underscores). Either edit field-group definitions in code or update the Field Type setting in the WP admin per field.
