[![Latest Stable Version](https://img.shields.io/packagist/v/smithfield-studio/acf-svg-icon-picker.svg?style=flat-square)](https://packagist.org/packages/smithfield-studio/acf-svg-icon-picker)
[![PHP unit tests](https://github.com/smithfield-studio/acf-svg-icon-picker/actions/workflows/php-unit-tests.yml/badge.svg?branch=main)](<[https://github.com//timber/timber/actions/workflows/php-unit-tests.yml?query=branch:2.x](https://github.com/smithfield-studio/acf-svg-icon-picker/actions/workflows/php-unit-tests.yml/badge.svg?branch=main)>)

# ACF SVG Icon Picker Field

Add a field type to ACF for selecting SVG icons from a popup modal. Theme developers provide the icon set; editors pick from it.

![SVG Icon Picker Popup](/screenshots/example-popup.jpg)

![Grouped picker UI with multiple icon sets](/screenshots/grouped-picker.jpg)

![Missing-asset state when a saved icon no longer resolves](/screenshots/missing-state.jpg)

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

> **Security note**: `get_svg_icon()` and `return_format = 'icon'` return the SVG file's contents verbatim — no sanitization. The plugin is built on the assumption that icons are committed to your theme/plugin and reviewed by you. **Do not point `acf_svg_icon_picker_custom_location` at a directory that accepts user uploads** (e.g. `wp-content/uploads/`) — a malicious editor could land XSS via an SVG `<script>` element. If you need that workflow, sanitize with [`enshrined/svg-sanitize`](https://github.com/darylldoyle/svg-sanitize) downstream of the helpers, or stay on `return_format = 'value'` and render `<img src>` against the URL only.

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

## How values are stored

The saved value is always a string. Its shape depends on which mode the picker is in at save time:

| Picker mode | Stored value | Example |
| --- | --- | --- |
| Default theme `icons/` folder, or single `{path, url}` filter | Bare slug | `arrow-down` |
| Multiple locations, or `group_by_subdir => true` | Composite `groupkey.slug` | `brand.discord` |

Resolution rules:

- The helper functions (`get_svg_icon_uri()`, `get_svg_icon_path()`, `get_svg_icon()`) accept both forms and resolve correctly.
- Composite values are **strict**: if the `groupkey` prefix no longer matches any configured group, the field renders the missing-asset state in the editor instead of substituting a same-slug icon from another group. The rationale is that swapping in an unrelated icon silently changes editorial intent — better to surface the data drift than paper over it.
- Bare values are **first-match-wins** across all configured locations (legacy back-compat for values saved before grouping was introduced).
- In grouped mode, saving a previously-bare value through the picker may auto-canonicalise to the composite form when exactly one configured group claims the slug — see `update_value()`.

If you read field values in custom code, prefer the helper functions; they paper over the difference.

## WPGraphQL

If [WPGraphQL](https://www.wpgraphql.com/) and [wp-graphql-acf](https://github.com/wp-graphql/wpgraphql-acf) are active, the field is automatically registered as an `SvgIcon` GraphQL object type:

```graphql
{
  page(id: "...") {
    myIcon {
      slug   # e.g. "brand.discord"
      url    # public URL of the resolved SVG
      svg    # inline SVG markup
    }
  }
}
```

`slug` is the bare slug in flat mode and `groupkey.slug` in grouped mode; `url` and `svg` are resolved using the same helpers as PHP-side code, so all three filter shapes (single, list, `group_by_subdir`) are honoured.

## Filters

| Filter | Signature | Default | Since |
| --- | --- | --- | --- |
| `acf_svg_icon_picker_folder` | `(string $folder): string` | `'icons/'` | 4.0.0 |
| `acf_svg_icon_picker_custom_location` | `(false\|array): false\|array` — return `false` to fall through to theme dirs, an array `{path, url, name?, key?, group_by_subdir?}` for a single location, or a list of such arrays for grouped mode | `false` | 4.0.0 |

```php
// Change the theme-relative folder.
add_filter('acf_svg_icon_picker_folder', fn() => 'resources/icons/');

// Single custom location outside the theme.
add_filter('acf_svg_icon_picker_custom_location', fn() => [
    'path' => WP_CONTENT_DIR . '/icons/',
    'url'  => content_url() . '/icons/',
]);
```

See [Configuring icon locations](#configuring-icon-locations) above for grouped + subdir-mode shapes.

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
