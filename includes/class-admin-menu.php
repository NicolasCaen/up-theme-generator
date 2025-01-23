<?php
namespace UPThemeGenerator;

class AdminMenu {
    private $view;

    public function __construct() {
        $this->view = new View();
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Générateur de Thème FSE',
            'Up Generator',
            'manage_options',
            'up-theme-generator',
            array($this->view, 'render_admin_page'),
            'dashicons-art',
            30
        );
    }
}
