<?php
/*
Plugin Name: UP Theme Generator
Description: Générateur de thèmes FSE avec configuration
Version: 1.0
Author: GEHIN Nicolas
*/

if (!defined('ABSPATH')) {
    exit;
}

// Définition des constantes
define('UP_THEME_GENERATOR_PATH', plugin_dir_path(__FILE__));
define('UP_THEME_GENERATOR_URL', plugin_dir_url(__FILE__));
define('UP_THEME_GENERATOR_VERSION', '1.0.0');
define('UP_THEME_GENERATOR_ASSETS', UP_THEME_GENERATOR_URL . 'assets/');
define('UP_THEME_GENERATOR_RESOURCES', UP_THEME_GENERATOR_PATH . 'resources/');

// Autoloader pour les classes avec correction des noms de fichiers
spl_autoload_register(function ($class) {
    $prefix = 'UPThemeGenerator\\';
    $base_dir = UP_THEME_GENERATOR_PATH . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    
    // Correction du nom de fichier
    $file_name = 'class-' . strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $relative_class)) . '.php';
    $file = $base_dir . $file_name;

    error_log('Tentative de chargement de la classe : ' . $class);
    error_log('Recherche du fichier : ' . $file);
    error_log('Le fichier existe : ' . (file_exists($file) ? 'oui' : 'non'));

    if (file_exists($file)) {
        require $file;
        error_log('Classe chargée : ' . $class);
    }
});

// Initialisation du plugin
function up_theme_generator_init() {
    error_log('Début de l\'initialisation du plugin');
    
    // Vérification des classes avant instanciation
    $required_classes = array(
        'UPThemeGenerator\ThemeData',
        'UPThemeGenerator\View',
        'UPThemeGenerator\AdminMenu',
        'UPThemeGenerator\Assets',
        'UPThemeGenerator\ThemeGenerator'
    );

    // Vérification des fichiers requis
    $missing_files = array();
    foreach ($required_classes as $class) {
        $relative_class = str_replace('UPThemeGenerator\\', '', $class);
        $file_name = 'class-' . strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $relative_class)) . '.php';
        $file = UP_THEME_GENERATOR_PATH . 'includes/' . $file_name;
        
        if (!file_exists($file)) {
            $missing_files[] = $file_name;
        }
    }

    if (!empty($missing_files)) {
        error_log('Fichiers manquants : ' . implode(', ', $missing_files));
        add_action('admin_notices', function() use ($missing_files) {
            echo '<div class="error"><p>Fichiers manquants dans le plugin UP Theme Generator : ' . esc_html(implode(', ', $missing_files)) . '</p></div>';
        });
        return;
    }

    try {
        $theme_data = new UPThemeGenerator\ThemeData();
        $view = new UPThemeGenerator\View();
        $admin_menu = new UPThemeGenerator\AdminMenu();
        $assets = new UPThemeGenerator\Assets();
        $theme_generator = new UPThemeGenerator\ThemeGenerator();
        $fonts_manager = new UPThemeGenerator\FontsManager();
        $typography_manager = new UPThemeGenerator\TypographyManager();
        
        error_log('Toutes les classes ont été instanciées avec succès');
    } catch (\Exception $e) {
        error_log('Erreur lors de l\'initialisation : ' . $e->getMessage());
        add_action('admin_notices', function() use ($e) {
            echo '<div class="error"><p>Erreur lors de l\'initialisation du plugin UP Theme Generator : ' . esc_html($e->getMessage()) . '</p></div>';
        });
    }
}

add_action('plugins_loaded', 'up_theme_generator_init');
