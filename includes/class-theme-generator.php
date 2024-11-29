<?php
namespace UPThemeGenerator;

class ThemeGenerator {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // S'assurer que l'init est appelé au bon moment
        add_action('init', array($this, 'init'));
        // Ajouter l'action AJAX immédiatement
        add_action('wp_ajax_get_theme_presets', array($this, 'ajax_get_theme_presets'));
        add_action('wp_ajax_update_theme', array($this, 'ajax_update_theme'));
    }

    public function init() {
        // Autres initialisations si nécessaire
    }

    /**
     * Handler AJAX pour récupérer les presets
     */
    public function ajax_get_theme_presets() {
        // Vérification du nonce
        if (!check_ajax_referer('up_theme_generator_nonce', 'nonce', false)) {
            wp_send_json_error('Nonce invalide');
            return;
        }

        // Vérification des permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
            return;
        }

        // Récupération du thème
        $theme = isset($_POST['theme']) ? sanitize_text_field($_POST['theme']) : '';
        if (empty($theme)) {
            wp_send_json_error('Thème non spécifié');
            return;
        }

        try {
            // Chemin vers le dossier des presets
            $typography_path = get_theme_root() . '/' . $theme . '/styles/typography';
            
            // Initialiser avec le preset par défaut
            $presets = array(
                array(
                    'name' => 'Preset par défaut (Arial/Sans-serif)',
                    'slug' => 'default'
                )
            );

            // Ajouter les presets du thème s'ils existent
            if (is_dir($typography_path)) {
                $files = glob($typography_path . '/*.json');
                if ($files) {
                    foreach ($files as $file) {
                        $content = file_get_contents($file);
                        if ($content) {
                            $data = json_decode($content, true);
                            if ($data && isset($data['title'])) {
                                $presets[] = array(
                                    'name' => $data['title'],
                                    'slug' => basename($file, '.json')
                                );
                            }
                        }
                    }
                }
            }

            error_log('Presets trouvés pour ' . $theme . ': ' . print_r($presets, true));
            wp_send_json_success($presets);

        } catch (Exception $e) {
            error_log('Erreur dans ajax_get_theme_presets: ' . $e->getMessage());
            wp_send_json_error('Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Gère la mise à jour du thème via AJAX
     */
    public function ajax_update_theme() {
        try {
            // Vérification du nonce
            if (!check_ajax_referer('up_theme_generator_nonce', 'nonce', false)) {
                wp_send_json_error('Nonce invalide');
                return;
            }

            // Vérification des permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Permission refusée');
                return;
            }

            // Récupération et décodage des données JSON
            $raw_data = isset($_POST['theme_data']) ? $_POST['theme_data'] : '';
            if (empty($raw_data)) {
                wp_send_json_error('Aucune donnée reçue');
                return;
            }

            $theme_data = json_decode(stripslashes($raw_data), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error('Erreur de décodage JSON: ' . json_last_error_msg());
                return;
            }

            // Log des données reçues
            error_log('Données reçues: ' . print_r($theme_data, true));

            // Vérification des données requises
            if (empty($theme_data['existing_theme'])) {
                wp_send_json_error('Thème non spécifié');
                return;
            }

            // Mise à jour du thème
            $result = $this->update_theme(
                $theme_data['existing_theme'],
                $theme_data,
                isset($theme_data['typography_preset']) ? $theme_data['typography_preset'] : 'default'
            );

            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
                return;
            }

            wp_send_json_success('Thème mis à jour avec succès');

        } catch (Exception $e) {
            error_log('Erreur dans ajax_update_theme: ' . $e->getMessage());
            wp_send_json_error('Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    /**
     * Met à jour le thème avec les nouvelles données
     */
    private function update_theme($theme_slug, $theme_data, $typography_preset) {
        try {
            // Chemin vers le thème
            $theme_path = get_theme_root() . '/' . $theme_slug;
            
            // Mise à jour du theme.json
            $theme_json_path = $theme_path . '/theme.json';
            if (!file_exists($theme_json_path)) {
                return new WP_Error('theme_json_missing', 'Le fichier theme.json est introuvable');
            }

            // Lire le theme.json existant
            $theme_json = json_decode(file_get_contents($theme_json_path), true);
            if (!$theme_json) {
                return new WP_Error('theme_json_invalid', 'Le fichier theme.json est invalide');
            }

            // Mettre à jour uniquement les fontFamilies si un preset est spécifié
            if ($typography_preset !== 'default') {
                $preset_path = $theme_path . '/styles/typography/' . $typography_preset . '.json';
                if (file_exists($preset_path)) {
                    $preset_data = json_decode(file_get_contents($preset_path), true);
                    if ($preset_data && isset($preset_data['settings']['typography']['fontFamilies'])) {
                        // S'assurer que la structure existe
                        if (!isset($theme_json['settings'])) {
                            $theme_json['settings'] = array();
                        }
                        if (!isset($theme_json['settings']['typography'])) {
                            $theme_json['settings']['typography'] = array();
                        }
                        
                        // Ne mettre à jour que les fontFamilies
                        $theme_json['settings']['typography']['fontFamilies'] = 
                            $preset_data['settings']['typography']['fontFamilies'];
                    }
                }
            }

            // Sauvegarder les modifications
            if (!file_put_contents($theme_json_path, json_encode($theme_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                return new WP_Error('save_failed', 'Impossible de sauvegarder le fichier theme.json');
            }

            return true;

        } catch (Exception $e) {
            error_log('Erreur dans update_theme: ' . $e->getMessage());
            return new WP_Error('update_failed', $e->getMessage());
        }
    }
}

// Initialiser l'instance
function up_theme_generator() {
    return ThemeGenerator::get_instance();
}

// S'assurer que l'instance est créée
add_action('plugins_loaded', 'up_theme_generator');
