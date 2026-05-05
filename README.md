[![Latest Stable Version](https://img.shields.io/packagist/v/smithfield-studio/acf-svg-icon-picker.svg?style=flat-square)](https://packagist.org/packages/smithfield-studio/acf-svg-icon-picker)
[![PHP unit tests](https://github.com/smithfield-studio/acf-svg-icon-picker/actions/workflows/php-unit-tests.yml/badge.svg?branch=main)](<[https://github.com//timber/timber/actions/workflows/php-unit-tests.yml?query=branch:2.x](https://github.com/smithfield-studio/acf-svg-icon-picker/actions/workflows/php-unit-tests.yml/badge.svg?branch=main)>)

# ACF SVG Icon Picker Field

Add a field type to ACF for selecting SVG icons from a popup modal. Theme developers provide the icon set; editors pick from it.

![SVG Icon Picker Popup](/screenshots/example-popup.jpg)

## Features

- **Theme-defined icon sets.** Icons live in your theme's `icons/` folder by default; configurable via filter.
- **Multiple icon sets in one picker.** Group brand, UI, decorative icons (etc.) into named sections of the popup, or auto-group by subdirectory.
- **Per-field group restriction.** A given field can opt to show only a subset of the configured groups (`allowed_groups`).
- **Native `<dialog>` popup** with browser-supplied focus trap, Esc-to-close, focus restoration, and arrow-key grid navigation.
- **Honest handling of stale data.** Saved values that no longer resolve render a distinct missing-asset state in the editor — never a silent substitute.
- **Two return formats.** `'value'` returns the slug (default); `'icon'` returns the SVG markup directly.
- **Parent/child theme aware.** Child-theme icons override same-slug parent-theme icons.

## Requirements

- ACF 5 or 6 (free or Pro).
- PHP 8.2+ — install fails fast on older versions via `composer.json`'s `require.php`.

## Installation

### Composer

```bash
composer require smithfield-studio/acf-svg-icon-picker
```

Activate via the plugins admin page.

### Manual

Copy the `acf-svg-icon-picker` folder into `wp-content/plugins/` and activate.

## Usage

By default, the picker scans the active theme's `icons/` folder (parent + child). Drop SVG files in there and they appear in the picker grid.

In your code, read field values via the helper functions — they handle both single-folder and multi-location setups, parent/child theme overrides, and composite group prefixes:

```php
use function SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_uri;
use function SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_path;
use function SmithfieldStudio\AcfSvgIconPicker\get_svg_icon;

$slug = get_field('my_icon_field');

$icon_url  = get_svg_icon_uri($slug);   // public URL
$icon_path = get_svg_icon_path($slug);  // filesystem path
$icon_svg  = get_svg_icon($slug);       // SVG markup as a string
```

The field has a `return_format` setting: `'value'` (default) returns the slug for the helpers above; `'icon'` returns the SVG markup directly so `get_field()` is enough.

### With [ACF Builder](https://github.com/StoutLogic/acf-builder) / [ACF Composer](https://github.com/Log1x/acf-composer)

```php
$fields->addField('my_icon', 'svg_icon_picker', [
    'label'         => 'My Icon',
    'return_format' => 'value', // or 'icon'
]);
```

## Configuring icon locations

### Custom folder in your theme

```php
add_filter('acf_svg_icon_picker_folder', fn() => 'resources/icons/');
```

### Icons outside the theme

```php
add_filter('acf_svg_icon_picker_custom_location', fn() => [
    'path' => WP_CONTENT_DIR . '/icons/',
    'url'  => content_url() . '/icons/',
]);
```

### Multiple icon sets (grouped picker)

Return a list of locations and the picker groups them under headings — useful for offering distinct icon sets without merging them on disk.

```php
add_filter('acf_svg_icon_picker_custom_location', fn() => [
    [
        'name' => 'Brand',                            // group heading
        'key'  => 'brand',                            // optional, derived from name when omitted
        'path' => WP_CONTENT_DIR . '/icons/brand/',
        'url'  => content_url() . '/icons/brand/',
    ],
    [
        'name' => 'UI',
        'path' => WP_CONTENT_DIR . '/icons/ui/',
        'url'  => content_url() . '/icons/ui/',
    ],
]);
```

In grouped mode, saved values are stored as `groupkey.slug` (e.g. `brand.discord`) so the same slug can live in multiple groups without colliding. Resolution is strict — a saved value whose group prefix no longer matches any configured group renders a missing-asset state in the editor rather than silently substituting an icon from another group. Empty locations (no SVGs found) are silently skipped. Group keys that slugify the same way auto-disambiguate with `-2`, `-3`, … suffixes.

### Auto-grouping by subdirectory

For projects that already organise icons into subfolders, set `group_by_subdir` on a single location and each top-level subfolder becomes its own group:

```php
// resources/icons/
// ├── brand/        → "Brand" group
// ├── ui/           → "UI" group
// └── decorative/   → "Decorative" group
add_filter('acf_svg_icon_picker_custom_location', fn() => [
    'path'            => get_stylesheet_directory() . '/resources/icons/',
    'url'             => get_stylesheet_directory_uri() . '/resources/icons/',
    'group_by_subdir' => true,
]);
```

The subfolder name (Title-Cased) becomes the group heading; the slugified name becomes the group key. Folder names with spaces or capitals (`Brand Icons/`) are matched via `sanitize_title()`, so `brand-icons.foo` resolves the literal folder.

### Restricting which groups appear per field

When the custom-location filter declares groups, each field can opt to show only a subset via the `allowed_groups` setting (a list of group keys). Empty / unset = show all.

```php
$fields->addField('industry_icon', 'svg_icon_picker', [
    'label'          => 'Industry icon',
    'allowed_groups' => ['nucleo', 'ui'], // hide 'brand', 'decorative', etc.
]);
```

In the field-editor UI this appears as a checkbox group listing every available group; the setting is hidden when no groups are configured.

## Upgrading

Coming from v4 (or earlier)? See [UPGRADING.md](UPGRADING.md).

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
