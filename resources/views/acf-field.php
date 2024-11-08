<div class="acf-svg-icon-picker">
    <div class="acf-svg-icon-picker__selector">
        <button class="acf-svg-icon-picker__icon">
            <?php if (!empty($icon['url'])) { ?>
                <img src="<?php echo $icon['url']; ?>" alt="" />
            <?php } else { ?>
                <span>&plus;</span>
            <?php } ?>
        </button>
        <input
            type="hidden"
            name="<?php echo esc_attr($field['name'] ?? ''); ?>"
            value="<?php echo esc_attr($saved_value ?? ''); ?>"
            readonly
        />
    </div>
    <?php if (!empty($field['required'])) { ?>
        <button class="acf-svg-icon-picker__remove">
            <?php esc_html_e('Remove', 'acf-svg-icon-picker'); ?>
        </button>
    <?php } ?>
</div>
