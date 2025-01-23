<?php
namespace UPThemeGenerator;

class BlockStylesManager {
    private $plugin_path;
    private $plugin_url;

    public function __construct() {
        $this->plugin_path = plugin_dir_path(dirname(__FILE__));
        $this->plugin_url = plugin_dir_url(dirname(__FILE__));

        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_block_style', array($this, 'ajax_save_block_style'));
        add_action('wp_ajax_delete_block_style', array($this, 'ajax_delete_block_style'));
        add_action('wp_ajax_get_block_example', array($this, 'ajax_get_block_example'));
    }

    public function add_menu_page() {
        add_submenu_page(
            'up-theme-generator',
            'Styles de Blocs',
            'Styles | Blocks',
            'manage_options',
            'up-theme-generator-block-styles',
            array($this, 'display_block_styles_manager')
        );
    }

    public function enqueue_scripts($hook) {
        if (strpos($hook, 'up-theme-generator-block-styles') === false) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('wp-components');
        wp_enqueue_style('wp-block-library');
        wp_enqueue_style('wp-block-library-theme');
        
        $theme_slug = isset($_GET['theme']) ? sanitize_text_field($_GET['theme']) : '';
        
        if (!empty($theme_slug)) {
            $theme_json_path = WP_CONTENT_DIR . '/themes/' . $theme_slug . '/theme.json';
            if (file_exists($theme_json_path)) {
                $theme_json = json_decode(file_get_contents($theme_json_path), true);
                $theme_url = get_theme_root_uri() . '/' . $theme_slug;
                
                $custom_css = ':root {';
                
                if (isset($theme_json['settings']['color']['palette'])) {
                    foreach ($theme_json['settings']['color']['palette'] as $color) {
                        $custom_css .= sprintf(
                            '--wp--preset--color--%s: %s;',
                            $color['slug'],
                            $color['color']
                        );
                    }
                }
                
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
                
                if (isset($theme_json['settings']['typography']['fontSizes'])) {
                    foreach ($theme_json['settings']['typography']['fontSizes'] as $fontSize) {
                        $custom_css .= sprintf(
                            '--wp--preset--font-size--%s: %s;',
                            $fontSize['slug'],
                            $fontSize['size']
                        );
                    }
                }
                
                if (isset($theme_json['settings']['spacing']['spacingSizes'])) {
                    foreach ($theme_json['settings']['spacing']['spacingSizes'] as $spacing) {
                        $custom_css .= sprintf(
                            '--wp--preset--spacing--%s: %s;',
                            $spacing['slug'],
                            $spacing['size']
                        );
                    }
                }
                
                $custom_css .= '}';
                
                wp_add_inline_style(
                    'wp-block-library',
                    $custom_css
                );
            }
        }

        wp_enqueue_style(
            'up-theme-generator-block-styles',
            $this->plugin_url . 'assets/css/block-styles.css',
            array('wp-block-library', 'wp-block-library-theme'),
            '1.0.0'
        );

        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('wp-blocks');
        wp_enqueue_script('wp-components');
        wp_enqueue_script('wp-element');
        wp_enqueue_script(
            'up-theme-generator-block-styles',
            $this->plugin_url . 'assets/js/block-styles.js',
            array('jquery', 'wp-blocks', 'wp-components', 'wp-element'),
            '1.0.0',
            true
        );

        wp_localize_script(
            'up-theme-generator-block-styles',
            'upThemeGenerator',
            array(
                'nonce' => wp_create_nonce('up_theme_generator_nonce'),
                'blocks' => $this->get_available_blocks(),
                'styleProperties' => $this->get_style_properties(),
                'savedStyles' => $this->get_saved_styles(),
                'themeColors' => $this->get_theme_colors($theme_slug),
                'themeFonts' => $this->get_theme_fonts($theme_slug),
                'themeSpacing' => $this->get_theme_spacing($theme_slug)
            )
        );
    }

    private function get_available_blocks() {
        $blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
        $block_list = array();

        foreach ($blocks as $block_name => $block) {
            $block_list[] = array(
                'name' => $block_name,
                'title' => $block->title,
                'icon' => $block->icon,
                'category' => $block->category
            );
        }

        return $block_list;
    }

    private function get_style_properties() {
        return array(
            'typography' => array(
                'label' => 'Typographie',
                'properties' => array(
                    'fontFamily' => array(
                        'type' => 'select',
                        'label' => 'Police',
                        'default' => 'inherit'
                    ),
                    'fontSize' => array(
                        'type' => 'text',
                        'label' => 'Taille',
                        'default' => ''
                    ),
                    'fontWeight' => array(
                        'type' => 'select',
                        'label' => 'Graisse',
                        'default' => 'inherit',
                        'options' => array(
                            'inherit' => 'Défaut',
                            '100' => 'Thin (100)',
                            '200' => 'Extra Light (200)',
                            '300' => 'Light (300)',
                            '400' => 'Regular (400)',
                            '500' => 'Medium (500)',
                            '600' => 'Semi Bold (600)',
                            '700' => 'Bold (700)',
                            '800' => 'Extra Bold (800)',
                            '900' => 'Black (900)'
                        )
                    ),
                    'fontStyle' => array(
                        'type' => 'select',
                        'label' => 'Style',
                        'default' => 'inherit',
                        'options' => array(
                            'inherit' => 'Défaut',
                            'normal' => 'Normal',
                            'italic' => 'Italique'
                        )
                    ),
                    'lineHeight' => array(
                        'type' => 'text',
                        'label' => 'Hauteur de ligne',
                        'default' => ''
                    )
                )
            ),
            'colors' => array(
                'label' => 'Couleurs',
                'properties' => array(
                    'textColor' => array(
                        'type' => 'color',
                        'label' => 'Couleur du texte',
                        'default' => ''
                    ),
                    'backgroundColor' => array(
                        'type' => 'color',
                        'label' => 'Couleur de fond',
                        'default' => ''
                    )
                )
            ),
            'spacing' => array(
                'label' => 'Espacement',
                'properties' => array(
                    'padding' => array(
                        'type' => 'dimensions',
                        'label' => 'Padding',
                        'default' => array(
                            'top' => '',
                            'right' => '',
                            'bottom' => '',
                            'left' => ''
                        )
                    ),
                    'margin' => array(
                        'type' => 'dimensions',
                        'label' => 'Margin',
                        'default' => array(
                            'top' => '',
                            'right' => '',
                            'bottom' => '',
                            'left' => ''
                        )
                    )
                )
            ),
            'border' => array(
                'label' => 'Bordure',
                'properties' => array(
                    'borderWidth' => array(
                        'type' => 'dimensions',
                        'label' => 'Largeur',
                        'default' => array(
                            'top' => '',
                            'right' => '',
                            'bottom' => '',
                            'left' => ''
                        )
                    ),
                    'borderColor' => array(
                        'type' => 'color',
                        'label' => 'Couleur',
                        'default' => ''
                    ),
                    'borderStyle' => array(
                        'type' => 'select',
                        'label' => 'Style',
                        'default' => 'none',
                        'options' => array(
                            'none' => 'Aucune',
                            'solid' => 'Pleine',
                            'dashed' => 'Tirets',
                            'dotted' => 'Points'
                        )
                    ),
                    'borderRadius' => array(
                        'type' => 'dimensions',
                        'label' => 'Rayon',
                        'default' => array(
                            'topLeft' => '',
                            'topRight' => '',
                            'bottomRight' => '',
                            'bottomLeft' => ''
                        )
                    )
                )
            )
        );
    }

    private function get_saved_styles() {
        return get_option('up_theme_generator_block_styles', array());
    }

    public function display_block_styles_manager() {
        $themes = wp_get_themes(array('errors' => null));
        $theme_slug = isset($_GET['theme']) ? sanitize_text_field($_GET['theme']) : '';
        
        $theme_colors = array();
        $theme_fonts = array();
        $theme_spacing = array();
        $theme_json = array();
        
        if (!empty($theme_slug)) {
            $theme_json_path = WP_CONTENT_DIR . '/themes/' . $theme_slug . '/theme.json';
            if (file_exists($theme_json_path)) {
                $theme_json = json_decode(file_get_contents($theme_json_path), true);
                
                if (isset($theme_json['settings']['color']['palette'])) {
                    $theme_colors = $theme_json['settings']['color']['palette'];
                }
                
                if (isset($theme_json['settings']['typography']['fontFamilies'])) {
                    $theme_fonts = $theme_json['settings']['typography']['fontFamilies'];
                }
                
                if (isset($theme_json['settings']['spacing']['spacingSizes'])) {
                    $theme_spacing = $theme_json['settings']['spacing']['spacingSizes'];
                }
            }
        }
        
        include $this->plugin_path . 'templates/block-styles-page.php';
    }

    public function ajax_save_block_style() {
        check_ajax_referer('up_theme_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $style_data = json_decode(stripslashes($_POST['style_data']), true);
        $styles = get_option('up_theme_generator_block_styles', array());
        $styles[$style_data['name']] = $style_data;
        
        update_option('up_theme_generator_block_styles', $styles);
        wp_send_json_success();
    }

    public function ajax_delete_block_style() {
        check_ajax_referer('up_theme_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $style_name = sanitize_text_field($_POST['style_name']);
        $styles = get_option('up_theme_generator_block_styles', array());
        
        if (isset($styles[$style_name])) {
            unset($styles[$style_name]);
            update_option('up_theme_generator_block_styles', $styles);
            wp_send_json_success();
        }
        
        wp_send_json_error('Style not found');
    }

    private function get_theme_colors($theme_slug) {
        if (empty($theme_slug)) return array();
        
        $theme_json_path = WP_CONTENT_DIR . '/themes/' . $theme_slug . '/theme.json';
        if (file_exists($theme_json_path)) {
            $theme_json = json_decode(file_get_contents($theme_json_path), true);
            return isset($theme_json['settings']['color']['palette']) 
                ? $theme_json['settings']['color']['palette'] 
                : array();
        }
        return array();
    }

    private function get_theme_fonts($theme_slug) {
        if (empty($theme_slug)) return array();
        
        $theme_json_path = WP_CONTENT_DIR . '/themes/' . $theme_slug . '/theme.json';
        if (file_exists($theme_json_path)) {
            $theme_json = json_decode(file_get_contents($theme_json_path), true);
            return isset($theme_json['settings']['typography']['fontFamilies']) 
                ? $theme_json['settings']['typography']['fontFamilies'] 
                : array();
        }
        return array();
    }

    private function get_theme_spacing($theme_slug) {
        if (empty($theme_slug)) return array();
        
        $theme_json_path = WP_CONTENT_DIR . '/themes/' . $theme_slug . '/theme.json';
        if (file_exists($theme_json_path)) {
            $theme_json = json_decode(file_get_contents($theme_json_path), true);
            return isset($theme_json['settings']['spacing']['spacingSizes']) 
                ? $theme_json['settings']['spacing']['spacingSizes'] 
                : array();
        }
        return array();
    }

    public function ajax_get_block_example() {
        check_ajax_referer('up_theme_generator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        $block_name = isset($_POST['block_name']) ? sanitize_text_field($_POST['block_name']) : '';
        
        if (empty($block_name)) {
            wp_send_json_error('Nom du bloc manquant');
        }

        $registry = \WP_Block_Type_Registry::get_instance();
        $block_type = $registry->get_registered($block_name);

        if (!$block_type) {
            wp_send_json_error('Bloc non trouvé');
        }

        // Utiliser l'exemple par défaut du bloc ou en créer un basique
        $example = isset($block_type->example) ? $block_type->example : array(
            'attributes' => array(),
            'innerContent' => array('Exemple de contenu pour ' . $block_type->title)
        );

        // Créer le bloc avec son exemple
        $block_content = new \WP_Block($block_type, $example['attributes'], array());
        $rendered_content = $block_content->render();

        wp_send_json_success($rendered_content);
    }
}