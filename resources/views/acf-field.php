<div class="acf-svg-icon-picker">
    <div class="acf-svg-icon-picker__selector">
        <div class="acf-svg-icon-picker__icon">
            <?php echo $button_ui; ?>
        </div>
        <input type="hidden" readonly
            name="<?php echo esc_attr($field['name']); ?>"
            value="<?php echo esc_attr($saved_value); ?>" />
    </div>
    <?php if (! $field['required']) { ?>
        <button class="acf-svg-icon-picker__remove">
            <?php esc_html_e('Remove', 'acf-svg-icon-picker'); ?>
        </button>
    <?php } ?>
</div>
