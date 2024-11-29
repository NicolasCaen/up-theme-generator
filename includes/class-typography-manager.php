<?php
namespace UPThemeGenerator;

class TypographyManager {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_typography_menu'));
        add_action('wp_ajax_save_typography_preset', array($this, 'ajax_save_typography_preset'));
    }

    public function add_typography_menu() {
        add_submenu_page(
            'up-theme-generator',
            'Gestionnaire de Typographie',
            'Typographie',
            'manage_options',
            'up-theme-generator-typography',
            array($this, 'render_typography_page')
        );
    }

    public function render_typography_page() {
        $fonts = $this->get_available_fonts();
        $themes = wp_get_themes(array('errors' => null));
        include UP_THEME_GENERATOR_PATH . 'templates/typography-page.php';
    }

    private function get_available_fonts() {
        $current_theme = wp_get_theme();
        $fonts_dir = WP_CONTENT_DIR . '/themes/' . $current_theme->get_stylesheet() . '/assets/fonts';
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
                $font_files[] = array(
                    'name' => $file,
                    'weight' => $this->extract_font_weight($file),
                    'style' => $this->extract_font_style($file)
                );
            }
        }
        
        return $font_files;
    }

    private function extract_font_weight($filename) {
        if (preg_match('/-(\d+)(?:-italic)?\./', $filename, $matches)) {
            return $matches[1];
        }
        
        if (stripos($filename, 'VariableFont') !== false) {
            return '200 900';
        }
        
        return '400';
    }

    private function extract_font_style($filename) {
        return preg_match('/-italic\./', $filename) ? 'italic' : 'normal';
    }

    public function ajax_save_typography_preset() {
        check_ajax_referer('up_theme_generator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        $theme_slug = sanitize_text_field($_POST['theme']);
        $preset_name = sanitize_text_field($_POST['preset_name']);
        $fonts = $_POST['fonts']; // Array of selected fonts

        $theme_path = WP_CONTENT_DIR . '/themes/' . $theme_slug;
        $styles_path = $theme_path . '/styles';
        $typography_path = $styles_path . '/typography';

        // Créer les dossiers nécessaires
        if (!is_dir($styles_path)) {
            mkdir($styles_path, 0755, true);
        }
        if (!is_dir($typography_path)) {
            mkdir($typography_path, 0755, true);
        }

        // Préparer les données du preset
        $preset_data = array(
            'version' => 3,
            '$schema' => 'https://schemas.wp.org/trunk/theme.json',
            'title' => $preset_name,
            'slug' => 'typography-' . sanitize_title($preset_name),
            'settings' => array(
                'typography' => array(
                    'fontFamilies' => array()
                )
            )
        );

        // Ajouter les polices
        $slugs = array('first', 'second', 'third');
        foreach ($fonts as $index => $font) {
            if ($index >= 3) break; // Maximum 3 polices

            $font_data = $this->prepare_font_data($font, $slugs[$index]);
            $preset_data['settings']['typography']['fontFamilies'][] = $font_data;
        }

        // Sauvegarder le fichier
        $file_path = $typography_path . '/' . sanitize_title($preset_name) . '.json';
        if (file_put_contents($file_path, json_encode($preset_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
            wp_send_json_success('Preset de typographie créé avec succès');
        } else {
            wp_send_json_error('Erreur lors de la création du preset');
        }
    }

    private function prepare_font_data($font_name, $slug) {
        $current_theme = wp_get_theme();
        $font_path = WP_CONTENT_DIR . '/themes/' . $current_theme->get_stylesheet() . '/assets/fonts/' . $font_name;
        $font_files = $this->get_font_files($font_path);

        $font_data = array(
            'name' => $font_name,
            'slug' => $slug,
            'fontFamily' => $font_name . ', ' . ($slug === 'second' ? 'serif' : 'sans-serif'),
            'fontFace' => array()
        );

        foreach ($font_files as $file) {
            $font_face = array(
                'fontFamily' => $font_name,
                'fontStyle' => $file['style'],
                'fontWeight' => $file['weight'],
                'src' => array(
                    'file:./assets/fonts/' . strtolower($font_name) . '/' . $file['name']
                )
            );
            $font_data['fontFace'][] = $font_face;
        }

        return $font_data;
    }
} 