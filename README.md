[![Latest Stable Version](https://img.shields.io/packagist/v/smithfield-studio/acf-svg-icon-picker.svg?style=flat-square)](https://packagist.org/packages/smithfield-studio/acf-svg-icon-picker)


# ACF SVG Icon Picker Field

Add a field type to ACF for selecting SVG icons from a popup modal. Theme developers can provide a set of SVG icons to choose from.

## Compatibility

This ACF field type is compatible with:

- [x] ACF 6
- [x] ACF 5

## Screenshots

![SVG Icon Picker Popup](/screenshots/example-popup.jpg)

## Installation

### via Composer
Run `composer require smithfield-studio/acf-svg-icon-picker` and activate the plugin via the plugins admin page.

### Manually
1. Copy the `acf-svg-icon-picker` folder into your `wp-content/plugins` folder
2. Activate the plugin
3. Create a new ACF field and select the SVG Icon Picker type

## Switch from the legacy 'ACF Icon Picker' to 'ACF SVG Icon Picker'
If you're coming from the original ACF Icon Picker plugin, you can switch to this plugin by following these steps:

1. Deactivate the old *ACF Icon Picker plugin*
2. Install the *ACF SVG Icon Picker plugin* via Composer or manually
3. Activate the *ACF SVG Icon Picker plugin*
4. Configure your desired icon path via the new [filters](#filters). Remove any old filters in use: `acf_icon_path`, `acf_icon_url` or `acf_icon_path_suffix`.
5. Go over your field configurations and change the field type from `icon-picker` to `svg_icon_picker` in the field settings. Be aware of the underscores in the field type name.
6. Check if the field type is now available in your ACF field settings

## Usage of this plugin
We recommend storing your SVG icons in a folder within your theme. This plugin defaults to looking for icons inside the `icons/` folder of your theme. You can change this path by using the [`acf_svg_icon_picker_folder` filter](#filters).

When using this plugin in conjunction with a parent/child theme, you can store your icons in the parent theme and use the child theme to override the path to the icons. This way, you can provide a set of icons in the parent theme and still allow the child theme to override them.

### Helper functions
We provide helper functions to fetch icons from the theme folder, without it mattering if the icon is stored in the parent or child theme.

```php
$my_icon_field = get_field('my_icon_field');

// Get the icon URL
$icon_url = get_svg_icon_uri($my_icon_field);

// Get the icon file system path
$icon_path = get_svg_icon_path($my_icon_field);

// Get the icon contents
$icon_svg = get_svg_icon($my_icon_field);
```

### Filters

Use the below filters to override the default icon folder inside your theme.

```php
// modify the path to the icons directory in your theme.
add_filter('acf_svg_icon_picker_folder', function () {
  return 'resources/icons/';
});
```

In case you do not want to store the icons in the theme folder, you can use the filter below to change the path to an icons directory in a custom location.
In this example, the icons are stored in the `WP_CONTENT_DIR . '/icons/'` folder.

```php
add_filter('acf_svg_icon_picker_custom_location', function () {
  return [
    'path' => WP_CONTENT_DIR . '/icons/',
    'url' =>  content_url() . '/icons/',
  ];
});
```

### [ACF Builder](https://github.com/StoutLogic/acf-builder) / [ACF Composer](https://github.com/Log1x/acf-composer)

```php
$fields->addField('my_icon', 'svg_icon_picker', [
    'label' => 'My Icon',
])
```

## Changelog
[See releases for the full changelog](https://github.com/smithfield-studio/acf-svg-icon-picker/releases)

* 4.0.1:
  * Fix version numbers in constant.
  * chore: Add files to export ignore 


* 4.0.0:
  * Remove/deprecate legacy filters, refactor and simplify icon path filters by [@Levdbas](https://github.com/Levdbas) in [#25](https://github.com/smithfield-studio/acf-svg-icon-picker/pull/25)
  * add unit tests, phpstan and return types by [@Levdbas](https://github.com/Levdbas) in [#25](https://github.com/smithfield-studio/acf-svg-icon-picker/pull/25)
  * add better support for hashed assets by [@mike-sheppard](https://github.com/mike-sheppard) in [#26](https://github.com/smithfield-studio/acf-svg-icon-picker/pull/26)
* 3.1.4: Fix filter on filenames with diacritical marks by [@Rvervuurt](https://github.com/Rvervuurt) in [#21](https://github.com/smithfield-studio/acf-svg-icon-picker/pull/21)
* 3.1.3: Added MutationObserver by [@chrisbakr](https://github.com/chrisbakr) in [#20](https://github.com/smithfield-studio/acf-svg-icon-picker/pull/20)
* 3.1.2: Add debounce to improve filter performance by [@stefanmomm](https://github.com/stefanmomm) in [#17](https://github.com/smithfield-studio/acf-svg-icon-picker/pull/17)
* 3.1.1: Optimize css by [@stefanmomm](https://github.com/stefanmomm) in [#16](https://github.com/smithfield-studio/acf-svg-icon-picker/pull/16)
* 3.1.0: Changed name of field to `svg_icon_picker` to avoid conflicts with vanilla ACF Icon Picker field.
* 3.0.0: Revert to original ACF field name, quick tidy + README updates
* 2.0.0: Fix for ACF 6.3 which now has an official icon-picker field + merged open PRs from [@Levdbas](https://github.com/Levdbas) in [#38](https://github.com/houke/acf-icon-picker/pull/38) & [@phschmanau](https://github.com/phschmanau) in [#37](https://github.com/houke/acf-icon-picker/pull/37)
---
* **Forked from [houke/acf-icon-picker](https://github.com/houke/acf-icon-picker)**
---
* 1.9.1: ACF 6 compatibility fix. Thanks to [@idflood](https://github.com/idflood) in [#30](https://github.com/houke/acf-icon-picker/pull/30)
* 1.9.0: Fix issue with Gutenberg preview not updating when removing. Thanks to [@cherbst](https://github.com/cherbst) in [#23](https://github.com/houke/acf-icon-picker/pull/23)
* 1.8.0: Fix issue with Gutenberg not saving icon. Thanks to [@tlewap](https://github.com/tlewap) in [#17](https://github.com/houke/acf-icon-picker/pull/17)
* 1.7.0: 2 new filters for more control over icon path. Thanks to [@benjibee](https://github.com/benjibee) in [#11](https://github.com/houke/acf-icon-picker/pull/11)
* 1.6.0: Performance fix with lots of icons. Thanks to [@idflood](https://github.com/idflood) in [#9](https://github.com/houke/acf-icon-picker/pull/9)
* 1.5.0: Fix issue where searching for icons would break preview if icon name has space
* 1.4.0: Add filter to change folder where svg icons are stored
* 1.3.0: Adding close option on modal
* 1.2.0: Adding search filter input to filter through icons by name
* 1.1.0: Add button to remove the selected icon when the field is not required
* 1.0.0: First release
