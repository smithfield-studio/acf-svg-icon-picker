# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [5.0.0]

### Added

- **Multiple icon locations** — `acf_svg_icon_picker_custom_location` now accepts a list of `{ path, url, name?, key? }` arrays. Each location renders as a named group in the picker UI. The single `{ path, url }` shape still works (renders flat).
- **Auto-grouping by subdirectory** — set `'group_by_subdir' => true` on a single location to expose each top-level subfolder as its own group.
- **Per-field group filter** — new `allowed_groups` field setting restricts a specific field to a chosen subset of the configured groups (closes [#32](https://github.com/smithfield-studio/acf-svg-icon-picker/issues/32)).
- **Arrow-key navigation** in the icon grid with roving tabindex (Left/Right step a tile, Up/Down jump a row, Home/End jump to ends).
- **PHP 8.2 minimum** (raised from 8.1). PHP 8.1 reached end-of-life in Dec 2025; 8.2 is the lowest version still receiving security fixes. Declared via `composer.json` so install fails on older versions instead of silently breaking.

### Changed

- **Save format in grouped mode is now `groupkey.slug`** (e.g. `nucleo.arrow-down`). Bare slugs (`arrow-down`) saved by older versions still resolve via a first-match scan, so existing data keeps working. Flat-mode (single `{ path, url }`) save format is unchanged.
- **Picker popup is now a native `<dialog>`.** Browser supplies focus trap, Esc-to-close, focus restoration, and inert background page; removes the manual focus-trap and overlay JS.
- **Vanilla JS picker.** jQuery dropped from the picker's own logic; only the ACF integration point still touches it (`acf.get_fields()` returns a jQuery collection).
- **Icon tiles are real `<button>` elements** with `aria-label` — focusable, keyboard-activatable, screen-reader-friendly.
- Picker grid uses `grid-template-columns: repeat(auto-fill, minmax(120px, 1fr))`; tiles use `aspect-ratio: 1` and reflow with popup width. Popup itself sizes to `clamp(320px, 75vw, 1200px) × clamp(400px, 75vh, 900px)`.
- Popup header is a single compact row (title + search + close); trigger selector enlarged to 70px.
- PHPStan upgraded to 2.x and raised to level 10 (max). Strict array-shape types and `is_string` guards on filter results throughout.
- Modern PHP-syntax pass via [Rector](https://getrector.com/) (one-shot, not a permanent dep): `::class` constant references over string class names, first-class callable syntax (`$this->method(...)`, `is_string(...)`), `readonly` on the `path_suffix` property, arrow functions in test fixtures, `dirname(__FILE__, 2)` for nested dirname calls, `=== []` in place of `empty()` on known-array values.

### Fixed

- **Pressing Enter anywhere in the admin opening the picker** ([#34](https://github.com/smithfield-studio/acf-svg-icon-picker/issues/34)) — the trigger and remove buttons were missing `type="button"`, so they defaulted to `type="submit"` and intercepted Enter inside Flexible Content (and any other form context).
- `get_svg_icon_uri()` returns the matching custom-location URL instead of always falling back to `get_theme_file_uri()` (which 404'd whenever icons lived outside the theme).
- Numerous a11y gaps in the popup: missing dialog role, no focus trap, no Esc handler, no focus restoration, unlabelled search input and close button, list items not keyboard-focusable.

### Removed

- `.acf-svg-icon-picker__popup-overlay` wrapper element — replaced by native `<dialog>` + `::backdrop`. The old z-index workaround is no longer needed (dialogs opened with `showModal()` live in the browser's top-layer).
- Deprecated filters `acf_icon_path`, `acf_icon_url`, and `acf_icon_path_suffix` (deprecated since 4.0.0). Use `acf_svg_icon_picker_folder` for the icon folder; the other two had no effect and were emitting notices only.
- Global constants `ACF_SVG_ICON_PICKER_VERSION`, `ACF_SVG_ICON_PICKER_URL`, and `ACF_SVG_ICON_PICKER_PATH`. They were never documented as public API. The version now lives on the field class as `ACF_Field_Svg_Icon_Picker::VERSION`; URL/path are derived inline via `plugin_dir_url(__FILE__)` / `plugin_dir_path(__FILE__)`.
- PHPCS / WPCS / 10up ruleset / `phpcompatibility/php-compatibility` — replaced by [Mago](https://github.com/carthage-software/mago) for PHP formatting.
- ESLint / Stylelint — replaced by [Oxfmt + Oxlint](https://github.com/oxc-project/oxc).

### Compatibility notes

- **DOM hooks for the picker UI changed.** If you target `.acf-svg-icon-picker__popup-overlay` or `.acf-svg-icon-picker__popup ul li[data-svg]` in custom CSS, switch to `.acf-svg-icon-picker__popup` and `.acf-svg-icon-picker__option`.
- **Browser baseline implicitly raised** by the native `<dialog>` move: Chrome 37+, Firefox 98+, Safari 15.4+.
- **Saved values may now be composite (`groupkey.slug`)** when the field is configured in grouped mode. Code that reads the field via `get_field()` and passes the result through `get_svg_icon*()` keeps working — the helpers parse both forms. Custom code that does its own slug → file lookup needs to handle the prefix.

## [4.3.1]

- fix: Increase z-index of SVG icon picker overlay so it sits above the block-editor extended view, co-authored by @adambichler in #38.
- chore: Update GitHub Actions workflows to use latest PHP versions.
- chore: Code formatting pass.

## [4.3.0]

- Change action hook for field type registration by @EarthmanWeb in #37.
- Update GitHub Actions workflows to use latest PHP versions.

## [4.2.0]

- `get_svg_icon_path()` helper function now returns the correct path when using the custom location filter.

## [4.1.0]

- Add ability to return the icon markup directly from the field via `'return_format' => 'icon'`.
- Enhance markup of the icon picker modal and field.
- Update hook docs in README, by @huubl in #29.
- Add tests for the new return format.
- Run PHPCS on PRs.

## [4.0.1]

- Fix version numbers in constant.
- Add files to export ignore.

## [4.0.0]

- Remove/deprecate legacy filters; refactor and simplify icon path filters, by [@Levdbas](https://github.com/Levdbas) in [#25](https://github.com/smithfield-studio/acf-svg-icon-picker/pull/25).
- Add unit tests, PHPStan, and return types, by [@Levdbas](https://github.com/Levdbas) in [#25](https://github.com/smithfield-studio/acf-svg-icon-picker/pull/25).
- Better support for hashed assets, by [@mike-sheppard](https://github.com/mike-sheppard) in [#26](https://github.com/smithfield-studio/acf-svg-icon-picker/pull/26).

## [3.1.4]

- Fix filter on filenames with diacritical marks, by [@Rvervuurt](https://github.com/Rvervuurt) in [#21](https://github.com/smithfield-studio/acf-svg-icon-picker/pull/21).

## [3.1.3]

- Added MutationObserver, by [@chrisbakr](https://github.com/chrisbakr) in [#20](https://github.com/smithfield-studio/acf-svg-icon-picker/pull/20).

## [3.1.2]

- Add debounce to improve filter performance, by [@stefanmomm](https://github.com/stefanmomm) in [#17](https://github.com/smithfield-studio/acf-svg-icon-picker/pull/17).

## [3.1.1]

- Optimize css, by [@stefanmomm](https://github.com/stefanmomm) in [#16](https://github.com/smithfield-studio/acf-svg-icon-picker/pull/16).

## [3.1.0]

- Changed name of field to `svg_icon_picker` to avoid conflicts with vanilla ACF Icon Picker field.

## [3.0.0]

- Revert to original ACF field name, quick tidy + README updates.

## [2.0.0]

- Fix for ACF 6.3 which now has an official icon-picker field, plus merged open PRs from [@Levdbas](https://github.com/Levdbas) in [#38](https://github.com/houke/acf-icon-picker/pull/38) and [@phschmanau](https://github.com/phschmanau) in [#37](https://github.com/houke/acf-icon-picker/pull/37).

---

**Forked from [houke/acf-icon-picker](https://github.com/houke/acf-icon-picker).** Pre-fork history below.

---

## [1.9.1]

- ACF 6 compatibility fix, by [@idflood](https://github.com/idflood) in [#30](https://github.com/houke/acf-icon-picker/pull/30).

## [1.9.0]

- Fix issue with Gutenberg preview not updating when removing, by [@cherbst](https://github.com/cherbst) in [#23](https://github.com/houke/acf-icon-picker/pull/23).

## [1.8.0]

- Fix issue with Gutenberg not saving icon, by [@tlewap](https://github.com/tlewap) in [#17](https://github.com/houke/acf-icon-picker/pull/17).

## [1.7.0]

- 2 new filters for more control over icon path, by [@benjibee](https://github.com/benjibee) in [#11](https://github.com/houke/acf-icon-picker/pull/11).

## [1.6.0]

- Performance fix with lots of icons, by [@idflood](https://github.com/idflood) in [#9](https://github.com/houke/acf-icon-picker/pull/9).

## [1.5.0]

- Fix issue where searching for icons would break preview if icon name has space.

## [1.4.0]

- Add filter to change folder where svg icons are stored.

## [1.3.0]

- Adding close option on modal.

## [1.2.0]

- Adding search filter input to filter through icons by name.

## [1.1.0]

- Add button to remove the selected icon when the field is not required.

## [1.0.0]

- First release.
