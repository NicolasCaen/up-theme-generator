<?php
namespace UPThemeGenerator;

class FontsManager {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_fonts_menu'));
        add_action('wp_ajax_add_font_to_theme', array($this, 'ajax_add_font_to_theme'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts($hook) {
        // Vérifier que nous sommes sur la bonne page
        if ($hook !== 'up-theme-generator_page_up-theme-generator-fonts') {
            return;
        }

        // Enregistrer et localiser le script
        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'upThemeGenerator', array(
            'nonce' => wp_create_nonce('up_theme_generator_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
    }

    public function add_fonts_menu() {
        add_submenu_page(
            'up-theme-generator',
            'Gestionnaire de Polices',
            'Polices',
            'manage_options',
            'up-theme-generator-fonts',
            array($this, 'render_fonts_page')
        );
    }

    public function render_fonts_page() {
        $fonts = $this->get_available_fonts();
        include UP_THEME_GENERATOR_PATH . 'templates/fonts-page.php';
    }

    private function get_available_fonts() {
        $fonts_dir = UP_THEME_GENERATOR_RESOURCES . 'fonts';
        $fonts = array();

        if (is_dir($fonts_dir)) {
            $font_folders = array_diff(scandir($fonts_dir), array('.', '..'));
            
            foreach ($font_folders as $folder) {
                $font_path = $fonts_dir . '/' . $folder;
                if (is_dir($font_path)) {
                    $fonts[] = array(
                        'name' => $folder,
                        'files' => $this->get_font_files($font_path),
                        'path' => $font_path
                    );
                }
            }
        }

        return $fonts;
    }

    private function get_font_files($font_path) {
        $files = array_diff(scandir($font_path), array('.', '..'));
        $font_files = array();
        
        foreach ($files as $file) {
            if (in_array(pathinfo($file, PATHINFO_EXTENSION), array('ttf', 'otf', 'woff', 'woff2'))) {
                $font_files[] = $file;
            }
        }
        
        return $font_files;
    }

    public function ajax_add_font_to_theme() {
        check_ajax_referer('up_theme_generator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        $theme_slug = sanitize_text_field($_POST['theme']);
        $font_name = sanitize_text_field($_POST['font']);

        $source_path = UP_THEME_GENERATOR_RESOURCES . 'fonts/' . $font_name;
        $theme_path = WP_CONTENT_DIR . '/themes/' . $theme_slug;
        $destination_path = $theme_path . '/assets/fonts/' . $font_name;

        if (!is_dir($source_path)) {
            wp_send_json_error('Police introuvable');
        }

        if (!is_dir($theme_path)) {
            wp_send_json_error('Thème introuvable');
        }

        // Créer le dossier fonts s'il n'existe pas
        if (!is_dir($theme_path . '/assets')) {
            mkdir($theme_path . '/assets');
        }
        if (!is_dir($theme_path . '/assets/fonts')) {
            mkdir($theme_path . '/assets/fonts');
        }

        // Copier le dossier de la police
        try {
            $this->copy_directory($source_path, $destination_path);
            wp_send_json_success('Police ajoutée avec succès');
        } catch (\Exception $e) {
            wp_send_json_error('Erreur lors de la copie : ' . $e->getMessage());
        }
    }

    private function copy_directory($src, $dst) {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        
        $dir = opendir($src);
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $src_file = $src . '/' . $file;
                $dst_file = $dst . '/' . $file;
                
                if (is_dir($src_file)) {
                    $this->copy_directory($src_file, $dst_file);
                } else {
                    copy($src_file, $dst_file);
                }
            }
        }
        closedir($dir);
    }
} 