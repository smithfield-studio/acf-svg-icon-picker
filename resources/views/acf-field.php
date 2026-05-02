<?php

/**
 * @var array<string, mixed>      $field
 * @var string                    $saved_value
 * @var array<string, mixed>|null $icon
 * @var list<string>              $allowed_groups
 */

// Narrow extracted values to their expected types so the markup below stays
// declarative and PHPStan can verify the esc_*() calls.
$field_name = isset($field['name']) && is_string($field['name']) ? $field['name'] : '';
$icon_url = isset($icon['url']) && is_string($icon['url']) ? $icon['url'] : '';
?>
<div
	class="acf-svg-icon-picker"
	<?php if (!empty($allowed_groups)) { ?>
		data-allowed-groups="<?php echo esc_attr(implode(',', $allowed_groups)); ?>"
	<?php } ?>
>
	<div class="acf-svg-icon-picker__selector">
		<button
			type="button"
			class="acf-svg-icon-picker__icon"
			aria-label="<?php esc_attr_e('Choose icon', 'acf-svg-icon-picker'); ?>"
		>
			<?php if ($icon_url !== '') { ?>
				<img src="<?php echo esc_url($icon_url); ?>" alt="" />
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
	<?php if (empty($field['required'])) { ?>
		<button type="button" class="acf-svg-icon-picker__remove">
			<?php esc_html_e('Remove', 'acf-svg-icon-picker'); ?>
		</button>
	<?php } ?>
</div>
