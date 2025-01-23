<?php
if (!defined('ABSPATH')) {
    exit;
}

$themes = wp_get_themes(array('errors' => null));
$nonce = wp_create_nonce('up_theme_generator_nonce');
?>

<div class="wrap">
    <h1>Gestionnaire de Polices</h1>
    <div class="up-theme-generator__info">
        <h3>Permet d'ajouter des fichiers de polices à un Thème</h3>
        <p>Selectionner un Thème et ajouter lui les fichiers de polices</p>
        <p>Une fois les fichiers ajouter, ces polices seront  disponible pour créer des presets de polices</p>
        <p>Ensuite, nous pouvons les inclure dans le thème.json du thème grâce à l'<a href="admin.php?page=up-theme-generator-typography">onglet Typographie</a></p>
    </div>
    <div class="tablenav top">
        <div class="alignleft actions">
            <select id="theme-selector">
                <option value="">Sélectionner un thème</option>
                <?php foreach ($themes as $theme_slug => $theme) : ?>
                    <?php if (strpos($theme_slug, 'backups') === false) : ?>
                        <option value="<?php echo esc_attr($theme_slug); ?>">
                            <?php echo esc_html($theme->get('Name')); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>

        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Nom de la police</th>
                <th>Fichiers</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($fonts)) : ?>
                <tr>
                    <td colspan="3">Aucune police disponible</td>
                </tr>
            <?php else : ?>
                <?php foreach ($fonts as $font) : ?>
                    <tr>
                        <td><?php echo esc_html($font['name']); ?></td>
                        <td>
                            <a href="#" class="toggle-files" data-font-id="<?php echo esc_attr($font['name']); ?>">
                                <?php echo count($font['files']); ?> fichier(s)
                            </a>
                            <ul class="font-files" id="files-<?php echo esc_attr($font['name']); ?>" style="display: none;">
                                <?php foreach ($font['files'] as $file) : ?>
                                    <li><?php echo esc_html($file); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                        <td>
                            <button class="button add-font" 
                                    data-font="<?php echo esc_attr($font['name']); ?>"
                                    disabled>
                                Ajouter au thème
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.font-files {
    margin: 10px 0;
    padding-left: 20px;
    list-style-type: disc;
}
.toggle-files {
    text-decoration: none;
    color: #2271b1;
    cursor: pointer;
}
.toggle-files:hover {
    color: #135e96;
}
.toggle-files.active {
    font-weight: bold;
}
#preset-selector {
    margin-left: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Gestion de la sélection du thème
    $('#theme-selector').on('change', function() {
        var selectedTheme = $(this).val();
        $('.add-font').prop('disabled', !selectedTheme);
        
        // Charger les presets disponibles
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
                    var $presetSelector = $('#preset-selector');
                    $presetSelector.empty().append('<option value="">Sélectionner un preset</option>');
                    
                    if (response.success && response.data) {
                        response.data.forEach(function(preset) {
                            $presetSelector.append(
                                $('<option>', {
                                    value: preset.slug,
                                    text: preset.name
                                })
                            );
                        });
                        $presetSelector.prop('disabled', false);
                    } else {
                        $presetSelector.prop('disabled', true);
                    }
                }
            });
        } else {
            $('#preset-selector').prop('disabled', true).val('');
        }
    });

    // Affichage/masquage des fichiers
    $('.toggle-files').on('click', function(e) {
        e.preventDefault();
        var fontId = $(this).data('font-id');
        var filesList = $('#files-' + fontId);
        
        filesList.slideToggle(200);
        $(this).toggleClass('active');
    });

    // Ajout de la police au thème
    $('.add-font').on('click', function() {
        var $button = $(this);
        var fontName = $button.data('font');
        var selectedTheme = $('#theme-selector').val();
        var selectedPreset = $('#preset-selector').val();

        if (!selectedTheme) {
            alert('Veuillez sélectionner un thème');
            return;
        }

        $button.prop('disabled', true).text('Ajout en cours...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'add_font_to_theme',
                nonce: '<?php echo wp_create_nonce('up_theme_generator_nonce'); ?>',
                theme: selectedTheme,
                preset: selectedPreset,
                font: fontName
            },
            success: function(response) {
                if (response.success) {
                    alert('Police ajoutée avec succès !');
                } else {
                    alert('Erreur : ' + response.data);
                }
            },
            error: function() {
                alert('Erreur lors de l\'ajout de la police');
            },
            complete: function() {
                $button.prop('disabled', false).text('Ajouter au thème');
            }
        });
    });
});
</script> 