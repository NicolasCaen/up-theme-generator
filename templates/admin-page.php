<?php 

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Générateur de Thème FSE</h1>
    
    <form id="theme-generator-form" class="up-theme-form">
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
            </table>
        </div>

        <div class="form-section">
            <h2>Configuration theme.json</h2>
            <table class="form-table">
                <tr>
                    <th>Palette de couleurs</th>
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
                    <th>Tailles de police</th>
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
            </table>
        </div>

        <div class="form-section">
            <h2>Templates</h2>
            <table class="form-table">
                <tr>
                    <th>Templates à inclure</th>
                    <td>
                        <label>
                            <input type="checkbox" name="templates[]" value="index"> Index
                        </label><br>
                        <label>
                            <input type="checkbox" name="templates[]" value="single"> Single
                        </label><br>
                        <label>
                            <input type="checkbox" name="templates[]" value="archive"> Archive
                        </label><br>
                        <label>
                            <input type="checkbox" name="templates[]" value="page"> Page
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>Template Parts</th>
                    <td>
                        <label>
                            <input type="checkbox" name="parts[]" value="header"> Header
                        </label><br>
                        <label>
                            <input type="checkbox" name="parts[]" value="footer"> Footer
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <div class="form-actions">
            <button type="submit" class="button button-primary">
                <span class="action-text-new">Générer le thème</span>
                <span class="action-text-update" style="display: none;">Mettre à jour le thème</span>
            </button>
        </div>
    </form>
</div>
