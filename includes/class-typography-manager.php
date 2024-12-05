<?php
namespace UPThemeGenerator;
use Exception;
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
        $selected_theme = isset($_GET['theme']) ? sanitize_text_field($_GET['theme']) : '';
        
        $fonts = $this->get_available_fonts($selected_theme);
        
        // Ajouter les scripts nécessaires
        wp_enqueue_script('up-theme-generator-typography', UP_THEME_GENERATOR_URL . 'assets/js/typography.js', array('jquery'), '1.0.0', true);
        wp_localize_script('up-theme-generator-typography', 'up_theme_generator', array(
            'nonce' => wp_create_nonce('up_theme_generator_nonce')
        ));
        
        // Gérer l'application du preset
        if (isset($_POST['apply_preset']) && check_admin_referer('apply_typography_preset', 'typography_preset_nonce')) {
            $this->handle_typography_preset($_POST['preset_file'], $selected_theme);
        }
        
        echo '<div class="wrap">';
        echo '<h1>Gestionnaire de Typographie</h1>';
        
        // Afficher le sélecteur de thème
        echo '<form method="get" action="">';
        echo '<input type="hidden" name="page" value="up-theme-generator-typography">';
        echo '<select name="theme" onchange="this.form.submit()">';
        echo '<option value="">Sélectionner un thème</option>';
        foreach ($themes as $theme) {
            $selected = ($theme->get_stylesheet() === $selected_theme) ? 'selected' : '';
            echo '<option value="' . esc_attr($theme->get_stylesheet()) . '" ' . $selected . '>';
            echo esc_html($theme->get('Name'));
            echo '</option>';
        }
        echo '</select>';
        echo '</form>';
        
        settings_errors('typography_presets');
        
        // Afficher les presets disponibles seulement si un thème est sélectionné
        if (!empty($selected_theme)) {
            $preset_dir = WP_CONTENT_DIR . '/themes/' . $selected_theme . '/styles/typography/';
            $preset_files = glob($preset_dir . '*.json');
            
            echo '<h2>Presets de Typographie</h2>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Nom du Preset</th>';
            echo '<th>First Font</th>';
            echo '<th>Second Font</th>';
            echo '<th>Third Font</th>';
            echo '<th>Action</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            if (empty($preset_files)) {
                echo '<tr><td colspan="5">Aucun preset de typographie trouvé pour ce thème.</td></tr>';
            } else {
                foreach ($preset_files as $file) {
                    $preset_data = json_decode(file_get_contents($file), true);
                    $preset_name = basename($file, '.json');
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($preset_data['title'] ?? $preset_name) . '</td>';
                    
                    // Afficher les détails des polices
                    foreach (['first', 'second', 'third'] as $font_key) {
                        echo '<td>';
                        if (isset($preset_data['settings']['typography']['fontFamilies'])) {
                            foreach ($preset_data['settings']['typography']['fontFamilies'] as $font) {
                                if ($font['slug'] === $font_key) {
                                    echo '<strong>Famille:</strong> ' . esc_html($font['fontFamily']) . '<br>';
                                    echo '<strong>Nom:</strong> ' . esc_html($font['name']) . '<br>';
                                    if (!empty($font['fontFace'])) {
                                        echo '<strong>Variantes:</strong> ' . count($font['fontFace']);
                                    }
                                }
                            }
                        }
                        echo '</td>';
                    }
                    
                    // Bouton d'action
                    echo '<td>';
                    echo '<form method="post">';
                    echo '<input type="hidden" name="preset_file" value="' . esc_attr($preset_name) . '">';
                    wp_nonce_field('apply_typography_preset', 'typography_preset_nonce');
                    echo '<input type="submit" name="apply_preset" class="button button-primary" value="Appliquer">';
                    echo '</form>';
                    echo '</td>';
                    
                    echo '</tr>';
                }
            }
            
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<div class="notice notice-warning"><p>Veuillez sélectionner un thème pour voir les presets de typographie disponibles.</p></div>';
        }
        
        // Afficher le reste de la page
        include UP_THEME_GENERATOR_PATH . 'templates/typography-page.php';
        echo '</div>';
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

    private function get_font_weight_value($weight_name) {
        // Table de correspondance des poids de police
        $weight_map = array(
            'thin' => '100',
            'hairline' => '100',
            'extralight' => '200',
            'ultralight' => '200',
            'light' => '300',
            'regular' => '400',
            'normal' => '400',
            'medium' => '500',
            'semibold' => '600',
            'demibold' => '600',
            'bold' => '700',
            'extrabold' => '800',
            'ultrabold' => '800',
            'black' => '900',
            'heavy' => '900',
            'extrablack' => '950',
            'ultrablack' => '950'
        );

        // Convertir en minuscules et supprimer les espaces
        $weight_name = strtolower(str_replace(' ', '', $weight_name));
        
        return isset($weight_map[$weight_name]) ? $weight_map[$weight_name] : '400';
    }

    private function get_font_files($font_path) {
        if (!is_dir($font_path)) {
            error_log('Dossier de police introuvable : ' . $font_path);
            return array();
        }

        $files = scandir($font_path);
        if ($files === false) {
            error_log('Impossible de lire le dossier : ' . $font_path);
            return array();
        }

        // Filtrer les fichiers de police
        $font_files = array_diff($files, array('.', '..'));
        
        // Trier les fichiers par poids et style
        $sorted_files = array();
        foreach ($font_files as $file) {
            // Vérifier d'abord le format numérique
            if (preg_match('/-(\d+)(-italic)?\.woff2$/', $file, $matches)) {
                $weight = $matches[1];
                $style = isset($matches[2]) ? 'italic' : 'normal';
            } 
            // Ensuite vérifier les noms de poids
            elseif (preg_match('/-([\w]+)(-italic)?\.woff2$/i', $file, $matches)) {
                $weight = $this->get_font_weight_value($matches[1]);
                $style = isset($matches[2]) ? 'italic' : 'normal';
            } 
            // Pour les fichiers sans indication de poids
            else {
                $weight = '400';
                $style = 'normal';
            }

            $sorted_files[] = array(
                'file' => $file,
                'weight' => $weight,
                'style' => $style
            );
        }

        return $sorted_files;
    }

    private function prepare_font_data($font_name, $slug) {
        try {
            // Construire le chemin vers les polices du thème actif
            $theme_slug = $_POST['theme'];
            $font_path = WP_CONTENT_DIR . '/themes/' . $theme_slug . '/assets/fonts/' . $font_name;
            error_log('Recherche des polices dans : ' . $font_path);

            $font_files = $this->get_font_files($font_path);
            error_log('Fichiers de police trouvés : ' . print_r($font_files, true));

            $font_faces = array();
            foreach ($font_files as $font_file) {
                $font_faces[] = array(
                    'fontFamily' => $font_name,
                    'fontStyle' => $font_file['style'],
                    'fontWeight' => $font_file['weight'],
                    'src' => array(
                        'file:./assets/fonts/' . $font_name . '/' . $font_file['file']
                    )
                );
            }

            // Si aucun fichier trouvé, utiliser une configuration par défaut
            if (empty($font_faces)) {
                $font_faces[] = array(
                    'fontFamily' => $font_name,
                    'fontStyle' => 'normal',
                    'fontWeight' => '400',
                    'src' => array(
                        'file:./assets/fonts/' . $font_name . '/' . $font_name . '.woff2'
                    )
                );
            }

            return array(
                'fontFamily' => $font_name . ', sans-serif',
                'name' => ucfirst($font_name),
                'slug' => $slug,
                'fontFace' => $font_faces
            );
        } catch (Exception $e) {
            error_log('Erreur dans prepare_font_data : ' . $e->getMessage());
            throw $e;
        }
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

    private function handle_typography_preset($preset_name, $theme_slug) {
        try {
            $preset_file = WP_CONTENT_DIR . '/themes/' . $theme_slug . '/styles/typography/' . $preset_name . '.json';
            
            if (!file_exists($preset_file)) {
                throw new Exception('Preset non trouvé');
            }

            // Lire le preset
            $preset_data = json_decode(file_get_contents($preset_file), true);
            
            // Lire le theme.json actuel
            $theme_json_path = WP_CONTENT_DIR . '/themes/' . $theme_slug . '/theme.json';
            $theme_json = json_decode(file_get_contents($theme_json_path), true);
            
            // Mettre à jour uniquement les fontFamilies
            if (!isset($theme_json['settings'])) {
                $theme_json['settings'] = array();
            }
            if (!isset($theme_json['settings']['typography'])) {
                $theme_json['settings']['typography'] = array();
            }
            
            $theme_json['settings']['typography']['fontFamilies'] = 
                $preset_data['settings']['typography']['fontFamilies'];
            
            // Sauvegarder le theme.json
            if (file_put_contents($theme_json_path, json_encode($theme_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                add_settings_error('typography_presets', 'preset_applied', 'Preset appliqué avec succès', 'success');
            } else {
                throw new Exception('Impossible de sauvegarder le theme.json');
            }
        } catch (Exception $e) {
            add_settings_error('typography_presets', 'preset_error', 
                'Erreur lors de l\'application du preset: ' . $e->getMessage(), 'error');
        }
    }
} 