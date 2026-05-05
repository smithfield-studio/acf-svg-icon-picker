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
$trigger_aria_label = $is_missing
    ? sprintf(__('Missing icon: %s. Click to pick a replacement.', 'acf-svg-icon-picker'), $saved_value)
    : __('Choose icon', 'acf-svg-icon-picker');

// "Remove" implies removing something present; in the missing state nothing
// is visibly there, so "Clear" reads more honestly for the same underlying
// action (zero out the saved value).
$clear_label = $is_missing ? __('Clear', 'acf-svg-icon-picker') : __('Remove', 'acf-svg-icon-picker');

// Path-style rendering of the saved slug for the missing-state error message.
// Composite (`nucleo.fan`) becomes `nucleo/fan.svg`; bare slugs become
// `<slug>.svg`. Honest framing: it's where we'd look in the configured icon
// set, not an absolute filesystem path.
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
		<p class="acf-svg-icon-picker__slug">
			<?php echo esc_html($saved_value); ?>
		</p>
	<?php } ?>

	<?php if (empty($field['required'])) { ?>
		<button type="button" class="button acf-svg-icon-picker__remove">
			<?php echo esc_html($clear_label); ?>
		</button>
	<?php } ?>

	<?php if ($is_missing) { ?>
		<p class="acf-svg-icon-picker__missing-msg" role="status">
			<strong><?php esc_html_e('Icon not found.', 'acf-svg-icon-picker'); ?></strong>
			<br>
			<?php

			printf(
    			// translators: %s: relative path to the missing icon, e.g. "nucleo/fan.svg".
    			esc_html__('Please replace or check original path: %s', 'acf-svg-icon-picker'),
    			'<code>' . esc_html($missing_path) . '</code>',
			);
			?>
		</p>
	<?php } ?>
</div>
