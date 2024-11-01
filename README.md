[![Latest Stable Version](https://img.shields.io/packagist/v/smithfield-studio/acf-svg-icon-picker.svg?style=flat-square)](https://packagist.org/packages/smithfield-studio/acf-svg-icon-picker)


# ACF SVG Icon Picker Field

Add an ACF field to your theme that lets users easily select SVG icons from a specified folder. The field returns the SVG's name.

## Compatibility

This ACF field type is compatible with:

- [x] ACF 6
- [x] ACF 5

## Screenshots

![SVG Icon Picker](/screenshots/example.png)

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
4. Go over your field configurations and change the field type from `icon-picker` to `svg_icon_picker` in the field settings. Be aware of the underscores in the field type name.
5. Check if the field type is now available in your ACF field settings

## Filters

Use the below filters to override the default icon folder, path, and / or URL:

```php
// modify the path to the icons directory
add_filter('acf_icon_path_suffix', 'acf_icon_path_suffix');

function acf_icon_path_suffix($path_suffix) {
    return 'assets/img/icons/';
}

// modify the path to the above prefix
add_filter('acf_icon_path', 'acf_icon_path');

function acf_icon_path($path_suffix) {
    return plugin_dir_path(__FILE__);
}

// modify the URL to the icons directory to display on the page
add_filter('acf_icon_url', 'acf_icon_url');

function acf_icon_url($path_suffix) {
    return plugin_dir_url( __FILE__ );
}
```

### For Sage/Bedrock edit filters.php:

```php
/// modify the path to the icons directory
add_filter('acf_icon_path_suffix',
  function ( $path_suffix ) {
    return '/assets/images/icons/'; // After assets folder you can define folder structure
  }
);

// modify the path to the above prefix
add_filter('acf_icon_path',
  function ( $path_suffix ) {
    return '/app/public/web/themes/THEME_NAME/resources';
  }
);

// modify the URL to the icons directory to display on the page
add_filter('acf_icon_url',
  function ( $path_suffix ) {
    return get_stylesheet_directory_uri();
  }
);
```

## Using with [ACF Builder](https://github.com/StoutLogic/acf-builder) / [ACF Composer](https://github.com/Log1x/acf-composer)

```php
$fields->addField('my_icon', 'svg_icon_picker', [
    'label' => 'My Icon',
])
```

## Originally Forked from [houke/acf-icon-picker](https://github.com/houke/acf-icon-picker)
Updated to work with ACF v6.3 and above.

## Changelog

* 3.1.0 - Changed name of field to `svg_icon_picker` to avoid conflicts with vanilla ACF Icon Picker field.
* 3.0.0 - Revert to original ACF field name, quick tidy + README updates
* 2.0.0 - Fix for ACF 6.3 which now has an official icon-picker field + merged open PRs from [Levdbas](https://github.com/houke/acf-icon-picker/pull/38) & [phschmanau](https://github.com/houke/acf-icon-picker/pull/37)
* 1.9.1 - ACF 6 compatibility fix. Thanks to [idflood](https://github.com/houke/acf-icon-picker/pull/30)
* 1.9.0 - Fix issue with Gutenberg preview not updating when removing. Thanks to [cherbst](https://github.com/houke/acf-icon-picker/pull/23)
* 1.8.0 - Fix issue with Gutenberg not saving icon. Thanks to [tlewap](https://github.com/houke/acf-icon-picker/pull/17)
* 1.7.0 - 2 new filters for more control over icon path. Thanks to [benjibee](https://github.com/houke/acf-icon-picker/pull/11)
* 1.6.0 - Performance fix with lots of icons. Thanks to [idflood](https://github.com/houke/acf-icon-picker/pull/9)
* 1.5.0 - Fix issue where searching for icons would break preview if icon name has space
* 1.4.0 - Add filter to change folder where svg icons are stored
* 1.3.0 - Adding close option on modal
* 1.2.0 - Adding search filter input to filter through icons by name
* 1.1.0 - Add button to remove the selected icon when the field is not required
* 1.0.0 - First release
