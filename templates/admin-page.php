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

        <div class="form-section">
            <h2>Typographie</h2>
            <table class="form-table">
                <tr>
                    <th>Preset de typographie</th>
                    <td>
                        <select name="typography_preset" id="typography_preset">
                            <option value="default">Preset par défaut (Arial/Sans-serif)</option>
                            <!-- Les autres options seront chargées dynamiquement -->
                        </select>
                        <p class="description">Sélectionnez un preset de typographie existant ou utilisez le preset par défaut</p>
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

<script>
jQuery(document).ready(function($) {
    // Code existant...

    function loadTypographyPresets(selectedTheme) {
        console.log('Chargement des presets pour le thème:', selectedTheme); // Debug
        
        var $presetSelect = $('#typography_preset');
        
        if (selectedTheme) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_theme_presets',
                    theme: selectedTheme,
                    nonce: '<?php echo wp_create_nonce('up_theme_generator_nonce'); ?>'
                },
                success: function(response) {
                    console.log('Réponse presets:', response); // Debug
                    $presetSelect.find('option:not(:first)').remove();
                    
                    if (response.success && response.data) {
                        response.data.forEach(function(preset) {
                            $presetSelect.append(
                                $('<option>', {
                                    value: preset.slug,
                                    text: preset.name
                                })
                            );
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX:', error);
                    console.error('Status:', status);
                    console.error('Réponse:', xhr.responseText); // Debug complet
                }
            });
        } else {
            $presetSelect.find('option:not(:first)').remove();
        }
    }

    // Écouter les changements du type d'opération
    $('#operation_type').on('change', function() {
        var operationType = $(this).val();
        var $existingThemeRow = $('#existing_theme_row');
        
        if (operationType === 'update') {
            $existingThemeRow.show();
            // Charger les presets pour le thème sélectionné
            var selectedTheme = $('#existing_theme').val();
            if (selectedTheme) {
                loadTypographyPresets(selectedTheme);
            }
        } else {
            $existingThemeRow.hide();
            // Réinitialiser les presets
            $('#typography_preset').find('option:not(:first)').remove();
        }
    });

    // Écouter les changements du thème sélectionné
    $('#existing_theme').on('change', function() {
        var selectedTheme = $(this).val();
        loadTypographyPresets(selectedTheme);
    });

    // Dans le gestionnaire de soumission du formulaire
    $('#theme-generator-form').on('submit', function(e) {
        e.preventDefault();
        
        var operationType = $('#operation_type').val();
        var selectedTheme = $('#existing_theme').val();
        var typographyPreset = $('#typography_preset').val();
        
        // Vérification des données requises
        if (operationType === 'update' && !selectedTheme) {
            alert('Veuillez sélectionner un thème à mettre à jour');
            return;
        }

        // Création de l'objet de données
        var formData = new FormData();
        formData.append('action', 'update_theme');
        formData.append('nonce', '<?php echo wp_create_nonce('up_theme_generator_nonce'); ?>');
        
        // Ajout des données du thème dans un objet structuré
        var themeData = {
            'existing_theme': selectedTheme,
            'typography_preset': typographyPreset,
            'operation_type': operationType
        };
        
        // Conversion en JSON et ajout à formData
        formData.append('theme_data', JSON.stringify(themeData));

        console.log('Données envoyées:', {
            action: 'update_theme',
            theme_data: themeData
        });

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Réponse reçue:', response);
                if (response.success) {
                    alert('Thème mis à jour avec succès !');
                } else {
                    alert('Erreur : ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
                console.error('Status:', status);
                console.error('Réponse:', xhr.responseText);
                alert('Erreur lors de la mise à jour du thème');
            }
        });
    });
});
</script>
