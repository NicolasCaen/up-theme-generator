<?php 
// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Initialiser le ThemeGenerator avec le namespace complet
$theme_generator = new \UPThemeGenerator\ThemeGenerator();
?>
<div class="wrap">
    <h1>Générateur de Thème FSE</h1>
    
    <form id="theme-generator-form" class="up-theme-form" enctype="multipart/form-data">
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
                    </td>
                </tr>
            </table>
        </div>

        <div class="form-section">
            <h2>Informations de base</h2>
            <table class="form-table">
                <tr>
                    <th>Nom du thème</th>
                    <td>
                        <input type="text" id="theme_name" name="theme_name" required>
                    </td>
                </tr>
                <tr>
                    <th>Slug</th>
                    <td>
                        <input type="text" id="theme_slug" name="theme_slug" required>
                    </td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td>
                        <textarea id="theme_description" name="theme_description"></textarea>
                    </td>
                </tr>
                <tr>
                    <th>Auteur</th>
                    <td>
                        <input type="text" id="theme_author" name="theme_author">
                    </td>
                </tr>
                <tr>
                    <th>Capture d'écran du thème</th>
                    <td>
                        <input type="file" id="theme_screenshot" name="theme_screenshot" accept="image/png,image/jpeg">
                        <p class="description">
                            Format recommandé : PNG ou JPEG<br>
                            Dimensions recommandées : 1200 × 900 pixels<br>
                            Taille maximale : 2 Mo
                        </p>
                        <div id="screenshot-preview" style="display: none; margin-top: 10px;">
                            <img src="" alt="Aperçu" style="max-width: 300px;">
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="form-section">
            <h2>Layout</h2>
            <table class="form-table">
                <tr>
                    <th>Taille du contenu</th>
                    <td>
                        <input type="text" name="content_size" id="content_size" placeholder="ex: 900px" value="900px">
                        <p class="description">Largeur par défaut du contenu (ex: 900px)</p>
                    </td>
                </tr>
                <tr>
                    <th>Taille large</th>
                    <td>
                        <input type="text" name="wide_size" id="wide_size" placeholder="ex: 1340px" value="1340px">
                        <p class="description">Largeur maximale pour le contenu large (ex: 1340px)</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="form-section">
            <h2>Configuration theme.json</h2>
            <table class="form-table">
                <tr>
                    <th>
                        Palette de couleurs
                        <button type="button" id="load-default-colors" class="button button-secondary">Charger les valeurs par défaut</button>
                    </th>
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
                    <th>
                        Tailles de police
                        <button type="button" id="load-default-font-sizes" class="button button-secondary">Charger les valeurs par défaut</button>
                    </th>
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
                    </td>
                </tr>
                <tr>
                    <th>
                        Tailles d'espacement
                        <button type="button" id="load-default-spacing-sizes" class="button button-secondary">Charger les valeurs par défaut</button>
                    </th>
                    <td>
                        <div id="spacing-sizes">
                            <div class="spacing-size-item">
                                <div class="spacing-size-name">
                                    <input type="text" name="spacing_names[]" placeholder="Nom (ex: small)">
                                </div>
                                <div class="spacing-size-values">
                                    <div class="spacing-size-value">
                                        <label>Taille</label>
                                        <input type="text" name="spacing_sizes[]" placeholder="ex: 1rem">
                                    </div>
                                </div>
                                <button type="button" class="remove-spacing">Supprimer</button>
                            </div>
                        </div>
                        <button type="button" id="add-spacing-size" class="button">Ajouter une taille d'espacement</button>
                    </td>
                </tr>
            </table>
        </div>

        <div class="form-section">
            <h2>Templates</h2>
            <div class="template-options">
                <?php
                $available_templates = $theme_generator->get_available_files('templates');
                foreach ($available_templates as $template) :
                    $template_id = sanitize_title($template);
                ?>
                    <label>
                        <input type="checkbox" name="templates[]" value="<?php echo esc_attr($template); ?>" <?php echo ($template === 'index') ? 'checked disabled' : ''; ?>>
                        <?php echo esc_html($template); ?>
                    </label><br>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-section">
            <h2>Template Parts</h2>
            <div class="parts-options">
                <?php
                $available_parts = $theme_generator->get_available_files('parts');
                foreach ($available_parts as $part) :
                    $part_id = sanitize_title($part);
                ?>
                    <label>
                        <input type="checkbox" name="parts[]" value="<?php echo esc_attr($part); ?>" checked>
                        <?php echo esc_html($part); ?>
                    </label><br>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-section">
            <h2>Patterns</h2>
            <div class="patterns-options">
                <?php
                $available_patterns = $theme_generator->get_available_files('patterns');
                foreach ($available_patterns as $pattern) :
                    $pattern_id = sanitize_title($pattern);
                ?>
                    <label>
                        <input type="checkbox" name="patterns[]" value="<?php echo esc_attr($pattern); ?>">
                        <?php echo esc_html($pattern); ?>
                    </label><br>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-field">
            <label>
                <input type="checkbox" id="create_backup" name="create_backup" checked>
                Créer une sauvegarde avant la mise à jour
            </label>
            <p class="description">Crée une copie de sauvegarde du thème avant de le mettre à jour</p>
        </div>

        <div class="form-actions">
            <button type="submit" class="button button-primary">
                <span class="action-text-new">Générer le thème</span>
                <span class="action-text-update" style="display: none;">Mettre à jour le thème</span>
            </button>
        </div>
    </form>
</div>
