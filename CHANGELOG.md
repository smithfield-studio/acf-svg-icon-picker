# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [4.4.0] — 2026-05-01

### Added

- **Multiple icon locations** — `acf_svg_icon_picker_custom_location` now accepts a list of `{ path, url, name?, key? }` arrays. Locations render as named groups in the picker UI. Single `{ path, url }` shape remains supported (back-compatible).
- **Auto-grouping by subdirectory** — set `'group_by_subdir' => true` on a single location to expose each top-level subfolder as its own group (subfolder name becomes the heading).
- **Per-field group filter** — closes [#32](https://github.com/smithfield-studio/acf-svg-icon-picker/issues/32). New `allowed_groups` field setting (rendered as a checkbox group of available group keys) restricts the picker to a chosen subset of groups for that specific field. Empty / unset = show all (back-compatible).
- **Arrow-key navigation** in the icon grid (Left/Right step one tile, Up/Down jump a row, Home/End jump to ends) with roving tabindex.
- **Subtle scale-up transition** on icon tile hover and keyboard focus, with `prefers-reduced-motion` opt-out.
- New helper `resolve_icon_in_location()` for resolving an icon within a single location config (honours subdir mode).
- New internal helper `normalize_custom_locations()` shared between the field class and helper functions.
- New public property `$plugin->groups` exposing the grouping metadata.
- Editor JS exposes `acfSvgIconPicker.groups` alongside the existing `svgs` data.
- [Oxfmt](https://github.com/oxc-project/oxc) + [Oxlint](https://github.com/oxc-project/oxc) for JS / CSS / JSON formatting and JS linting (Rust-based, sub-second). Replaces ESLint + Stylelint.
- [Mago](https://github.com/carthage-software/mago) (`carthage-software/mago`) for PHP formatting. Replaces PHPCS.
- New `.github/workflows/code-quality.yml` running PHPStan, Mago format check, Oxfmt format check, and Oxlint on every push to `main` and PR (in addition to the existing PHPUnit workflow).
- **PHPStan strictness raised from level 5 → level 9** (max for PHPStan 1.x). Required adding precise array shape types (`array{path: string, url: string, name?: string, key?: string, group_by_subdir?: bool}`) to the location-config contract, narrowing `mixed` filter inputs with explicit `is_string` checks, and handling the `false` return from `file_get_contents`/`scandir`.
- **WP admin colour scheme integration** — the picker's focus ring now pulls from `--wp-admin-theme-color`, so it matches the user's chosen admin colour scheme out of the box.
- **RTL support** — block-axis margins, paddings, and borders use logical properties (`margin-block-end`, `padding-inline`, `border-block-end`, `min-inline-size`) so right-to-left languages render correctly.

### Changed

- **Picker popup is now a native `<dialog>` element.** Browser provides focus trap, Escape-to-close, focus restoration, and inert background page automatically. Removes the manual focus-trap and overlay JS.
- **Vanilla JS picker code.** Removed jQuery from the picker's own logic — only the ACF integration point still touches jQuery (because `acf.get_fields()` returns a jQuery collection). All DOM work now uses native `addEventListener`, `closest`, `classList`, `dispatchEvent`, etc.
- **Icon tiles are real `<button>` elements** (`<li><button data-svg>`), not clickable list items. They're focusable, keyboard-activatable, and have `aria-label` for screen readers.
- Picker grid uses CSS `grid-template-columns: repeat(auto-fill, minmax(120px, 1fr))` so columns reflow with the popup width. Tiles are `aspect-ratio: 1` for a uniform grid; icons normalised via `width: 50%; aspect-ratio: 1; object-fit: contain`.
- Popup sizing is responsive: `clamp(320px, 75vw, 1200px) × clamp(400px, 75vh, 900px)` with a 95v* viewport cap.
- Popup header is a single compact row: title, search input (flex-grow), close button.
- Trigger selector enlarged from 50px → 70px circle; remove button now centres beneath the trigger via a flex-column wrapper.
- Group headings render whenever the filter returns a list of locations (or a single location with `group_by_subdir`), even if only one ends up populated. Empty groups are silently hidden.
- `acf_svg_icon_picker_custom_location` filter callbacks may use the new optional `name` / `key` / `group_by_subdir` keys per location.
- Composer `composer.json` now declares `"require": { "php": ">=8.1" }` so installation enforces the same minimum the plugin header advertises.

### Fixed

- **Pressing Enter anywhere in the admin opening the icon picker** — fixes [#34](https://github.com/smithfield-studio/acf-svg-icon-picker/issues/34). The trigger and remove buttons in the field view template were missing `type="button"`, so they defaulted to `type="submit"` and intercepted Enter-key form submissions inside Flexible Content (and any other form context). Both now have explicit `type="button"`.
- `get_svg_icon_uri()` now returns the URL of the matching custom location instead of always falling back to `get_theme_file_uri()`. Pre-fix the helper returned theme-relative URLs even when icons were stored elsewhere via the custom-location filter — those URLs typically 404'd.
- Numerous a11y issues with the picker popup: missing dialog role, no focus trap, no Escape handler, no focus restoration, unlabelled search input, unlabelled close button, list items not keyboard-focusable.
- Pre-existing `@var strin` typo on `$path_suffix` corrected to `string`.

### Removed

- `.acf-svg-icon-picker__popup-overlay` wrapper element (replaced by native `<dialog>` + `::backdrop`). The previous z-index workaround is no longer necessary because dialogs opened with `showModal()` live in the browser's top-layer.
- Internal `$path` and `$url` private properties on the field class — they were write-only after the multi-location refactor and provided no external value.
- PHPCS, the 10up PHPCS ruleset, WPCS, and `phpcompatibility/php-compatibility` dev dependencies — replaced by Mago for PHP formatting.
- ESLint and Stylelint dev dependencies — replaced by Oxfmt + Oxlint.
- `.phpcs.xml.dist`, `.eslintrc.json`, and `.stylelintrc.json` config files (no longer used).

### Compatibility notes

- **DOM hooks for the picker UI changed.** If you target `.acf-svg-icon-picker__popup-overlay` or `.acf-svg-icon-picker__popup ul li[data-svg]` in custom CSS, replace them with `.acf-svg-icon-picker__popup` and `.acf-svg-icon-picker__option` respectively.
- **Browser baseline implicitly raised** by the move to native `<dialog>`: Chrome 37+, Firefox 98+ (Mar 2022), Safari 15.4+ (Mar 2022).

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
