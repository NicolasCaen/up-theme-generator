<?php
namespace UPThemeGenerator;

class ThemeGenerator {
    private $theme_data;

    public function __construct() {
        $this->theme_data = new ThemeData();
        add_action('wp_ajax_generate_theme', array($this, 'ajax_generate_theme'));
    }

    public function ajax_generate_theme() {
        check_ajax_referer('up_theme_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        $operation_type = sanitize_text_field($_POST['operation_type']);

        // Log des données reçues
        error_log('Données reçues: ' . print_r($_POST, true));

        // Pour la mise à jour, récupérer les données du thème existant
        if ($operation_type === 'update') {
            $existing_theme = sanitize_text_field($_POST['existing_theme']);
            $theme = wp_get_theme($existing_theme);
            if ($theme->exists()) {
                $_POST['theme_name'] = $theme->get('Name');
                $_POST['theme_slug'] = $existing_theme;
            }
        }

        // Valider les données
        $validation = $this->theme_data->validate_theme_data($_POST);
        if ($validation !== true) {
            wp_send_json_error('Erreur lors de la validation : ' . implode(', ', $validation));
        }

        // Assainir les données
        $theme_data = $this->theme_data->sanitize_theme_data($_POST);

        try {
            if ($operation_type === 'update') {
                $result = $this->update_theme($theme_data);
            } else {
                $result = $this->generate_theme($theme_data);
            }

            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }

            wp_send_json_success('Thème ' . ($operation_type === 'update' ? 'mis à jour' : 'généré') . ' avec succès');
        } catch (\Exception $e) {
            wp_send_json_error('Erreur lors de la génération du thème : ' . $e->getMessage());
        }
    }

    private function generate_theme($theme_data) {
        $theme_dir = WP_CONTENT_DIR . '/themes/' . $theme_data['theme_slug'];

        // Vérifier si le thème existe déjà
        if (is_dir($theme_dir)) {
            return new \WP_Error('theme_exists', 'Un thème avec ce slug existe déjà');
        }

        // Créer le dossier du thème
        if (!mkdir($theme_dir, 0755, true)) {
            return new \WP_Error('mkdir_failed', 'Impossible de créer le dossier du thème');
        }

        try {
            $this->generate_style_css($theme_dir, $theme_data);
            $this->generate_theme_json($theme_dir, $theme_data);
            $this->generate_templates($theme_dir, $theme_data);
            $this->generate_parts($theme_dir, $theme_data);
            $this->generate_patterns($theme_dir, $theme_data);
            
            // Gérer le screenshot - déplacé ici avant le return
            if (isset($_FILES['theme_screenshot']) && !empty($_FILES['theme_screenshot']['tmp_name'])) {
                error_log('Screenshot uploadé trouvé');
                $this->handle_screenshot($theme_dir, $_FILES['theme_screenshot']);
            } else {
                error_log('Téléchargement de l\'image par défaut depuis Picsum');
                $default_image_url = 'https://picsum.photos/1200/900?blur=5';
                $default_image_path = $theme_dir . '/screenshot.png';

                $image_data = file_get_contents($default_image_url);
                if ($image_data === false) {
                    error_log('Erreur lors du téléchargement de l\'image par défaut');
                    throw new \Exception('Impossible de télécharger l\'image par défaut');
                }

                if (file_put_contents($default_image_path, $image_data) === false) {
                    error_log('Erreur lors de l\'enregistrement de l\'image par défaut');
                    throw new \Exception('Impossible d\'enregistrer l\'image par défaut');
                }

                error_log('Image par défaut téléchargée et enregistrée avec succès');
            }

            return true;
        } catch (\Exception $e) {
            // En cas d'erreur, supprimer le dossier créé
            $this->recursive_remove_directory($theme_dir);
            throw $e;
        }
    }

    private function update_theme($theme_data) {
        $theme_slug = sanitize_text_field($_POST['existing_theme']);

        // Créer une sauvegarde
        $backup_result = $this->theme_data->backup_theme($theme_slug);
        if (is_wp_error($backup_result)) {
            return $backup_result;
        }

        $theme_dir = WP_CONTENT_DIR . '/themes/' . $theme_slug;

        try {
            $this->generate_style_css($theme_dir, $theme_data);
            $this->generate_theme_json($theme_dir, $theme_data);
            $this->generate_templates($theme_dir, $theme_data);
            $this->generate_parts($theme_dir, $theme_data);
            $this->generate_patterns($theme_dir, $theme_data);

            // Gérer le screenshot - ajouté ici
            if (isset($_FILES['theme_screenshot']) && !empty($_FILES['theme_screenshot']['tmp_name'])) {
                error_log('Screenshot uploadé trouvé');
                $this->handle_screenshot($theme_dir, $_FILES['theme_screenshot']);
            } else {
                error_log('Téléchargement de l\'image par défaut depuis Picsum');
                $default_image_url = 'https://picsum.photos/1200/900?blur=5';
                $default_image_path = $theme_dir . '/screenshot.png';

                $image_data = file_get_contents($default_image_url);
                if ($image_data === false) {
                    error_log('Erreur lors du téléchargement de l\'image par défaut');
                    throw new \Exception('Impossible de télécharger l\'image par défaut');
                }

                if (file_put_contents($default_image_path, $image_data) === false) {
                    error_log('Erreur lors de l\'enregistrement de l\'image par défaut');
                    throw new \Exception('Impossible d\'enregistrer l\'image par défaut');
                }

                error_log('Image par défaut téléchargée et enregistrée avec succès');
            }

            return true;
        } catch (\Exception $e) {
            // En cas d'erreur, restaurer la sauvegarde
            $this->restore_backup($backup_result, $theme_dir);
            throw $e;
        }
    }

    private function generate_style_css($theme_dir, $theme_data) {
        $content = "/*\n";
        $content .= "Theme Name: {$theme_data['theme_name']}\n";
        $content .= "Theme URI: \n";
        $content .= "Author: {$theme_data['theme_author']}\n";
        $content .= "Author URI: \n";
        $content .= "Description: {$theme_data['theme_description']}\n";
        $content .= "Version: 1.0\n";
        $content .= "License: GNU General Public License v2 or later\n";
        $content .= "License URI: http://www.gnu.org/licenses/gpl-2.0.html\n";
        $content .= "Text Domain: {$theme_data['theme_slug']}\n";
        $content .= "*/\n";

        if (!file_put_contents($theme_dir . '/style.css', $content)) {
            throw new \Exception('Impossible de créer le fichier style.css');
        }
    }

    private function generate_theme_json($theme_dir, $theme_data) {
        $theme_json = array(
            '$schema' => 'https://schemas.wp.org/trunk/theme.json',
            'version' => 3,
            'settings' => array(
                'appearanceTools' => true,
                'layout' => array(
                    'contentSize' => $theme_data['content_size'] ?: '900px',
                    'wideSize' => $theme_data['wide_size'] ?: '1340px'
                ),
                'color' => array(
                    'palette' => array(),
                    'defaultDuotone' => false,
                    'defaultGradients' => false,
                    'defaultPalette' => false
                ),
                'typography' => array(
                    'writingMode' => true,
                    'defaultFontSizes' => false,
                    'fluid' => true,
                    'fontSizes' => array()
                ),
                'spacing' => array(
                    'spacingSizes' => array(),
                    'defaultSpacingSizes' => false,
                    'units' => [
                        '%',
                        'px',
                        'em',
                        'rem',
                        'vh',
                        'vw'
                    ]
                )
            ),
            'styles' => array(
                'typography' => array(
                    'fontFamily' => 'var(--wp--preset--font-family--first)'
                ),
                'elements' => array(
                    'heading' => array(
                        'typography' => array(
                            'fontFamily' => 'var(--wp--preset--font-family--second)'
                        )
                    )
                )
            )
        );

        // Ajouter les couleurs
        if (!empty($theme_data['color_names'])) {
            foreach ($theme_data['color_names'] as $index => $name) {
                $theme_json['settings']['color']['palette'][] = array(
                    'slug' => $theme_data['color_slugs'][$index],
                    'name' => $name,
                    'color' => $theme_data['color_values'][$index]
                );
            }
        }

        // Ajouter les tailles de police
        if (!empty($theme_data['font_names'])) {
            foreach ($theme_data['font_names'] as $index => $name) {
                $font_size = array(
                    'slug' => $theme_data['font_slugs'][$index],
                    'name' => $name,
                    'size' => $theme_data['font_sizes'][$index]
                );

                // Ajouter les valeurs fluid si présentes
                if (!empty($theme_data['font_sizes_min'][$index]) && !empty($theme_data['font_sizes_max'][$index])) {
                    $font_size['fluid'] = array(
                        'min' => $theme_data['font_sizes_min'][$index],
                        'max' => $theme_data['font_sizes_max'][$index]
                    );
                }

                $theme_json['settings']['typography']['fontSizes'][] = $font_size;
            }
        }

        // Ajouter les tailles d'espacement
        if (!empty($theme_data['spacing_names'])) {
            foreach ($theme_data['spacing_names'] as $index => $name) {
                $theme_json['settings']['spacing']['spacingSizes'][] = array(
                    'slug' => $theme_data['spacing_slugs'][$index],
                    'name' => $name,
                    'size' => $theme_data['spacing_sizes'][$index]
                );
            }
        }

        if (!file_put_contents($theme_dir . '/theme.json', json_encode($theme_json, JSON_PRETTY_PRINT))) {
            throw new \Exception('Impossible de créer le fichier theme.json');
        }
    }

    private function generate_templates($theme_dir, $theme_data) {
        if (empty($theme_data['templates'])) {
            return;
        }

        $templates_dir = $theme_dir . '/templates';
        if (!is_dir($templates_dir) && !mkdir($templates_dir, 0755, true)) {
            throw new \Exception('Impossible de créer le dossier templates');
        }

        foreach ($theme_data['templates'] as $template) {
            $template_content = $this->get_template_content($template);
            if (!empty($template_content)) {
                $template_file = $templates_dir . '/' . $template . '.html';
                if (!file_put_contents($template_file, $template_content)) {
                    throw new \Exception("Impossible de créer le template {$template}");
                }
            }
        }
    }

    private function generate_parts($theme_dir, $theme_data) {
        $parts_dir = $theme_dir . '/parts';
        if (!is_dir($parts_dir) && !mkdir($parts_dir, 0755, true)) {
            throw new \Exception('Impossible de créer le dossier parts');
        }

        // Liste des parts par défaut
        $default_parts = $this->get_available_files('parts');
        foreach ($default_parts as $part) {
            $part_content = $this->get_part_content($part);
            if (!empty($part_content)) {
                $part_file = $parts_dir . '/' . $part . '.html';
                if (!file_put_contents($part_file, $part_content)) {
                    throw new \Exception("Impossible de créer le template part {$part}");
                }
            }
        }
    }

    private function generate_patterns($theme_dir, $theme_data) {
        $patterns_dir = $theme_dir . '/patterns';
        if (!is_dir($patterns_dir) && !mkdir($patterns_dir, 0755, true)) {
            throw new \Exception('Impossible de créer le dossier patterns');
        }

        $patterns = $this->get_available_files('patterns');
        foreach ($patterns as $pattern) {
            $pattern_content = $this->get_pattern_content($pattern);
            if (!empty($pattern_content)) {
                $pattern_file = $patterns_dir . '/' . $pattern . '.html';
                if (!file_put_contents($pattern_file, $pattern_content)) {
                    throw new \Exception("Impossible de créer le pattern {$pattern}");
                }
            }
        }
    }

    public function get_available_files($type) {
        $dir = UP_THEME_GENERATOR_PATH . 'resources/default-theme-tmpl/' . $type;
        $files = glob($dir . '/*.html.dist');
        $available = array();
        
        foreach ($files as $file) {
            $name = basename($file, '.html.dist');
            $available[] = $name;
        }
        
        return $available;
    }

    private function get_template_content($template) {
        $template_file = UP_THEME_GENERATOR_PATH . 'resources/default-theme-tmpl/templates/' . $template . '.html.dist';
        
        if (file_exists($template_file)) {
            return file_get_contents($template_file);
        }
        
        return '';
    }

    private function get_part_content($part) {
        $part_file = UP_THEME_GENERATOR_PATH . 'resources/default-theme-tmpl/parts/' . $part . '.html.dist';
        
        if (file_exists($part_file)) {
            return file_get_contents($part_file);
        }
        
        return '';
    }

    private function get_pattern_content($pattern) {
        $pattern_file = UP_THEME_GENERATOR_PATH . 'resources/default-theme-tmpl/patterns/' . $pattern . '.html.dist';
        
        if (file_exists($pattern_file)) {
            return file_get_contents($pattern_file);
        }
        
        return '';
    }

    private function recursive_remove_directory($dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
                        $this->recursive_remove_directory($dir . DIRECTORY_SEPARATOR . $file);
                    } else {
                        unlink($dir . DIRECTORY_SEPARATOR . $file);
                    }
                }
            }
            rmdir($dir);
        }
    }

    private function restore_backup($backup_dir, $theme_dir) {
        // Vérifier que le dossier de sauvegarde existe
        if (!is_dir($backup_dir)) {
            error_log('Dossier de sauvegarde non trouvé : ' . $backup_dir);
            throw new \Exception('Impossible de restaurer la sauvegarde : dossier non trouvé');
        }

        try {
            // Supprimer le dossier du thème actuel
            if (is_dir($theme_dir)) {
                $this->recursive_remove_directory($theme_dir);
            }

            // Créer un nouveau dossier pour le thème
            if (!mkdir($theme_dir, 0755, true)) {
                throw new \Exception('Impossible de créer le dossier du thème pour la restauration');
            }

            // Copier les fichiers de la sauvegarde
            if (!$this->recursive_copy($backup_dir, $theme_dir)) {
                throw new \Exception('Échec de la copie des fichiers de sauvegarde');
            }

            error_log('Sauvegarde restaurée avec succès');
            return true;
        } catch (\Exception $e) {
            error_log('Erreur lors de la restauration de la sauvegarde : ' . $e->getMessage());
            throw new \Exception('Échec de la restauration de la sauvegarde : ' . $e->getMessage());
        }
    }

    private function handle_screenshot($theme_dir, $file) {
        error_log('Traitement du screenshot uploadé');

        $allowed_types = array('image/jpeg', 'image/png');
        $max_size = 2 * 1024 * 1024; // 2 Mo

        // Vérifier le type MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            error_log('Type de fichier non autorisé : ' . $mime_type);
            throw new \Exception('Type de fichier non autorisé pour le screenshot');
        }

        // Vérifier la taille
        if ($file['size'] > $max_size) {
            error_log('Fichier trop volumineux : ' . $file['size']);
            throw new \Exception('Le screenshot est trop volumineux (max 2 Mo)');
        }

        // Déplacer et renommer le fichier
        $target_path = $theme_dir . '/screenshot.png';

        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            error_log('Échec du déplacement du fichier vers : ' . $target_path);
            throw new \Exception('Erreur lors du déplacement du screenshot');
        }

        error_log('Screenshot uploadé avec succès : ' . $target_path);

        // Redimensionner si nécessaire
        $this->resize_screenshot($target_path);
    }

    /**
     * Redimensionne le screenshot aux dimensions recommandées par WordPress
     */
    private function resize_screenshot($path) {
        error_log('Début du redimensionnement du screenshot : ' . $path);

        if (!function_exists('wp_get_image_editor')) {
            error_log('Fonction wp_get_image_editor non disponible');
            return false;
        }

        // Obtenir l'éditeur d'image
        $editor = wp_get_image_editor($path);

        if (is_wp_error($editor)) {
            error_log('Erreur lors de la création de l\'éditeur d\'image : ' . $editor->get_error_message());
            return false;
        }

        try {
            // Dimensions recommandées pour les screenshots de thème WordPress
            $width = 1200;
            $height = 900;

            // Redimensionner l'image
            $result = $editor->resize($width, $height, true);

            if (is_wp_error($result)) {
                error_log('Erreur lors du redimensionnement : ' . $result->get_error_message());
                return false;
            }

            // Définir la qualité de l'image
            $editor->set_quality(90);

            // Sauvegarder l'image
            $result = $editor->save($path);

            if (is_wp_error($result)) {
                error_log('Erreur lors de la sauvegarde : ' . $result->get_error_message());
                return false;
            }

            error_log('Screenshot redimensionné avec succès');
            return true;

        } catch (\Exception $e) {
            error_log('Exception lors du redimensionnement du screenshot : ' . $e->getMessage());
            return false;
        }
    }

    private function recursive_copy($src, $dst) {
        $dir = opendir($src);
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recursive_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
        return true;
    }
}
