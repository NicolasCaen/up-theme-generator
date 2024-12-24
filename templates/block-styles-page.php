<div class="wrap">
    <h1>Styles de Blocs</h1>

    <!-- Sélecteur de thème -->
    <div class="theme-selector">
        <form method="get">
            <input type="hidden" name="page" value="up-theme-generator-block-styles">
            <select name="theme" id="theme-selector">
                <option value="">Sélectionner un thème...</option>
                <?php foreach ($themes as $theme_key => $theme): ?>
                    <option value="<?php echo esc_attr($theme_key); ?>" <?php selected($theme_key, $theme_slug); ?>>
                        <?php echo esc_html($theme->get('Name')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button">Charger le thème</button>
        </form>
    </div>

    <?php if (!empty($theme_slug)): ?>
        <div class="block-styles-container">
            <!-- Sélecteur de bloc -->
            <div class="block-selector">
                <h2>Sélectionner un bloc</h2>
                <select id="block-type-selector">
                    <option value="">Choisir un bloc...</option>
                    <?php
                    $blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
                    foreach ($blocks as $block_name => $block) {
                        printf(
                            '<option value="%s">%s</option>',
                            esc_attr($block_name),
                            esc_html($block->title)
                        );
                    }
                    ?>
                </select>
            </div>

            <div class="style-editor-container">
                <!-- Formulaire des styles -->
                <div class="style-form">
                    <h2>Propriétés du style</h2>
                    <form id="block-style-form">
                        <p>
                            <label for="style-name">Nom du style:</label>
                            <input type="text" id="style-name" name="style_name" required>
                        </p>

                        <!-- Section Typographie -->
                        <div class="style-section">
                            <h3>Typographie</h3>
                            <p>
                                <label>Police:</label>
                                <select name="typography_fontFamily" class="style-input">
                                    <option value="inherit">Police par défaut</option>
                                    <?php foreach ($theme_fonts as $font): ?>
                                        <option value="var(--wp--preset--font-family--<?php echo esc_attr($font['slug']); ?>)">
                                            <?php echo esc_html($font['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </p>
                            <p>
                                <label>Taille:</label>
                                <select name="typography_fontSize" class="style-input">
                                    <option value="inherit">Taille par défaut</option>
                                    <?php 
                                    if (isset($theme_json['settings']['typography']['fontSizes'])) {
                                        foreach ($theme_json['settings']['typography']['fontSizes'] as $fontSize): 
                                    ?>
                                        <option value="var(--wp--preset--font-size--<?php echo esc_attr($fontSize['slug']); ?>)">
                                            <?php echo esc_html($fontSize['name']); ?> (<?php echo esc_html($fontSize['size']); ?>)
                                        </option>
                                    <?php 
                                        endforeach;
                                    }
                                    ?>
                                </select>
                            </p>
                            <p>
                                <label>Graisse:</label>
                                <select name="typography_fontWeight" class="style-input">
                                    <option value="inherit">Par défaut</option>
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
                                <label>Style:</label>
                                <select name="typography_fontStyle" class="style-input">
                                    <option value="inherit">Par défaut</option>
                                    <option value="normal">Normal</option>
                                    <option value="italic">Italique</option>
                                </select>
                            </p>
                            <p>
                                <label>Hauteur de ligne:</label>
                                <input 
                                    type="number" 
                                    name="typography_lineHeight" 
                                    class="style-input" 
                                    min="0.1" 
                                    max="3" 
                                    step="0.1" 
                                    placeholder="ex: 1.5"
                                    value="inherit"
                                >
                                <span class="input-description">Valeur relative (1.5 = 150% de la taille de police)</span>
                            </p>
                        </div>

                        <!-- Section Couleurs -->
                        <div class="style-section">
                            <h3>Couleurs</h3>
                            <p>
                                <label>Couleur du texte:</label>
                                <select name="color_text" class="color-select style-input">
                                    <option value="inherit">Couleur par défaut</option>
                                    <?php foreach ($theme_colors as $color): ?>
                                        <option value="var(--wp--preset--color--<?php echo esc_attr($color['slug']); ?>)"
                                                data-color="<?php echo esc_attr($color['color']); ?>">
                                            <?php echo esc_html($color['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </p>
                            <p>
                                <label>Couleur de fond:</label>
                                <select name="color_background" class="color-select style-input">
                                    <option value="inherit">Couleur par défaut</option>
                                    <?php foreach ($theme_colors as $color): ?>
                                        <option value="var(--wp--preset--color--<?php echo esc_attr($color['slug']); ?>)"
                                                data-color="<?php echo esc_attr($color['color']); ?>">
                                            <?php echo esc_html($color['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </p>
                        </div>

                        <!-- Section Espacement -->
                        <div class="style-section">
                            <h3>Espacement</h3>
                            <div class="spacing-controls">
                                <label>Marge interne (padding):</label>
                                <select name="spacing_padding" class="style-input">
                                    <option value="inherit">Par défaut</option>
                                    <?php foreach ($theme_spacing as $spacing): ?>
                                        <option value="var(--wp--preset--spacing--<?php echo esc_attr($spacing['slug']); ?>)">
                                            <?php echo esc_html($spacing['name']); ?> (<?php echo esc_html($spacing['size']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="spacing-controls">
                                <label>Marge externe (margin):</label>
                                <select name="spacing_margin" class="style-input">
                                    <option value="inherit">Par défaut</option>
                                    <?php foreach ($theme_spacing as $spacing): ?>
                                        <option value="var(--wp--preset--spacing--<?php echo esc_attr($spacing['slug']); ?>)">
                                            <?php echo esc_html($spacing['name']); ?> (<?php echo esc_html($spacing['size']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="button button-primary">Enregistrer le style</button>
                            <button type="button" class="button preview-style">Prévisualiser</button>
                        </div>
                    </form>
                </div>

                <!-- Zone de prévisualisation -->
                <div class="preview-area">
                    <h2>Prévisualisation</h2>
                    <div id="block-preview-container">
                        <div class="preview-placeholder">
                            Sélectionnez un bloc pour voir la prévisualisation
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des styles sauvegardés -->
            <div class="saved-styles">
                <h2>Styles sauvegardés</h2>
                <div id="saved-styles-list">
                    <!-- Les styles seront chargés dynamiquement ici -->
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="notice notice-info">
            <p>Veuillez sélectionner un thème pour commencer à créer des styles de blocs.</p>
        </div>
    <?php endif; ?>
</div> 