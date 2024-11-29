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
            'version' => 2,
            'settings' => array(
                'color' => array(
                    'palette' => array()
                ),
                'typography' => array(
                    'fluid' => true,
                    'fontSizes' => array()
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
                    'slug' => sanitize_title($name),
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
            $template_file = $templates_dir . '/' . $template . '.html';
            
            if (!file_put_contents($template_file, $template_content)) {
                throw new \Exception("Impossible de créer le template {$template}");
            }
        }
    }

    private function generate_parts($theme_dir, $theme_data) {
        if (empty($theme_data['parts'])) {
            return;
        }

        $parts_dir = $theme_dir . '/parts';
        if (!is_dir($parts_dir) && !mkdir($parts_dir, 0755, true)) {
            throw new \Exception('Impossible de créer le dossier parts');
        }

        foreach ($theme_data['parts'] as $part) {
            $part_content = $this->get_part_content($part);
            $part_file = $parts_dir . '/' . $part . '.html';
            
            if (!file_put_contents($part_file, $part_content)) {
                throw new \Exception("Impossible de créer le template part {$part}");
            }
        }
    }

    private function get_template_content($template) {
        $templates = array(
            'index' => '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group">
    <!-- wp:query -->
    <div class="wp-block-query">
        <!-- wp:post-template -->
            <!-- wp:post-title {"isLink":true} /-->
            <!-- wp:post-excerpt /-->
        <!-- /wp:post-template -->
        
        <!-- wp:query-pagination -->
            <!-- wp:query-pagination-previous /-->
            <!-- wp:query-pagination-numbers /-->
            <!-- wp:query-pagination-next /-->
        <!-- /wp:query-pagination -->
    </div>
    <!-- /wp:query -->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->',
            
            'single' => '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group">
    <!-- wp:post-title /-->
    <!-- wp:post-content /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->',
            
            'page' => '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group">
    <!-- wp:post-title /-->
    <!-- wp:post-content /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->',
            
            'archive' => '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group">
    <!-- wp:query-title {"type":"archive"} /-->
    
    <!-- wp:query -->
    <div class="wp-block-query">
        <!-- wp:post-template -->
            <!-- wp:post-title {"isLink":true} /-->
            <!-- wp:post-excerpt /-->
        <!-- /wp:post-template -->
        
        <!-- wp:query-pagination -->
            <!-- wp:query-pagination-previous /-->
            <!-- wp:query-pagination-numbers /-->
            <!-- wp:query-pagination-next /-->
        <!-- /wp:query-pagination -->
    </div>
    <!-- /wp:query -->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->'
        );

        return isset($templates[$template]) ? $templates[$template] : '';
    }

    private function get_part_content($part) {
        $parts = array(
            'header' => '<!-- wp:group {"tagName":"div","className":"site-header"} -->
<div class="wp-block-group site-header">
    <!-- wp:site-title /-->
    <!-- wp:site-tagline /-->
    <!-- wp:navigation /-->
</div>
<!-- /wp:group -->',
            
            'footer' => '<!-- wp:group {"tagName":"div","className":"site-footer"} -->
<div class="wp-block-group site-footer">
    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">
        Powered by WordPress
    </p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->'
        );

        return isset($parts[$part]) ? $parts[$part] : '';
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
        $this->recursive_remove_directory($theme_dir);
        mkdir($theme_dir, 0755, true);
        $this->theme_data->recursive_copy($backup_dir, $theme_dir);
    }
}
