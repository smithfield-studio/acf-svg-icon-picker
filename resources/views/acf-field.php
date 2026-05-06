<?php

/**
 * @var array<string, mixed>      $field
 * @var string                    $saved_value
 * @var array<string, mixed>|null $icon
 * @var bool                      $is_missing
 * @var list<string>              $allowed_groups
 */

// Narrow extracted values to their expected types so the markup below stays
// declarative and PHPStan can verify the esc_*() calls. $saved_value is
// already string by the controller's contract — see render_field().
$field_name = isset($field['name']) && is_string($field['name']) ? $field['name'] : '';
$icon_url = isset($icon['url']) && is_string($icon['url']) ? $icon['url'] : '';

$selector_classes = ['acf-svg-icon-picker__selector'];
if ($is_missing) {
    $selector_classes[] = 'acf-svg-icon-picker__selector--missing';
}
// The trigger renders an <img alt=""> once an icon is picked, so the slug
// only reaches assistive tech via the button's accessible name. Three states:
// empty (no value), selected (slug + change hint), and missing (warning).
if ($is_missing) {
    $trigger_aria_label = sprintf(
        __('Missing icon: %s. Click to pick a replacement.', 'acf-svg-icon-picker'),
        $saved_value,
    );
} elseif ($saved_value !== '') {
    $trigger_aria_label = sprintf(__('Selected icon: %s. Click to change.', 'acf-svg-icon-picker'), $saved_value);
} else {
    $trigger_aria_label = __('Choose icon', 'acf-svg-icon-picker');
}

$clear_label = __('Clear', 'acf-svg-icon-picker');

// Path-style rendering of the saved slug for the missing-state error message.
// Composite (`nucleo.fan`) becomes `nucleo/fan.svg`; bare slugs become
// `<slug>.svg`. Indicative location within the configured icon set rather
// than an absolute filesystem path.
$missing_path = str_replace('.', '/', $saved_value) . '.svg';
?>
<div
	class="acf-svg-icon-picker"
	<?php if (!empty($allowed_groups)) { ?>
		data-allowed-groups="<?php echo esc_attr(implode(',', $allowed_groups)); ?>"
	<?php } ?>
>
	<div class="<?php echo esc_attr(implode(' ', $selector_classes)); ?>">
		<button
			type="button"
			class="acf-svg-icon-picker__icon"
			aria-label="<?php echo esc_attr($trigger_aria_label); ?>"
			<?php if ($is_missing) { ?>
				title="<?php echo esc_attr(sprintf(__('Missing icon: %s', 'acf-svg-icon-picker'), $saved_value)); ?>"
				data-missing-slug="<?php echo esc_attr($saved_value); ?>"
			<?php } ?>
		>
			<?php if ($icon_url !== '') { ?>
				<img src="<?php echo esc_url($icon_url); ?>" alt="" />
			<?php } elseif ($is_missing) { ?>
				<span aria-hidden="true">!</span>
			<?php } else { ?>
				<span aria-hidden="true">&plus;</span>
			<?php } ?>
		</button>
		<input
			type="hidden"
			name="<?php echo esc_attr($field_name); ?>"
			value="<?php echo esc_attr($saved_value); ?>"
			readonly
		/>
	</div>

	<?php if ($saved_value !== '' && !$is_missing) { ?>
		<code class="acf-svg-icon-picker__slug"><?php echo esc_html($saved_value); ?></code>
	<?php } ?>

	<?php if ($is_missing) { ?>
		<p class="acf-svg-icon-picker__missing-msg" role="status">
			<strong><?php esc_html_e('Icon not found.', 'acf-svg-icon-picker'); ?></strong>
			<span class="acf-svg-icon-picker__missing-path">
				<?php

				// translators: %s: relative path to the missing icon, e.g. "nucleo/fan.svg".
				printf(
    				esc_html__('Please replace or check path: %s', 'acf-svg-icon-picker'),
    				'<code>' . esc_html($missing_path) . '</code>',
				);
				?>
			</span>
		</p>
	<?php } ?>

	<?php if (empty($field['required'])) { ?>
		<button type="button" class="button button-small acf-svg-icon-picker__remove">
			<?php echo esc_html($clear_label); ?>
		</button>
	<?php } ?>
</div>
