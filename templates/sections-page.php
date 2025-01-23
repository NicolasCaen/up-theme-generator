<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Gestionnaire de Sections</h1>
    
    <!-- Sélecteur de thème -->
    <form method="get" action="">
        <input type="hidden" name="page" value="up-theme-generator-sections">
        <select name="theme" onchange="this.form.submit()">
            <option value="">Sélectionner un thème</option>
            <?php foreach ($themes as $theme): ?>
                <option value="<?php echo esc_attr($theme->get_stylesheet()); ?>" 
                        <?php selected($theme->get_stylesheet(), $theme_slug); ?>>
                    <?php echo esc_html($theme->get('Name')); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
        <!-- Liste des presets existants -->
        <?php include UP_THEME_GENERATOR_PATH . 'templates/sections-presets-list.php'; ?>
    <?php if (!empty($theme_slug)): ?>
        <div class="section-manager-layout">
            <!-- Colonne de gauche : Formulaire -->
            <div class="section-form-column">
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
                            <h3>Générals</h3>
                        <div class="element-group">
                            <h4>Couleurs</h4>
                            <p>
                                <label>Couleur de fond:</label>
                                <select name="background_color" class="color-select" data-default="var(--wp--preset--color--base-2)">
                                    <?php foreach ($theme_colors as $color): ?>
                                        <option value="var(--wp--preset--color--<?php echo esc_attr($color['slug']); ?>)"
                                                data-color="<?php echo esc_attr($color['color']); ?>">
                                            <?php echo esc_html($color['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </p>
                            <p>
                                <label>Couleur du texte:</label>
                                <select name="text_color" class="color-select" data-default="var(--wp--preset--color--contrast)">
                                    <?php foreach ($theme_colors as $color): ?>
                                        <option value="var(--wp--preset--color--<?php echo esc_attr($color['slug']); ?>)"
                                                data-color="<?php echo esc_attr($color['color']); ?>">
                                            <?php echo esc_html($color['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </p>
                            <div class="font-settings">
                                <p>
                                    <label>Police du texte:</label>
                                    <select name="text_font_family" class="font-select" data-default="inherit">
                                        <option value="inherit">Police par défaut</option>
                                        <?php foreach ($theme_fonts as $font): ?>
                                            <option value="var(--wp--preset--font-family--<?php echo esc_attr($font['slug']); ?>)">
                                                <?php echo esc_html($font['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>
                                <div class="font-additional-options">
                                        <p>
                                        <label for="text_font_weight">Font Weight:</label>
                                        <select name="text_font_weight" class="font-weight-select">
                                            <option value="inherit">Graisse par défaut</option>
                                            <option value="100">Thin (100)</option>
                                            <option value="200">Extra Light (200)</option>
                                            <option value="300">Light (300)</option>
                                            <option value="400">Regular (400)</option>
                                            <option value="500">Medium (500)</option>
                                            <option value="600">Semi Bold (600)</option>
                                            <option value="700">Bold (700)</option>
                                            <option value="800">Extra Bold (800)</option>
                                            <option value="900">Black (900)</option>
                                        </select>
                                        </p>
                                        <p>
                                        <label for="text_font_style">Font-style:</label>
                                        <select name="text_font_style" class="font-style-select">
                                            <option value="inherit">Style par défaut</option>
                                            <option value="normal">Normal</option>
                                            <option value="italic">Italique</option>
                                        </select>
                                        <p>
                                    </div>
                            </div>
                                        </div>
                        </div>

                        <div class="element-styles">
                            <h3>Styles des éléments</h3>
                            <div class="element-group">
                                <h4>Titre</h4>
                                <p>
                                    <label>Couleur du texte:</label>
                                    <select name="heading_text" class="color-select" data-default="var(--wp--preset--color--contrast-2)">
                                        <?php foreach ($theme_colors as $color): ?>
                                            <option value="var(--wp--preset--color--<?php echo esc_attr($color['slug']); ?>)"
                                                    data-color="<?php echo esc_attr($color['color']); ?>">
                                                <?php echo esc_html($color['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>
                                <p>
                                    <label>Background:</label>
                                    <select name="heading_bg" class="color-select" data-default="tranparent">
                                        <option value="transparent">Transparent</option>
                                        <?php foreach ($theme_colors as $color): ?>
                                            <option value="var(--wp--preset--color--<?php echo esc_attr($color['slug']); ?>)"
                                                    data-color="<?php echo esc_attr($color['color']); ?>">
                                                <?php echo esc_html($color['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>
                                <div class="font-settings">
                                    <p>
                                        <label>Police du titre:</label>
                                        <select name="heading_font_family" class="font-select" data-default="inherit">
                                            <option value="inherit">Police par défaut</option>
                                            <?php foreach ($theme_fonts as $font): ?>
                                                <option value="var(--wp--preset--font-family--<?php echo esc_attr($font['slug']); ?>)">
                                                    <?php echo esc_html($font['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </p>
                                    <div class="font-additional-options">
                                        <p>
                                        <label for="heading_font_weight">Font Weight:</label>
                                        <select name="heading_font_weight" class="font-weight-select">
                                            <option value="inherit">Graisse par défaut</option>
                                            <option value="100">Thin (100)</option>
                                            <option value="200">Extra Light (200)</option>
                                            <option value="300">Light (300)</option>
                                            <option value="400">Regular (400)</option>
                                            <option value="500">Medium (500)</option>
                                            <option value="600">Semi Bold (600)</option>
                                            <option value="700">Bold (700)</option>
                                            <option value="800">Extra Bold (800)</option>
                                            <option value="900">Black (900)</option>
                                        </select>
                                        </p>
                                        <p>
                                        <label for="heading_font_style">Font-style:</label>
                                        <select name="heading_font_style" class="font-style-select">
                                            <option value="inherit">Style par défaut</option>
                                            <option value="normal">Normal</option>
                                            <option value="italic">Italique</option>
                                        </select>
                                        <p>
                                    </div>
                                </div>
                            </div>
                            <div class="element-group">
                                <h4>Lien</h4>
                                <p>
                                    <label>Couleur du texte | hover:</label>
                                    <select name="link_text" class="color-select" data-default="var(--wp--preset--color--contrast)">
                                        <?php foreach ($theme_colors as $color): ?>
                                            <option value="var(--wp--preset--color--<?php echo esc_attr($color['slug']); ?>)"
                                                    data-color="<?php echo esc_attr($color['color']); ?>">
                                                <?php echo esc_html($color['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="link_hover_text" class="color-select" data-default="var(--wp--preset--color--contrast)">
                                        <?php foreach ($theme_colors as $color): ?>
                                            <option value="var(--wp--preset--color--<?php echo esc_attr($color['slug']); ?>)"
                                                    data-color="<?php echo esc_attr($color['color']); ?>">
                                                <?php echo esc_html($color['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>

                                <p>
                                    <label><input type="checkbox" name="link_underline" value="1"> Souligner le lien</label>
                                </p>
                            </div>
                            <div class="element-group">
                                <h4>Bouton</h4>
                                <p>
                                    <label>Couleur de fond | Hover :</label>
                                    <select name="button_background" class="color-select" data-default="var(--wp--preset--color--contrast)">
                                        <?php foreach ($theme_colors as $color): ?>
                                            <option value="var(--wp--preset--color--<?php echo esc_attr($color['slug']); ?>)"
                                                    data-color="<?php echo esc_attr($color['color']); ?>">
                                                <?php echo esc_html($color['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="button_background_hover" class="color-select" data-default="var(--wp--preset--color--contrast)">
                                        <?php foreach ($theme_colors as $color): ?>
                                            <option value="var(--wp--preset--color--<?php echo esc_attr($color['slug']); ?>)"
                                                    data-color="<?php echo esc_attr($color['color']); ?>">
                                                <?php echo esc_html($color['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>
                                <p>
                                    <label>Couleur du texte | Hover:</label>
                                    <select name="button_text" class="color-select" data-default="var(--wp--preset--color--base-2)">
                                        <?php foreach ($theme_colors as $color): ?>
                                            <option value="var(--wp--preset--color--<?php echo esc_attr($color['slug']); ?>)"
                                                    data-color="<?php echo esc_attr($color['color']); ?>">
                                                <?php echo esc_html($color['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="button_text_hover" class="color-select" data-default="var(--wp--preset--color--base-2)">
                                        <?php foreach ($theme_colors as $color): ?>
                                            <option value="var(--wp--preset--color--<?php echo esc_attr($color['slug']); ?>)"
                                                    data-color="<?php echo esc_attr($color['color']); ?>">
                                                <?php echo esc_html($color['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>
                                <div class="font-settings">
                                    <p>
                                        <label>Police du bouton:</label>
                                        <select name="button_font_family" class="font-select" data-default="inherit">
                                            <option value="inherit">Police par défaut</option>
                                            <?php foreach ($theme_fonts as $font): ?>
                                                <option value="var(--wp--preset--font-family--<?php echo esc_attr($font['slug']); ?>)">
                                                    <?php echo esc_html($font['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </p>
                                    <div class="font-additional-options">
                                        <p>
                                        <label for="button_font_weight">Font Weight:</label>
                                        <select name="button_font_weight" class="font-weight-select">
                                            <option value="inherit">Graisse par défaut</option>
                                            <option value="100">Thin (100)</option>
                                            <option value="200">Extra Light (200)</option>
                                            <option value="300">Light (300)</option>
                                            <option value="400">Regular (400)</option>
                                            <option value="500">Medium (500)</option>
                                            <option value="600">Semi Bold (600)</option>
                                            <option value="700">Bold (700)</option>
                                            <option value="800">Extra Bold (800)</option>
                                            <option value="900">Black (900)</option>
                                        </select>
                                        </p>
                                        <p>
                                        <label for="button_font_style">Font-style:</label>
                                        <select name="button_font_style" class="font-style-select">
                                            <option value="inherit">Style par défaut</option>
                                            <option value="normal">Normal</option>
                                            <option value="italic">Italique</option>
                                        </select>
                                        <p>
                                    </div>
                                </div>
                            </div>



 
                        </div>

                        <p>
                            <button type="submit" class="button button-primary">Enregistrer le preset</button>
                        </p>
                    </form>
                </div>
            </div>

            <!-- Colonne de droite : Aperçu Gutenberg -->
            <div class="section-preview-column">
                <div class="section-preview-gutenberg">
                    <h2>Aperçu du bloc</h2>
                    <div class="preview-toolbar">
                        <select id="preview-block-type">
                            <option value="core/group">Group</option>
                            <option value="core/columns">Columns</option>
                            <option value="core/cover">Cover</option>
                        </select>
                        <button type="button" class="button" id="refresh-preview">Rafraîchir l'aperçu</button>
                    </div>
                    <div class="preview-editor">
                        <div class="editor-styles-wrapper">
                            <div id="block-preview-container">
                                <!-- L'aperçu du bloc sera injecté ici via JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="notice notice-warning">
            <p>Veuillez sélectionner un thème pour voir les presets de section disponibles.</p>
        </div>
    <?php endif; ?>
</div> 