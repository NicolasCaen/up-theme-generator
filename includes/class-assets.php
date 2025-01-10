<?php
namespace UPThemeGenerator;

class Assets {
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_up-theme-generator') {
            return;
        }

        wp_enqueue_style(
            'up-theme-generator-style',
            UP_THEME_GENERATOR_URL . 'assets/css/admin.css',
            array(),
            UP_THEME_GENERATOR_VERSION
        );

        wp_enqueue_script(
            'up-theme-generator-script',
            UP_THEME_GENERATOR_URL . 'assets/js/admin.js',
            array('jquery'),
            UP_THEME_GENERATOR_VERSION,
            true
        );

        wp_localize_script('up-theme-generator-script', 'upThemeGenerator', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('up_theme_generator_nonce'),
            'pluginUrl' => UP_THEME_GENERATOR_URL
        ));
    }
}
