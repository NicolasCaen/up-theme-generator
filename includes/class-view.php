<?php
namespace UPThemeGenerator;

class View {
    public function render_admin_page() {
        $theme_data = new ThemeData();
        $themes = $theme_data->get_available_themes();
        
        include UP_THEME_GENERATOR_PATH . 'templates/admin-page.php';
    }
}
