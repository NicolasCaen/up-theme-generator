<?php
if (!defined('ABSPATH')) {
    exit;
}
$nonce = wp_create_nonce('up_theme_generator_nonce');
?>

<div class="wrap">
    <h1>Gestionnaire de Typographie</h1>
    
    <div class="typography-form">
        <div class="form-group">
            <label for="theme-selector">Sélectionner un thème :</label>
            <select id="theme-selector" required>
                <option value="">Choisir un thème</option>
                <?php foreach ($themes as $theme_slug => $theme) : ?>
                    <?php if (strpos($theme_slug, 'backups') === false) : ?>
                        <option value="<?php echo esc_attr($theme_slug); ?>">
                            <?php echo esc_html($theme->get('Name')); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="preset-name">Nom du preset :</label>
            <input type="text" id="preset-name" required placeholder="ex: Modern & Classic">
        </div>

        <div class="fonts-selection">
            <h3>Sélection des polices (maximum 3)</h3>
            <div id="font-selectors">
                <div class="font-selector">
                    <label>Police 1 (Primary) :</label>
                    <select class="font-select" required>
                        <option value="">Choisir une police</option>
                        <?php foreach ($fonts as $font) : ?>
                            <option value="<?php echo esc_attr($font['name']); ?>">
                                <?php echo esc_html($font['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="button" id="add-font" class="button">Ajouter une police</button>
        </div>

        <div class="form-actions">
            <button type="button" id="save-preset" class="button button-primary">
                Créer le preset
            </button>
        </div>
    </div>
</div>

<style>
.typography-form {
    max-width: 800px;
    margin-top: 20px;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}
.font-selector {
    margin-bottom: 15px;
}
.fonts-selection {
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
}
</style>

<script>
jQuery(document).ready(function($) {
    let fontCount = 1;
    const maxFonts = 3;

    $('#add-font').on('click', function() {
        if (fontCount >= maxFonts) {
            alert('Maximum 3 polices autorisées');
            return;
        }

        fontCount++;
        const newSelector = `
            <div class="font-selector">
                <label>Police ${fontCount} (${fontCount === 2 ? 'Secondary' : 'Tertiary'}) :</label>
                <select class="font-select" required>
                    <option value="">Choisir une police</option>
                    <?php foreach ($fonts as $font) : ?>
                        <option value="<?php echo esc_attr($font['name']); ?>">
                            <?php echo esc_html($font['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button remove-font">Supprimer</button>
            </div>
        `;
        $('#font-selectors').append(newSelector);

        if (fontCount >= maxFonts) {
            $('#add-font').prop('disabled', true);
        }
    });

    $(document).on('click', '.remove-font', function() {
        $(this).closest('.font-selector').remove();
        fontCount--;
        $('#add-font').prop('disabled', false);
    });

    $('#save-preset').on('click', function() {
        const theme = $('#theme-selector').val();
        const presetName = $('#preset-name').val();
        const fonts = [];

        if (!theme || !presetName) {
            alert('Veuillez remplir tous les champs requis');
            return;
        }

        $('.font-select').each(function() {
            const fontValue = $(this).val();
            if (fontValue) {
                fonts.push(fontValue);
            }
        });

        if (fonts.length === 0) {
            alert('Veuillez sélectionner au moins une police');
            return;
        }

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'save_typography_preset',
                nonce: '<?php echo $nonce; ?>',
                theme: theme,
                preset_name: presetName,
                fonts: fonts
            },
            success: function(response) {
                if (response.success) {
                    alert('Preset créé avec succès !');
                    location.reload();
                } else {
                    alert('Erreur : ' + response.data);
                }
            },
            error: function() {
                alert('Erreur lors de la création du preset');
            }
        });
    });
});
</script> 