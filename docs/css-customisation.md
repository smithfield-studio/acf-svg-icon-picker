# Customising the picker UI

The picker exposes a small set of CSS custom properties so you can retune sizing and colours from your theme without overriding rules. Defaults:

```css
:root {
  /* Generic */
  --acfsip-border-radius: 4px;
  --acfsip-spacing: 10px;
  --acfsip-accent-color: #2271b1; /* focus ring; pulls from --wp-admin-theme-color when set */

  /* Trigger (round button next to the field label) */
  --acfsip-trigger-size: 70px;
  --acfsip-trigger-bg: #eee;
  --acfsip-trigger-bg-hover: #ddd;

  /* Popup */
  --acfsip-popup-width: clamp(320px, 75vw, 1200px);
  --acfsip-popup-height: clamp(400px, 75vh, 900px);
  --acfsip-popup-bg: #fff;
  --acfsip-popup-header-bg: #f4f4f4;
  --acfsip-popup-backdrop: rgb(0 0 0 / 80%);

  /* Icon grid */
  --acfsip-tile-min-width: 120px; /* drives auto-fill column count */
  --acfsip-tile-icon-size: 50%; /* image size relative to tile */
  --acfsip-tile-bg-hover: #eee;
}
```

## Overriding

Override at any specificity. For example, a wider popup just for one field group:

```css
.acf-field-group-my-icons .acf-svg-icon-picker__popup {
  --acfsip-popup-width: clamp(600px, 90vw, 1600px);
  --acfsip-tile-min-width: 96px;
}
```

Or globally from your theme's admin stylesheet:

```css
:root {
  --acfsip-trigger-size: 50px;
  --acfsip-tile-min-width: 96px;
}
```

## Notes

- `--acfsip-accent-color` defaults to `var(--wp-admin-theme-color, #2271b1)`, so the focus ring already matches the user's WP admin colour scheme out of the box.
- The popup uses block-axis logical properties (`margin-block-end`, `padding-inline`, etc.) so RTL languages render correctly without extra work.
- DOM hooks for the picker UI are considered internal — they may change between minor versions. The CSS custom properties listed above are the supported customisation surface.
