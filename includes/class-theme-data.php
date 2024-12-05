<?php
namespace UPThemeGenerator;

class ThemeData {
    public function __construct() {
        add_action('wp_ajax_get_theme_data', array($this, 'ajax_get_theme_data'));
    }

    public function get_available_themes() {
        $all_themes = wp_get_themes(array('errors' => null));
        $themes = array();
   
       foreach ($all_themes as $theme_slug => $theme) {
            if (strpos($theme_slug, 'backup') === false) {
                $themes[$theme_slug] = $theme;
            }
        }
        
        return $themes;
    }

    public function ajax_get_theme_data() {
        check_ajax_referer('up_theme_generator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        $theme_slug = sanitize_text_field($_POST['theme_slug']);
        $theme_data = $this->get_theme_data($theme_slug);

        if (is_wp_error($theme_data)) {
            wp_send_json_error($theme_data->get_error_message());
        }

        wp_send_json_success($theme_data);
    }

    public function get_theme_data($theme_slug) {
        $theme = wp_get_theme($theme_slug);
        
        if (!$theme->exists()) {
            return new \WP_Error('theme_not_found', 'Thème non trouvé');
        }
        
        $theme_dir = $theme->get_stylesheet_directory();
        
        $theme_data = array(
            'basic' => array(
                'name' => $theme->get('Name'),
                'description' => $theme->get('Description'),
                'author' => $theme->get('Author'),
                'slug' => $theme_slug
            ),
            'colors' => array(),
            'typography' => array(
                'fontSizes' => array()
            ),
            'spacing' => array(
                'spacingSizes' => array()
            ),
            'templates' => array(),
            'parts' => array()
        );

        // Récupérer les données du theme.json
        $theme_json_path = $theme_dir . '/theme.json';
        if (file_exists($theme_json_path)) {
            $theme_json = json_decode(file_get_contents($theme_json_path), true);
            
            // Récupérer la palette de couleurs
            if (isset($theme_json['settings']['color']['palette'])) {
                $theme_data['colors'] = $theme_json['settings']['color']['palette'];
            }
            
            // Récupérer les tailles de police
            if (isset($theme_json['settings']['typography']['fontSizes'])) {
                $theme_data['typography']['fontSizes'] = $theme_json['settings']['typography']['fontSizes'];
            }

            // Récupérer les tailles d'espacement
            if (isset($theme_json['settings']['spacing']['spacingSizes'])) {
                $theme_data['spacing']['spacingSizes'] = $theme_json['settings']['spacing']['spacingSizes'];
            }
        }

        // Récupérer les templates
        $templates_dir = $theme_dir . '/templates';
        if (is_dir($templates_dir)) {
            $files = glob($templates_dir . '/*.html');
            if ($files) {
                foreach ($files as $file) {
                    $theme_data['templates'][] = basename($file, '.html');
                }
            }
        }

        // Récupérer les parts
        $parts_dir = $theme_dir . '/parts';
        if (is_dir($parts_dir)) {
            $files = glob($parts_dir . '/*.html');
            if ($files) {
                foreach ($files as $file) {
                    $theme_data['parts'][] = basename($file, '.html');
                }
            }
        }

        return $theme_data;
    }

    public function validate_theme_data($data) {
        $errors = array();
        $operation_type = isset($data['operation_type']) ? $data['operation_type'] : 'new';

        // Validation uniquement pour les nouveaux thèmes
        if ($operation_type === 'new') {
            if (empty($data['theme_name'])) {
                $errors[] = 'Le nom du thème est requis';
            }
            if (empty($data['theme_slug'])) {
                $errors[] = 'Le slug du thème est requis';
            }
        } else {
            // Pour la mise à jour, vérifier uniquement le thème existant
            if (empty($data['existing_theme'])) {
                $errors[] = 'Veuillez sélectionner un thème à mettre à jour';
            }
        }

        // Autres validations communes
        if (!empty($data['color_names'])) {
            foreach ($data['color_names'] as $index => $name) {
                if (empty($name) && !empty($data['color_values'][$index])) {
                    $errors[] = 'Le nom de la couleur est requis';
                }
            }
        }

        if (!empty($data['font_names'])) {
            foreach ($data['font_names'] as $index => $name) {
                if (empty($name) && !empty($data['font_sizes'][$index])) {
                    $errors[] = 'Le nom de la taille de police est requis';
                }
            }
        }

        // Validation des tailles d'espacement
        if (!empty($data['spacing_names'])) {
            foreach ($data['spacing_names'] as $index => $name) {
                if (empty($name) && !empty($data['spacing_sizes'][$index])) {
                    $errors[] = 'Le nom de la taille d\'espacement est requis';
                }
            }
        }

        return empty($errors) ? true : $errors;
    }

    public function sanitize_theme_data($theme_data) {
        $sanitized = array();

        // Sanitize basic info
        $sanitized['theme_name'] = sanitize_text_field($theme_data['theme_name']);
        $sanitized['theme_slug'] = sanitize_title($theme_data['theme_slug']);
        $sanitized['theme_description'] = sanitize_textarea_field($theme_data['theme_description']);
        $sanitized['theme_author'] = sanitize_text_field($theme_data['theme_author']);

        // Sanitize colors
        if (!empty($theme_data['color_names'])) {
            foreach ($theme_data['color_names'] as $index => $name) {
                $sanitized['color_names'][] = sanitize_text_field($name);
                $sanitized['color_slugs'][] = sanitize_title($theme_data['color_slugs'][$index]);
                $sanitized['color_values'][] = sanitize_hex_color($theme_data['color_values'][$index]);
            }
        }

        // Sanitize font sizes
        if (!empty($theme_data['font_names'])) {
            foreach ($theme_data['font_names'] as $index => $name) {
                $sanitized['font_names'][] = sanitize_text_field($name);
                $sanitized['font_sizes'][] = sanitize_text_field($theme_data['font_sizes'][$index]);
                
                if (!empty($theme_data['font_sizes_min'][$index])) {
                    $sanitized['font_sizes_min'][] = sanitize_text_field($theme_data['font_sizes_min'][$index]);
                }
                if (!empty($theme_data['font_sizes_max'][$index])) {
                    $sanitized['font_sizes_max'][] = sanitize_text_field($theme_data['font_sizes_max'][$index]);
                }
            }
        }

        // Sanitize spacing sizes
        if (!empty($theme_data['spacing_names'])) {
            foreach ($theme_data['spacing_names'] as $index => $name) {
                if (!empty($name) && !empty($theme_data['spacing_sizes'][$index])) {
                    $sanitized['spacing_names'][] = sanitize_text_field($name);
                    $sanitized['spacing_sizes'][] = sanitize_text_field($theme_data['spacing_sizes'][$index]);
                }
            }
        }

        // Sanitize templates and parts
        if (!empty($theme_data['templates'])) {
            $sanitized['templates'] = array_map('sanitize_text_field', $theme_data['templates']);
        }
        if (!empty($theme_data['parts'])) {
            $sanitized['parts'] = array_map('sanitize_text_field', $theme_data['parts']);
        }

        return $sanitized;
    }

    public function backup_theme($theme_slug) {
        $theme = wp_get_theme($theme_slug);
        if (!$theme->exists()) {
            return new \WP_Error('theme_not_found', 'Thème non trouvé');
        }

        $theme_dir = $theme->get_stylesheet_directory();
        $backup_base_dir = WP_CONTENT_DIR . '/backup/theme';
        
        if (!is_dir($backup_base_dir)) {
            if (!mkdir($backup_base_dir, 0755, true)) {
                return new \WP_Error('backup_failed', 'Impossible de créer le dossier de sauvegarde');
            }
        }

        $backup_dir = $backup_base_dir . '/' . $theme_slug . '_' . date('Y-m-d_H-i-s');
        
        if (!$this->recursive_copy($theme_dir, $backup_dir)) {
            return new \WP_Error('backup_failed', 'Échec de la sauvegarde');
        }

        return $backup_dir;
    }

    private function recursive_copy($src, $dst) {
        $dir = opendir($src);
        if (!$dir) {
            return false;
        }
        
        if (!is_dir($dst)) {
            if (!mkdir($dst, 0755, true)) {
                return false;
            }
        }
        
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $src_file = $src . '/' . $file;
            $dst_file = $dst . '/' . $file;
            
            if (is_dir($src_file)) {
                if (!$this->recursive_copy($src_file, $dst_file)) {
                    return false;
                }
            } else {
                if (!copy($src_file, $dst_file)) {
                    return false;
                }
            }
        }
        
        closedir($dir);
        return true;
    }
}
