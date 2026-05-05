# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [5.0.0]

A major release focused on icon-set organisation, accessibility, and sane handling of stale data. See [UPGRADING.md](UPGRADING.md) for the v4 → v5 walkthrough.

### Added

- **Multiple icon locations** — `acf_svg_icon_picker_custom_location` now accepts a list of `{ path, url, name?, key? }` arrays. Each location renders as a named group in the picker UI; a single location with `'group_by_subdir' => true` exposes each top-level subfolder as its own group instead.
- **Per-field group filter** — new `allowed_groups` field setting restricts a specific field to a chosen subset of the configured groups (closes [#32](https://github.com/smithfield-studio/acf-svg-icon-picker/issues/32)).
- **Composite save format in grouped mode** (`groupkey.slug`, e.g. `nucleo.arrow-down`). Records the editor's explicit pick so the same slug can live in multiple groups without colliding. Strict resolution — see the missing-asset note below.
- **Missing-asset state in the editor.** When a saved value can't be resolved (file deleted, group renamed, etc.), the field renders a distinct red-tinted trigger with a `!` glyph and an inline status message ("Icon not found. Please replace or check original path: …"). Editors can spot stale data instead of mistaking it for a never-picked field. Frontend output unchanged.
- **Native `<dialog>` popup** — focus trap, Esc-to-close, focus restoration, inert background page all handled by the browser. Icon tiles are real `<button>` elements with `aria-label`. Arrow-key navigation across the grid (Left/Right step a tile, Up/Down jump a row, Home/End to extremes), with column preservation when crossing groups.
- **PHP 8.2 minimum**, declared via `composer.json` so install fails fast. PHP 8.1 reached EOL in Nov 2025.
- **`'array'` return format** + new `get_svg_icon_data()` helper. Returns `{ slug, url, path, title, group_key, group_name }`, or `null` when the saved value no longer resolves. SVG markup is intentionally omitted to keep the format cheap on long lists — call `get_svg_icon($slug)` when markup is needed.

### Breaking

- **Deprecated filters removed** — `acf_icon_path`, `acf_icon_url`, `acf_icon_path_suffix` (deprecated since 4.0.0). Use `acf_svg_icon_picker_folder` instead.
- **Global constants removed** — `ACF_SVG_ICON_PICKER_VERSION`, `_URL`, `_PATH`. Version is now `\SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker::VERSION`; URL/path are derived inline at use sites.
- **DOM hooks renamed** for custom CSS that targets the picker UI:
  - `.acf-svg-icon-picker__popup-overlay` → `.acf-svg-icon-picker__popup::backdrop`
  - `.acf-svg-icon-picker__popup ul li[data-svg]` → `.acf-svg-icon-picker__option`
- **Browser baseline raised** by the native `<dialog>` move: Chrome 37+, Firefox 98+, Safari 15.4+. WP admin only — frontend unchanged.
- **Composite save values** in grouped mode. Code that reads via `get_field()` + `get_svg_icon*()` keeps working (helpers accept both forms). Custom code that does its own slug → file lookup needs to handle the `groupkey.slug` form (`str_replace('.', '/', $slug) . '.svg'` is a reasonable default).

### Fixed

- `get_svg_icon_uri()` returns the matching custom-location URL instead of always falling back to `get_theme_file_uri()` (which 404'd whenever icons lived outside the theme).
- Pressing Enter anywhere in the admin no longer opens the icon picker ([#34](https://github.com/smithfield-studio/acf-svg-icon-picker/issues/34)) — trigger and remove buttons were missing `type="button"` and defaulted to `type="submit"`.
- Numerous a11y gaps in the popup: missing dialog role, no focus trap, no Esc handler, no focus restoration, unlabelled search input and close button, list items not keyboard-focusable.
- `group_by_subdir` resolution for non-slug folder names — the helper now scans subdirs and matches via `sanitize_title($subdir)`, so `Brand Icons/` on disk resolves saved values like `brand-icons.foo`.
- Group-key collisions auto-disambiguate with `-2`, `-3`, … rather than letting later locations silently overwrite or merge into earlier ones.

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
