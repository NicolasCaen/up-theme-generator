<?php
/*
Plugin Name: UP Theme Generator
Description: Générateur de thèmes FSE avec configuration
Version: 1.0
Author: GEHIN Nicolas
*/

// Empêche l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

class UPThemeGenerator {
    private $plugin_path;
    private $plugin_url;

    public function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_generate_theme', array($this, 'generate_theme'));
        add_action('wp_ajax_get_theme_data', function() {
            check_ajax_referer('up_theme_generator_nonce', 'nonce');
            
            $theme_slug = sanitize_text_field($_POST['theme_slug']);
            $theme = wp_get_theme($theme_slug);
            
            if (!$theme->exists()) {
                wp_send_json_error('Thème non trouvé');
            }
            
            $theme_dir = $theme->get_stylesheet_directory();
            error_log('Dossier du thème: ' . $theme_dir);
            
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
                'templates' => array(),
                'parts' => array()
            );
            
            // Lire theme.json
            $theme_json_path = $theme_dir . '/theme.json';
            error_log('Recherche du fichier theme.json: ' . $theme_json_path);
            
            if (file_exists($theme_json_path)) {
                $theme_json = json_decode(file_get_contents($theme_json_path), true);
                error_log('Contenu theme.json: ' . print_r($theme_json, true));
                
                // Récupérer la palette de couleurs
                if (isset($theme_json['settings']['color']['palette'])) {
                    foreach ($theme_json['settings']['color']['palette'] as $color) {
                        $theme_data['colors'][] = array(
                            'name' => $color['name'],
                            'slug' => $color['slug'],
                            'color' => $color['color']
                        );
                    }
                }
                
                // Récupérer les tailles de police
                if (isset($theme_json['settings']['typography']['fontSizes'])) {
                    foreach ($theme_json['settings']['typography']['fontSizes'] as $fontSize) {
                        $font_data = array(
                            'name' => $fontSize['name'],
                            'size' => $fontSize['size']
                        );
                        
                        // Ajouter les valeurs fluid si elles existent
                        if (isset($fontSize['fluid'])) {
                            $font_data['fluid'] = array(
                                'min' => $fontSize['fluid']['min'],
                                'max' => $fontSize['fluid']['max']
                            );
                        }
                        
                        $theme_data['typography']['fontSizes'][] = $font_data;
                    }
                }
            } else {
                error_log('Fichier theme.json non trouvé');
            }
            
            // Récupérer les templates
            $templates_dir = $theme_dir . '/templates';
            if (is_dir($templates_dir)) {
                $files = glob($templates_dir . '/*.html');
                if ($files) {
                    foreach ($files as $file) {
                        $template_name = basename($file, '.html');
                        $theme_data['templates'][] = $template_name;
                    }
                }
            }
            
            // Récupérer les parts
            $parts_dir = $theme_dir . '/parts';
            if (is_dir($parts_dir)) {
                $files = glob($parts_dir . '/*.html');
                if ($files) {
                    foreach ($files as $file) {
                        $part_name = basename($file, '.html');
                        $theme_data['parts'][] = $part_name;
                    }
                }
            }
            
            error_log('Données finales envoyées: ' . print_r($theme_data, true));
            wp_send_json_success($theme_data);
        });
    }

    public function add_admin_menu() {
        add_menu_page(
            'Générateur de Thème',
            'Générateur Thème',
            'manage_options',
            'up-theme-generator',
            array($this, 'render_admin_page'),
            'dashicons-admin-appearance',
            30
        );
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_up-theme-generator') {
            return;
        }

        wp_enqueue_style(
            'up-theme-generator-style',
            $this->plugin_url . 'assets/css/admin.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'up-theme-generator-script',
            $this->plugin_url . 'assets/js/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('up-theme-generator-script', 'upThemeGenerator', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('up_theme_generator_nonce')
        ));
    }

    public function render_admin_page() {
        // Récupération de tous les thèmes installés en excluant le dossier backups
        $all_themes = wp_get_themes(array('errors' => null));
        $themes = array();
        
        // Filtrer les thèmes pour exclure le dossier backups
        foreach ($all_themes as $theme_slug => $theme) {
            if (strpos($theme_slug, 'backups') === false) {
                $themes[$theme_slug] = $theme;
            }
        }
        
        ?>
        <div class="wrap">
            <h1>Générateur de Thème FSE</h1>
            
            <form id="theme-generator-form" class="up-theme-form">
                <div class="form-section">
                    <h2>Mode de génération</h2>
                    <table class="form-table">
                        <tr>
                            <th>Type d'opération</th>
                            <td>
                                <select name="operation_type" id="operation_type">
                                    <option value="new">Nouveau thème</option>
                                    <option value="update">Mettre à jour un thème existant</option>
                                </select>
                            </td>
                        </tr>
                        <tr id="existing_theme_row" style="display: none;">
                            <th>Thème à mettre à jour</th>
                            <td>
                                <select name="existing_theme" id="existing_theme">
                                    <?php 
                                    foreach ($themes as $theme_slug => $theme) {
                                        $theme_name = $theme->get('Name');
                                        ?>
                                        <option value="<?php echo esc_attr($theme_slug); ?>">
                                            <?php echo esc_html($theme_name . ' (' . $theme_slug . ')'); ?>
                                        </option>
                                        <?php
                                    }
                                    ?>
                                </select>
                                <p class="description">
                                    <?php echo count($themes); ?> thèmes disponibles
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="form-section">
                    <h2>Informations de base</h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="theme_name">Nom du thème</label></th>
                            <td><input type="text" id="theme_name" name="theme_name" required></td>
                        </tr>
                        <tr>
                            <th><label for="theme_slug">Slug du thème</label></th>
                            <td><input type="text" id="theme_slug" name="theme_slug" required></td>
                        </tr>
                        <tr>
                            <th><label for="theme_description">Description</label></th>
                            <td><textarea id="theme_description" name="theme_description"></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="theme_author">Auteur</label></th>
                            <td><input type="text" id="theme_author" name="theme_author"></td>
                        </tr>
                    </table>
                </div>

                <div class="form-section">
                    <h2>Configuration theme.json</h2>
                    <table class="form-table">
                        <tr>
                            <th>Palette de couleurs</th>
                            <td>
                                <div id="color-palette">
                                    <div class="color-item">
                                        <input type="text" name="color_names[]" placeholder="Nom de la couleur">
                                        <input type="text" name="color_slugs[]" placeholder="Slug de la couleur">
                                        <input type="color" name="color_values[]">
                                        <button type="button" class="remove-color">Supprimer</button>
                                    </div>
                                </div>
                                <button type="button" id="add-color" class="button">Ajouter une couleur</button>
                            </td>
                        </tr>
                        <tr>
                            <th>Configuration des polices</th>
                            <td>
                                <div id="font-sizes">
                                    <div class="font-size-item">
                                        <div class="font-size-name">
                                            <input type="text" name="font_names[]" placeholder="Nom (ex: small)">
                                        </div>
                                        <div class="font-size-values">
                                            <div class="font-size-value">
                                                <label>Défaut</label>
                                                <input type="text" name="font_sizes[]" placeholder="ex: clamp(1rem, 2vw, 1.5rem)">
                                            </div>
                                            <div class="font-size-fluid">
                                                <label>Min</label>
                                                <input type="text" name="font_sizes_min[]" placeholder="ex: 1rem">
                                            </div>
                                            <div class="font-size-fluid">
                                                <label>Max</label>
                                                <input type="text" name="font_sizes_max[]" placeholder="ex: 1.5rem">
                                            </div>
                                        </div>
                                        <button type="button" class="remove-font">Supprimer</button>
                                    </div>
                                </div>
                                <button type="button" id="add-font-size" class="button">Ajouter une taille</button>
                                <p class="description">
                                    Vous pouvez utiliser des valeurs fixes (px, rem) ou des valeurs fluides avec clamp().
                                    Pour les valeurs fluides, remplissez les champs Min et Max.
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="form-section">
                    <h2>Structure du thème</h2>
                    <table class="form-table">
                        <tr>
                            <th>Templates à inclure</th>
                            <td>
                                <label><input type="checkbox" name="templates[]" value="index" checked disabled> index.html (requis)</label><br>
                                <label><input type="checkbox" name="templates[]" value="single"> single.html</label><br>
                                <label><input type="checkbox" name="templates[]" value="archive"> archive.html</label><br>
                                <label><input type="checkbox" name="templates[]" value="page"> page.html</label><br>
                                <label><input type="checkbox" name="templates[]" value="404"> 404.html</label>
                            </td>
                        </tr>
                        <tr>
                            <th>Parts à inclure</th>
                            <td>
                                <label><input type="checkbox" name="parts[]" value="header" checked> header.html</label><br>
                                <label><input type="checkbox" name="parts[]" value="footer" checked> footer.html</label><br>
                                <label><input type="checkbox" name="parts[]" value="sidebar"> sidebar.html</label>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary">
                        <span class="action-text-new">Générer le thème</span>
                        <span class="action-text-update" style="display: none;">Mettre à jour le thème</span>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    public function generate_theme() {
        check_ajax_referer('up_theme_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $theme_data = $_POST;
        $operation_type = sanitize_text_field($theme_data['operation_type']);
        
        if ($operation_type === 'update') {
            $theme_slug = sanitize_text_field($theme_data['existing_theme']);
            $theme_dir = WP_CONTENT_DIR . '/themes/' . $theme_slug;
            
            // Vérifier si le thème existe
            if (!is_dir($theme_dir)) {
                wp_send_json_error('Le thème sélectionné n\'existe pas.');
            }
            
            // Créer le dossier de backup s'il n'existe pas
            $backup_base_dir = WP_CONTENT_DIR . '/themes/backups';
            if (!is_dir($backup_base_dir)) {
                if (!mkdir($backup_base_dir, 0755, true)) {
                    wp_send_json_error('Impossible de créer le dossier de sauvegarde.');
                }
            }
            
            // Créer un dossier daté pour cette sauvegarde
            $backup_dir = $backup_base_dir . '/' . $theme_slug . '_' . date('Y-m-d_H-i-s');
            
            // Copier le thème dans le dossier de backup
            if (!$this->recursive_copy($theme_dir, $backup_dir)) {
                wp_send_json_error('Impossible de créer une sauvegarde du thème.');
            }
            
            // Nettoyer le dossier du thème original
            $this->recursive_remove_directory($theme_dir);
            
            // Recréer le dossier du thème
            if (!mkdir($theme_dir, 0755, true)) {
                // En cas d'erreur, restaurer depuis la sauvegarde
                $this->recursive_copy($backup_dir, $theme_dir);
                wp_send_json_error('Impossible de préparer le dossier du thème.');
            }
        } else {
            $theme_dir = WP_CONTENT_DIR . '/themes/' . sanitize_file_name($theme_data['theme_slug']);
            if (!file_exists($theme_dir)) {
                mkdir($theme_dir, 0755, true);
            }
        }

        // Génération des fichiers
        try {
            $this->generate_style_css($theme_dir, $theme_data);
            $this->generate_theme_json($theme_dir, $theme_data);
            $this->generate_templates($theme_dir, $theme_data);
            $this->generate_parts($theme_dir, $theme_data);

            wp_send_json_success(array(
                'message' => $operation_type === 'update' ? 'Thème mis à jour avec succès' : 'Thème généré avec succès',
                'theme_dir' => $theme_dir
            ));
        } catch (Exception $e) {
            // En cas d'erreur, restaurer la sauvegarde si c'était une mise à jour
            if ($operation_type === 'update') {
                $this->recursive_remove_directory($theme_dir);
                $this->recursive_copy($backup_dir, $theme_dir);
            }
            wp_send_json_error($e->getMessage());
        }
    }

    private function generate_style_css($theme_dir, $theme_data) {
        $content = "/*\n";
        $content .= "Theme Name: " . esc_html($theme_data['theme_name']) . "\n";
        $content .= "Theme URI: \n";
        $content .= "Author: " . esc_html($theme_data['theme_author']) . "\n";
        $content .= "Description: " . esc_html($theme_data['theme_description']) . "\n";
        $content .= "Version: 1.0\n";
        $content .= "Requires at least: 6.0\n";
        $content .= "Tested up to: " . get_bloginfo('version') . "\n";
        $content .= "Requires PHP: 7.4\n";
        $content .= "License: GNU General Public License v2 or later\n";
        $content .= "License URI: http://www.gnu.org/licenses/gpl-2.0.html\n";
        $content .= "Text Domain: " . esc_html($theme_data['theme_slug']) . "\n";
        $content .= "*/\n";

        file_put_contents($theme_dir . '/style.css', $content);
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

        // Ajout des couleurs avec slugs personnalisés
        if (!empty($theme_data['color_names'])) {
            foreach ($theme_data['color_names'] as $index => $name) {
                if (!empty($name) && !empty($theme_data['color_values'][$index])) {
                    $theme_json['settings']['color']['palette'][] = array(
                        'slug' => !empty($theme_data['color_slugs'][$index]) 
                            ? sanitize_title($theme_data['color_slugs'][$index])
                            : sanitize_title($name),
                        'name' => $name,
                        'color' => $theme_data['color_values'][$index]
                    );
                }
            }
        }

        // Ajout des tailles de police avec support fluid
        if (!empty($theme_data['font_names'])) {
            foreach ($theme_data['font_names'] as $index => $name) {
                if (!empty($name)) {
                    $font_size = array(
                        'slug' => sanitize_title($name),
                        'name' => $name,
                        'size' => $theme_data['font_sizes'][$index],
                    );

                    // Ajout des valeurs fluid si présentes
                    if (!empty($theme_data['font_sizes_min'][$index])) {
                        $font_size['fluid'] = array(
                            'min' => $theme_data['font_sizes_min'][$index],
                            'max' => $theme_data['font_sizes_max'][$index] ?? $theme_data['font_sizes'][$index]
                        );
                    }

                    $theme_json['settings']['typography']['fontSizes'][] = $font_size;
                }
            }
        }

        file_put_contents(
            $theme_dir . '/theme.json',
            json_encode($theme_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function generate_templates($theme_dir, $theme_data) {
        if (!file_exists($theme_dir . '/templates')) {
            mkdir($theme_dir . '/templates', 0755, true);
        }

        // Template de base pour index.html
        $index_content = '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group">
    <!-- wp:query -->
    <div class="wp-block-query">
        <!-- wp:post-template -->
            <!-- wp:post-title /-->
            <!-- wp:post-content /-->
        <!-- /wp:post-template -->
    </div>
    <!-- /wp:query -->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';

        file_put_contents($theme_dir . '/templates/index.html', $index_content);

        // Génération des autres templates selon la sélection
        if (!empty($theme_data['templates'])) {
            foreach ($theme_data['templates'] as $template) {
                if ($template !== 'index') {
                    $this->generate_template_file($theme_dir, $template);
                }
            }
        }
    }

    private function generate_template_file($theme_dir, $template_name) {
        $content = '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group">';

        switch ($template_name) {
            case 'single':
                $content .= '
    <!-- wp:post-title /-->
    <!-- wp:post-content /-->';
                break;
            case '404':
                $content .= '
    <!-- wp:heading {"level":1} -->
    <h1>Page non trouvée</h1>
    <!-- /wp:heading -->';
                break;
            // Ajoutez d'autres cas selon les besoins
        }

        $content .= '
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';

        file_put_contents($theme_dir . '/templates/' . $template_name . '.html', $content);
    }

    private function generate_parts($theme_dir, $theme_data) {
        if (!file_exists($theme_dir . '/parts')) {
            mkdir($theme_dir . '/parts', 0755, true);
        }

        if (!empty($theme_data['parts'])) {
            foreach ($theme_data['parts'] as $part) {
                $this->generate_part_file($theme_dir, $part);
            }
        }
    }

    private function generate_part_file($theme_dir, $part_name) {
        $content = '';
        switch ($part_name) {
            case 'header':
                $content = '<!-- wp:group {"tagName":"header","className":"site-header"} -->
<header class="wp-block-group site-header">
    <!-- wp:site-title /-->
    <!-- wp:site-tagline /-->
    <!-- wp:navigation /-->
</header>
<!-- /wp:group -->';
                break;
            
            case 'footer':
                $content = '<!-- wp:group {"tagName":"footer","className":"site-footer"} -->
<footer class="wp-block-group site-footer">
    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">© ' . date('Y') . ' ' . get_bloginfo('name') . '</p>
    <!-- /wp:paragraph -->
</footer>
<!-- /wp:group -->';
                break;
        }

        file_put_contents($theme_dir . '/parts/' . $part_name . '.html', $content);
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

    private function recursive_remove_directory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->recursive_remove_directory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
}

// Initialisation du plugin
new UPThemeGenerator();
