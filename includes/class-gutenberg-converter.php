<?php
namespace UPThemeGenerator;

class GutenbergConverter {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_convertor_menu'));
     
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

     public function enqueue_scripts($hook) {
         // Vérifier que nous sommes sur la bonne page
         if ($hook !== 'up-theme-generator_page_up-theme-generator-gutenberg-converter') {
             return;
         }  
         // Enregistrer et localiser le script
         
         wp_enqueue_script(
            'up-theme-generator-script',
            UP_THEME_GENERATOR_URL . 'assets/js/gutenberg-parser.js',
            array('jquery'),
            UP_THEME_GENERATOR_VERSION,
            true
        );
     }

    public function add_convertor_menu() {
        add_submenu_page(
            'up-theme-generator',
            'Convertisseur Gutenberg',
            'Convertir des block',
            'manage_options',
            'up-theme-generator-converter',
            array($this, 'render_gutenberg_conterter_page')
        );
    }

    public function render_gutenberg_conterter_page() {

        include UP_THEME_GENERATOR_PATH . 'templates/gutenberg-converter-page.php';
    }

    /**
     * Initialise les hooks nécessaires
     */
    public function init() {
        add_action('wp_ajax_get_theme_presets', array($this, 'get_theme_presets'));
        // ... autres hooks existants ...
    }
} 