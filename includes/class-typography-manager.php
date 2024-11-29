<?php
namespace UPThemeGenerator;

class TypographyManager {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_typography_menu'));
        add_action('wp_ajax_save_typography_preset', array($this, 'ajax_save_typography_preset'));
        add_action('wp_ajax_get_theme_fonts', array($this, 'ajax_get_theme_fonts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
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
        $themes = wp_get_themes(array('errors' => null));
        $selected_theme = isset($_GET['theme']) ? sanitize_text_field($_GET['theme']) : wp_get_theme()->get_stylesheet();
        
        $fonts = $this->get_available_fonts($selected_theme);
        
        // Ajouter les scripts nécessaires
        wp_enqueue_script('up-theme-generator-typography', UP_THEME_GENERATOR_URL . 'assets/js/typography.js', array('jquery'), '1.0.0', true);
        wp_localize_script('up-theme-generator-typography', 'up_theme_generator', array(
            'nonce' => wp_create_nonce('up_theme_generator_nonce')
        ));
        
        include UP_THEME_GENERATOR_PATH . 'templates/typography-page.php';
    }

    private function get_available_fonts($theme_slug) {
        $fonts_dir = WP_CONTENT_DIR . '/themes/' . $theme_slug . '/assets/fonts';
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
        // Activer l'affichage des erreurs pour le débogage
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        
        // Log des données reçues
        error_log('Données POST reçues : ' . print_r($_POST, true));

        try {
            // Vérification du nonce et des permissions
            if (!check_ajax_referer('up_theme_generator_nonce', 'nonce', false)) {
                error_log('Erreur de nonce');
                wp_send_json_error('Nonce invalide');
                return;
            }
            
            if (!current_user_can('manage_options')) {
                error_log('Erreur de permissions');
                wp_send_json_error('Permission refusée');
                return;
            }

            // Validation des données reçues
            if (empty($_POST['theme']) || empty($_POST['preset_name']) || empty($_POST['fonts'])) {
                error_log('Données manquantes dans la requête');
                wp_send_json_error('Données manquantes');
                return;
            }

            $theme_slug = sanitize_text_field($_POST['theme']);
            $preset_name = sanitize_text_field($_POST['preset_name']);
            $fonts = array_map('sanitize_text_field', $_POST['fonts']);

            error_log('Données validées : theme=' . $theme_slug . ', preset=' . $preset_name . ', fonts=' . print_r($fonts, true));

            // Vérification des chemins
            $theme_path = WP_CONTENT_DIR . '/themes/' . $theme_slug;
            error_log('Chemin du thème : ' . $theme_path);
            
            if (!is_dir($theme_path)) {
                throw new Exception('Thème introuvable : ' . $theme_path);
            }

            $styles_path = $theme_path . '/styles';
            error_log('Chemin des styles : ' . $styles_path);
            
            if (!is_dir($styles_path)) {
                if (!mkdir($styles_path, 0755, true)) {
                    error_log('Erreur création dossier styles : ' . error_get_last()['message']);
                    throw new Exception('Impossible de créer le dossier styles');
                }
            }

            $typography_path = $styles_path . '/typography';
            error_log('Chemin de la typographie : ' . $typography_path);
            
            if (!is_dir($typography_path)) {
                if (!mkdir($typography_path, 0755, true)) {
                    error_log('Erreur création dossier typography : ' . error_get_last()['message']);
                    throw new Exception('Impossible de créer le dossier typography');
                }
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

        } catch (Exception $e) {
            error_log('Exception dans ajax_save_typography_preset : ' . $e->getMessage());
            error_log('Trace : ' . $e->getTraceAsString());
            wp_send_json_error($e->getMessage());
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

    public function ajax_get_theme_fonts() {
        check_ajax_referer('up_theme_generator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        $theme_slug = sanitize_text_field($_POST['theme']);
        $fonts = $this->get_available_fonts($theme_slug);
        
        wp_send_json_success(array(
            'fonts' => $fonts
        ));
    }

    public function enqueue_scripts($hook) {
        // Vérifier que nous sommes sur la bonne page
        if (strpos($hook, 'up-theme-generator-typography') === false) {
            return;
        }

        wp_enqueue_script(
            'up-theme-generator-typography', 
            plugins_url('assets/js/typography.js', dirname(__FILE__)), 
            array('jquery'), 
            '1.0.0', 
            true
        );

        wp_localize_script(
            'up-theme-generator-typography',
            'up_theme_generator',
            array(
                'nonce' => wp_create_nonce('up_theme_generator_nonce')
            )
        );
    }
} 