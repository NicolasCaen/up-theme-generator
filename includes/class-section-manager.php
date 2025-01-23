<?php
namespace UPThemeGenerator;
use Exception;

class SectionManager {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_section_menu'));
        add_action('wp_ajax_save_section_preset', array($this, 'ajax_save_section_preset'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_delete_section_preset', array($this, 'ajax_delete_section_preset'));
    }

    public function add_section_menu() {
        add_submenu_page(
            'up-theme-generator',
            'Gestionnaire de Sections',
            'Styles | Sections',
            'manage_options',
            'up-theme-generator-sections',
            array($this, 'render_section_page')
        );
    }

    public function render_section_page() {
        $themes = wp_get_themes(array('errors' => null));
        $theme_slug = isset($_GET['theme']) ? sanitize_text_field($_GET['theme']) : '';
        
        // Récupérer les couleurs du theme.json si un thème est sélectionné
        $theme_colors = array();
        if (!empty($theme_slug)) {
            $theme_json_path = WP_CONTENT_DIR . '/themes/' . $theme_slug . '/theme.json';
            if (file_exists($theme_json_path)) {
                $theme_json = json_decode(file_get_contents($theme_json_path), true);
                if (isset($theme_json['settings']['color']['palette'])) {
                    $theme_colors = $theme_json['settings']['color']['palette'];
                }
            }
        }
        
        // Récupérer les polices du thème
        $theme_json_path = WP_CONTENT_DIR . '/themes/' . $theme_slug . '/theme.json';
        $theme_fonts = array();
        
        if (file_exists($theme_json_path)) {
            $theme_json = json_decode(file_get_contents($theme_json_path), true);
            if (isset($theme_json['settings']['typography']['fontFamilies'])) {
                $theme_fonts = $theme_json['settings']['typography']['fontFamilies'];
            }
        }
        
        // Passer les couleurs au template
        include UP_THEME_GENERATOR_PATH . 'templates/sections-page.php';
    }

    private function render_section_form($theme_slug) {
        ?>
        <div class="section-preset-form">
            <h2>Créer un nouveau preset de section</h2>
            <form id="section-preset-form">
                <input type="hidden" name="theme" value="<?php echo esc_attr($theme_slug); ?>">
                
                <p>
                    <label for="preset_name">Nom du preset:</label>
                    <input type="text" id="preset_name" name="preset_name" required>
                </p>

                <div class="block-types">
                    <h3>Types de blocs</h3>
                    <label><input type="checkbox" name="block_types[]" value="core/group"> Group</label>
                    <label><input type="checkbox" name="block_types[]" value="core/columns"> Columns</label>
                    <label><input type="checkbox" name="block_types[]" value="core/column"> Column</label>
                    <label><input type="checkbox" name="block_types[]" value="core/cover"> Cover</label>
                </div>

                <div class="color-settings">
                    <h3>Couleurs</h3>
                    <p>
                        <label>Couleur de fond:</label>
                        <input type="text" name="background_color" class="color-picker" 
                               value="var(--wp--preset--color--base-2)">
                    </p>
                    <p>
                        <label>Couleur du texte:</label>
                        <input type="text" name="text_color" class="color-picker" 
                               value="var(--wp--preset--color--contrast)">
                    </p>
                </div>

                <div class="element-styles">
                    <h3>Styles des éléments</h3>
                    <div class="element-group">
                        <h4>Bouton</h4>
                        <p>
                            <label>Couleur de fond:</label>
                            <input type="text" name="button_background" class="color-picker" 
                                   value="var(--wp--preset--color--contrast)">
                        </p>
                        <p>
                            <label>Couleur du texte:</label>
                            <input type="text" name="button_text" class="color-picker" 
                                   value="var(--wp--preset--color--base-2)">
                        </p>
                    </div>

                    <div class="element-group">
                        <h4>Lien</h4>
                        <p>
                            <label>Couleur du texte:</label>
                            <input type="text" name="link_text" class="color-picker" 
                                   value="var(--wp--preset--color--contrast)">
                        </p>
                    </div>

                    <div class="element-group">
                        <h4>Titre</h4>
                        <p>
                            <label>Couleur du texte:</label>
                            <input type="text" name="heading_text" class="color-picker" 
                                   value="var(--wp--preset--color--contrast-2)">
                        </p>
                    </div>
                </div>

                <p>
                    <button type="submit" class="button button-primary">Enregistrer le preset</button>
                </p>
            </form>
        </div>
        <?php
    }

    private function display_existing_presets($theme_slug) {
        include UP_THEME_GENERATOR_PATH . 'templates/sections-presets-list.php';
    }

    public function ajax_save_section_preset() {
        check_ajax_referer('up_theme_generator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        try {
            $theme_slug = sanitize_text_field($_POST['theme']);
            $preset_name = sanitize_text_field($_POST['preset_name']);
            $is_edit = isset($_POST['is_edit']);
            
            // Construire les données du preset
            $preset_data = array(
                '$schema' => 'https://schemas.wp.org/trunk/theme.json',
                'version' => 3,
                'slug' => 'section-' . sanitize_title($preset_name),
                'title' => $preset_name,
                'blockTypes' => array_map('sanitize_text_field', $_POST['block_types']),
                'styles' => array(
                    'color' => array(
                        'background' => sanitize_text_field($_POST['background_color']),
                        'text' => sanitize_text_field($_POST['text_color'])
                    )
                )
            );

            // Ajouter les styles de typographie s'ils ne sont pas sur "inherit"
            $typography = array();
            if ($_POST['text_font_family'] !== 'inherit') {
                $typography['fontFamily'] = sanitize_text_field($_POST['text_font_family']);
            }
            if ($_POST['text_font_weight'] !== 'inherit') {
                $typography['fontWeight'] = sanitize_text_field($_POST['text_font_weight']);
            }
            if ($_POST['text_font_style'] !== 'inherit') {
                $typography['fontStyle'] = sanitize_text_field($_POST['text_font_style']);
            }
            if (!empty($typography)) {
                $preset_data['styles']['typography'] = $typography;
            }

            // Gérer les éléments
            $preset_data['styles']['elements'] = array();

            // Boutons
            $button_typography = array();
            if ($_POST['button_font_family'] !== 'inherit') {
                $button_typography['fontFamily'] = sanitize_text_field($_POST['button_font_family']);
            }
            if ($_POST['button_font_weight'] !== 'inherit') {
                $button_typography['fontWeight'] = sanitize_text_field($_POST['button_font_weight']);
            }
            if ($_POST['button_font_style'] !== 'inherit') {
                $button_typography['fontStyle'] = sanitize_text_field($_POST['button_font_style']);
            }

            $preset_data['styles']['elements']['button'] = array(
                'color' => array(
                    'background' => sanitize_text_field($_POST['button_background']),
                    'text' => sanitize_text_field($_POST['button_text'])
                )
            );
            if (!empty($button_typography)) {
                $preset_data['styles']['elements']['button']['typography'] = $button_typography;
            }

            // Titres
            $heading_typography = array();
            if ($_POST['heading_font_family'] !== 'inherit') {
                $heading_typography['fontFamily'] = sanitize_text_field($_POST['heading_font_family']);
            }
            if ($_POST['heading_font_weight'] !== 'inherit') {
                $heading_typography['fontWeight'] = sanitize_text_field($_POST['heading_font_weight']);
            }
            if ($_POST['heading_font_style'] !== 'inherit') {
                $heading_typography['fontStyle'] = sanitize_text_field($_POST['heading_font_style']);
            }

            $preset_data['styles']['elements']['heading'] = array(
                'color' => array(
                    'text' => sanitize_text_field($_POST['heading_text'])
                )
            );
            if (!empty($heading_typography)) {
                $preset_data['styles']['elements']['heading']['typography'] = $heading_typography;
            }

            // Créer le dossier si nécessaire
            $preset_dir = WP_CONTENT_DIR . '/themes/' . $theme_slug . '/styles/sections/';
            if (!file_exists($preset_dir)) {
                wp_mkdir_p($preset_dir);
            }

            $file_path = $preset_dir . sanitize_file_name($preset_name) . '.json';
            
            // Vérifier si le fichier existe déjà pour une nouvelle création
            if (!$is_edit && file_exists($file_path)) {
                wp_send_json_error('Un preset avec ce nom existe déjà');
            }

            if (file_put_contents($file_path, json_encode($preset_data, JSON_PRETTY_PRINT))) {
                wp_send_json_success($is_edit ? 'Preset mis à jour avec succès' : 'Preset créé avec succès');
            } else {
                wp_send_json_error('Erreur lors de la sauvegarde du preset');
            }

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function enqueue_scripts($hook) {
        if (strpos($hook, 'up-theme-generator-sections') === false) {
            return;
        }

        // Styles existants
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('wp-block-library');
        wp_enqueue_style('wp-block-library-theme');
        
        // Récupérer le thème sélectionné
        $theme_slug = isset($_GET['theme']) ? sanitize_text_field($_GET['theme']) : '';
        
        if (!empty($theme_slug)) {
            // Charger le theme.json
            $theme_json_path = WP_CONTENT_DIR . '/themes/' . $theme_slug . '/theme.json';
            if (file_exists($theme_json_path)) {
                $theme_json = json_decode(file_get_contents($theme_json_path), true);
                $theme_url = get_theme_root_uri() . '/' . $theme_slug;
                
                $custom_css = ':root {';
                
                // Variables de couleur
                if (isset($theme_json['settings']['color']['palette'])) {
                    foreach ($theme_json['settings']['color']['palette'] as $color) {
                        $custom_css .= sprintf(
                            '--wp--preset--color--%s: %s;',
                            $color['slug'],
                            $color['color']
                        );
                    }
                }
                
                // Variables de police
                if (isset($theme_json['settings']['typography']['fontFamilies'])) {
                    foreach ($theme_json['settings']['typography']['fontFamilies'] as $font) {
                        $fontFamily = is_array($font['fontFamily']) ? implode(', ', $font['fontFamily']) : $font['fontFamily'];
                        $custom_css .= sprintf(
                            '--wp--preset--font-family--%s: %s;',
                            $font['slug'],
                            $fontFamily
                        );
                    }
                }
                
                $custom_css .= '}';
                
                // Ajouter les règles @font-face avec les URLs complètes
                if (isset($theme_json['settings']['typography']['fontFamilies'])) {
                    foreach ($theme_json['settings']['typography']['fontFamilies'] as $font) {
                        if (isset($font['fontFace'])) {
                            foreach ($font['fontFace'] as $fontFace) {
                                // Convertir les chemins relatifs en URLs complètes
                                if (isset($fontFace['src'])) {
                                    $src = is_array($fontFace['src']) ? $fontFace['src'] : array($fontFace['src']);
                                    $fontFace['src'] = array_map(function($path) use ($theme_url) {
                                        // Si le chemin commence par '/', supprimer le premier '/'
                                        $path = ltrim($path, '/');
                                        return $theme_url . '/' . $path;
                                    }, $src);
                                }
                                
                                $custom_css .= sprintf(
                                    '@font-face {
                                        font-family: "%s";
                                        src: %s;
                                        font-weight: %s;
                                        font-style: %s;
                                    }',
                                    $font['fontFamily'],
                                    $this->generate_font_src($fontFace),
                                    $fontFace['fontWeight'] ?? 'normal',
                                    $fontFace['fontStyle'] ?? 'normal'
                                );
                            }
                        }
                    }
                }
                
                wp_add_inline_style(
                    'wp-block-library',
                    $custom_css
                );
            }
        }
        
        // Vos styles
        wp_enqueue_style(
            'up-theme-generator-sections',
            plugins_url('assets/css/sections.css', dirname(__FILE__)),
            array('wp-block-library', 'wp-block-library-theme'),
            '1.0.0'
        );

        // Scripts
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script(
            'up-theme-generator-sections',
            plugins_url('assets/js/sections.js', dirname(__FILE__)),
            array('jquery', 'wp-color-picker'),
            '1.0.0',
            true
        );

        wp_localize_script(
            'up-theme-generator-sections',
            'up_theme_generator',
            array(
                'nonce' => wp_create_nonce('up_theme_generator_nonce')
            )
        );
    }

    private function generate_font_src($fontFace) {
        $srcs = array();
        
        if (isset($fontFace['src'])) {
            $src = is_array($fontFace['src']) ? $fontFace['src'] : array($fontFace['src']);
            foreach ($src as $url) {
                if (strpos($url, '.woff2') !== false) {
                    $srcs[] = sprintf("url('%s') format('woff2')", $url);
                } elseif (strpos($url, '.woff') !== false) {
                    $srcs[] = sprintf("url('%s') format('woff')", $url);
                } elseif (strpos($url, '.ttf') !== false) {
                    $srcs[] = sprintf("url('%s') format('truetype')", $url);
                } elseif (strpos($url, '.otf') !== false) {
                    $srcs[] = sprintf("url('%s') format('opentype')", $url);
                }
            }
        }
        
        return implode(', ', $srcs);
    }

    public function ajax_delete_section_preset() {
        check_ajax_referer('up_theme_generator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        if (empty($_POST['preset_name']) || empty($_POST['theme'])) {
            wp_send_json_error('Données manquantes');
        }

        $theme_slug = sanitize_text_field($_POST['theme']);
        $preset_name = sanitize_text_field($_POST['preset_name']);
        
        $preset_dir = WP_CONTENT_DIR . '/themes/' . $theme_slug . '/styles/sections/';
        $file_path = $preset_dir . sanitize_file_name($preset_name) . '.json';
        
        if (!file_exists($file_path)) {
            wp_send_json_error('Le preset n\'existe pas');
        }
        
        if (unlink($file_path)) {
            wp_send_json_success('Preset supprimé avec succès');
        } else {
            wp_send_json_error('Impossible de supprimer le preset');
        }
    }
} 