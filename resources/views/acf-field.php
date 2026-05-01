<div class="acf-svg-icon-picker">
	<div class="acf-svg-icon-picker__selector">
		<button
			type="button"
			class="acf-svg-icon-picker__icon"
			aria-label="<?php esc_attr_e( 'Choose icon', 'acf-svg-icon-picker' ); ?>"
		>
			<?php if ( ! empty( $icon['url'] ) ) { ?>
				<img src="<?php echo esc_url( $icon['url'] ); ?>" alt="" />
			<?php } else { ?>
				<span aria-hidden="true">&plus;</span>
			<?php } ?>
		</button>
		<input
			type="hidden"
			name="<?php echo esc_attr( $field['name'] ?? '' ); ?>"
			value="<?php echo esc_attr( $saved_value ?? '' ); ?>"
			readonly
		/>
	</div>
	<?php if ( empty( $field['required'] ) ) { ?>
		<button type="button" class="acf-svg-icon-picker__remove">
			<?php esc_html_e( 'Remove', 'acf-svg-icon-picker' ); ?>
		</button>
	<?php } ?>
</div>
