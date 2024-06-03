<?php
/*
Plugin Name: Advanced Custom Fields: SVG Icon Picker
Plugin URI: https://github.com/smithfield-studio/acf-svg-icon-picker
Description: Allows you to pick an icon from a predefined list
Version: 2.0.0
Author: Houke de Kwant
Author URI: https://github.com/houke/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
GitHub Plugin URI: https://github.com/smithfield-studio/acf-svg-icon-picker
GitHub Branch: main
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('acf_plugin_svg_icon_picker')) {
    return;
}

class acf_plugin_svg_icon_picker {

    public $settings = array();

    public function __construct() {
        $this->settings = array(
            'version' => '2.0.0',
            'url' => plugin_dir_url(__FILE__),
            'path' => plugin_dir_path(__FILE__),
        );

        add_action('acf/include_field_types', array($this, 'include_field_types'));
    }

    public function include_field_types($version = false) {
        include_once 'fields/acf-svg-icon-picker-v5.php';
    }
}

new acf_plugin_svg_icon_picker();
