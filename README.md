[![Latest Stable Version](https://img.shields.io/packagist/v/smithfield-studio/acf-svg-icon-picker.svg?style=flat-square)](https://packagist.org/packages/smithfield-studio/acf-svg-icon-picker)
[![PHP unit tests](https://github.com/smithfield-studio/acf-svg-icon-picker/actions/workflows/php-unit-tests.yml/badge.svg?branch=main)](<[https://github.com//timber/timber/actions/workflows/php-unit-tests.yml?query=branch:2.x](https://github.com/smithfield-studio/acf-svg-icon-picker/actions/workflows/php-unit-tests.yml/badge.svg?branch=main)>)

# ACF SVG Icon Picker Field

Add a field type to ACF for selecting SVG icons from a popup modal. Theme developers can provide a set of SVG icons to choose from.

## Compatibility

This ACF field type is compatible with:

- [x] ACF 6
- [x] ACF 5
- PHP 8.2+ (8.1 reached EOL Dec 2025; the install fails fast on older versions via `composer.json`'s `require.php`).

## Preview

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

1. Deactivate the old _ACF Icon Picker plugin_
2. Install the _ACF SVG Icon Picker plugin_ via Composer or manually
3. Activate the _ACF SVG Icon Picker plugin_
4. Configure your desired icon path via the new [filters](#filters). Remove any old filters in use: `acf_icon_path`, `acf_icon_url` or `acf_icon_path_suffix`.
5. Go over your field configurations and change the field type from `icon-picker` to `svg_icon_picker` in the field settings. Be aware of the underscores in the field type name.
6. Check if the field type is now available in your ACF field settings

## Usage of this plugin

We recommend storing your SVG icons in a folder within your theme. This plugin defaults to looking for icons inside the `icons/` folder of your theme. You can change this path by using the [`acf_svg_icon_picker_folder` filter](#filters).

When using this plugin in conjunction with a parent/child theme, you can store your icons in the parent theme and use the child theme to override the path to the icons. This way, you can provide a set of icons in the parent theme and still allow the child theme to override them.

You can configure this field to output the icon name or the icon SVG markup. You can set this in the field settings by changing the `return_format`.

### Helper functions

We provide helper functions to fetch icons from the theme folder, without it mattering if the icon is stored in the parent or child theme.

```php
use function SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_uri;
use function SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_path;
use function SmithfieldStudio\AcfSvgIconPicker\get_svg_icon;

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

#### Multiple icon locations (grouped picker)

The same filter can return a **list of locations**. The picker UI groups icons under a heading per location — useful for offering distinct icon sets (e.g. brand vs. utility) without needing to merge them on disk.

```php
add_filter('acf_svg_icon_picker_custom_location', function () {
  return [
    [
      'name' => 'Brand',                                 // group heading
      'key'  => 'brand',                                 // optional, derived from name when omitted
      'path' => WP_CONTENT_DIR . '/icons/brand/',
      'url'  => content_url() . '/icons/brand/',
    ],
    [
      'name' => 'UI',
      'path' => WP_CONTENT_DIR . '/icons/ui/',
      'url'  => content_url() . '/icons/ui/',
    ],
  ];
});
```

Behaviour:

- A single `{ path, url }` array still works (back-compatible) and renders flat without a group heading.
- A list of locations renders with group headings — even if only one location ends up populated.
- Empty locations (no SVGs found) are silently skipped.
- **Composite save format.** In grouped mode, saved values are stored as `groupkey.slug` (e.g. `brand.discord`), so the same slug can live in multiple groups without colliding. The `get_svg_icon*()` helpers parse the prefix and resolve within the matching group only.
- **Composite saves are strict.** A saved value whose group prefix no longer matches any configured group (e.g. you renamed `social` → `brand` in the filter) returns `''` from the helpers rather than silently substituting a same-slug icon from a different group. The editor's trigger renders a missing-asset state (red `!` + path-style error) so stale data is visible at a glance — pick a replacement or clear the field.
- **Legacy bare slugs** (`arrow-down`, no prefix) saved by pre-grouping versions still resolve via a first-match scan across all locations.
- **Group keys auto-disambiguate.** Two locations whose names slugify the same way (`Brand Icons` and `brand-icons` → both `brand-icons`) get suffixed `-2`, `-3`, … so the second location's icons don't silently merge or get dropped.

#### Auto-grouping by subdirectory

For projects that already organise icons into subfolders, set `group_by_subdir` on a single location to expose each top-level subfolder as its own group. The subfolder name (Title-Cased) becomes the group heading; the slugified name becomes the group key.

```php
// resources/icons/
// ├── brand/        → "Brand" group
// ├── ui/           → "UI" group
// └── decorative/   → "Decorative" group
add_filter('acf_svg_icon_picker_custom_location', function () {
  return [
    'path'            => get_stylesheet_directory() . '/resources/icons/',
    'url'             => get_stylesheet_directory_uri() . '/resources/icons/',
    'group_by_subdir' => true,
  ];
});
```

`get_svg_icon_path()` and `get_svg_icon_uri()` understand subdir mode — composite saves like `brand.discord` are resolved by scanning each subdir of the configured `path` and matching against `sanitize_title($subdir)`, so a saved `brand-icons.foo` resolves the literal `Brand Icons/` folder. Bare-slug saves (legacy data, no prefix) scan all subdirs first-match-wins.

### [ACF Builder](https://github.com/StoutLogic/acf-builder) / [ACF Composer](https://github.com/Log1x/acf-composer)

```php
$fields->addField('my_icon', 'svg_icon_picker', [
    'label'         => 'My Icon',
    'return_format' => 'value', // or 'icon'
])
```

### Limiting which groups appear per field

When the custom-location filter declares groups, each field can opt to show only a subset via the `allowed_groups` setting (a list of group keys). Unset/empty = show all groups.

```php
$fields->addField('industry_icon', 'svg_icon_picker', [
    'label'          => 'Industry icon',
    'allowed_groups' => ['nucleo', 'ui'], // hide 'social', 'decorative', etc.
])
```

In the WP admin field-editor UI, this appears as a checkbox group listing every available group; the setting is hidden when no groups are configured.

## Development

### Tests

PHPUnit-based, running against a real WordPress test environment. One-time setup of the WP test suite:

```bash
bash bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]
# example
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

Then:

```bash
composer test            # PHPUnit
composer phpstan         # static analysis (level 10)
composer format          # mago — format PHP
composer format:check    # mago — format check (used by CI)
```

### JS / CSS code quality

JS and CSS are formatted with [oxfmt](https://github.com/oxc-project/oxc) and JS is linted with [oxlint](https://github.com/oxc-project/oxc) (both Rust-based, very fast). Install once:

```bash
npm install
```

Then:

```bash
npm run format          # format JS, CSS, JSON
npm run format:check    # check formatting (used by CI)
npm run lint            # oxlint on JS
npm run lint:fix        # auto-fix lint issues
npm run check           # format:check + lint, run together
```

CI runs the full quality suite (`composer phpstan`, `composer format:check`, `npm run format -- --check`, `npm run lint`) on every push and PR via `.github/workflows/code-quality.yml`. The PHPUnit suite runs separately via `.github/workflows/php-unit-tests.yml`.

## Changelog

See [`CHANGELOG.md`](CHANGELOG.md) for the full version history, or the [GitHub releases](https://github.com/smithfield-studio/acf-svg-icon-picker/releases) page for tagged downloads.
