jQuery(document).ready(function($) {
    // Initialiser les sélecteurs de couleur
    $('.color-select').each(function() {
        var select = $(this);
        var defaultValue = select.data('default');
        
        // Sélectionner la valeur par défaut
        if (defaultValue) {
            select.val(defaultValue);
        }
        
        // Ajouter les couleurs visuelles aux options
        select.find('option').each(function() {
            var color = $(this).data('color');
            if (color) {
                $(this).css('background-color', color);
            }
        });
    });

    // Fonction pour générer le contenu du bloc d'exemple
    function generateBlockContent(blockType) {
        const textStyles = getFontStyles('text');
        const buttonStyles = getFontStyles('button');
        const headingStyles = getFontStyles('heading');

        const commonStyles = {
            backgroundColor: $('select[name="background_color"] option:selected').data('color'),
            color: $('select[name="text_color"] option:selected').data('color'),
            ...(textStyles.fontFamily && { fontFamily: textStyles.fontFamily }),
            ...(textStyles.fontWeight && { fontWeight: textStyles.fontWeight }),
            ...(textStyles.fontStyle && { fontStyle: textStyles.fontStyle })
        };

        const buttonCss = {
            backgroundColor: $('select[name="button_background"] option:selected').data('color'),
            color: $('select[name="button_text"] option:selected').data('color'),
            ...(buttonStyles.fontFamily && { fontFamily: buttonStyles.fontFamily }),
            ...(buttonStyles.fontWeight && { fontWeight: buttonStyles.fontWeight }),
            ...(buttonStyles.fontStyle && { fontStyle: buttonStyles.fontStyle })
        };

        const headingCss = {
            color: $('select[name="heading_text"] option:selected').data('color'),
            ...(headingStyles.fontFamily && { fontFamily: headingStyles.fontFamily }),
            ...(headingStyles.fontWeight && { fontWeight: headingStyles.fontWeight }),
            ...(headingStyles.fontStyle && { fontStyle: headingStyles.fontStyle })
        };

        // Convertir les objets de style en chaînes CSS
        const commonStylesStr = Object.entries(commonStyles)
            .map(([key, value]) => `${key.replace(/([A-Z])/g, '-$1').toLowerCase()}: ${value}`)
            .join('; ');

        const buttonStylesStr = Object.entries(buttonCss)
            .map(([key, value]) => `${key.replace(/([A-Z])/g, '-$1').toLowerCase()}: ${value}`)
            .join('; ');

        const headingStylesStr = Object.entries(headingCss)
            .map(([key, value]) => `${key.replace(/([A-Z])/g, '-$1').toLowerCase()}: ${value}`)
            .join('; ');

        let content = '';
        switch(blockType) {
            case 'core/group':
                content = `
                    <div class="wp-block-group" style="background-color: ${commonStyles.backgroundColor}; padding: 2em;">
                        <div class="preview-section" style="${commonStylesStr}">
                            <h2 class="preview-heading" style="${headingStylesStr}">Titre d'exemple</h2>
                            <p class="preview-text">Voici un exemple de contenu avec un <a href="#" style="color: ${$('select[name="link_text"] option:selected').data('color')}">lien</a>.</p>
                            <div class="wp-block-button">
                                <a class="preview-button wp-block-button__link" style="${buttonStylesStr}">Bouton d'exemple</a>
                            </div>
                        </div>
                    </div>`;
                break;

            case 'core/columns':
                content = `
                    <div class="wp-block-columns" style="background-color: ${commonStyles.backgroundColor}; padding: 2em;">
                        <div class="preview-section" style="${commonStylesStr}">
                            <div class="wp-block-column">
                                <h2 class="preview-heading" style="${headingStylesStr}">Colonne 1</h2>
                                <p class="preview-text">Contenu avec <a href="#" style="color: ${$('select[name="link_text"] option:selected').data('color')}">lien</a>.</p>
                                <div class="wp-block-button">
                                    <a class="preview-button wp-block-button__link" style="${buttonStylesStr}">Bouton</a>
                                </div>
                            </div>
                            <div class="wp-block-column">
                                <h2 class="preview-heading" style="${headingStylesStr}">Colonne 2</h2>
                                <p class="preview-text">Autre contenu d'exemple.</p>
                            </div>
                        </div>
                    </div>`;
                break;

            case 'core/cover':
                content = `
                    <div class="wp-block-cover" style="background-color: ${commonStyles.backgroundColor}; min-height: 300px;">
                        <div class="preview-section wp-block-cover__inner-container" style="${commonStylesStr}">
                            <h2 class="preview-heading" style="${headingStylesStr}">Titre Cover</h2>
                            <p class="preview-text">Texte sur l'image de fond avec <a href="#" style="color: ${$('select[name="link_text"] option:selected').data('color')}">lien</a>.</p>
                            <div class="wp-block-button">
                                <a class="preview-button wp-block-button__link" style="${buttonStylesStr}">Bouton Cover</a>
                            </div>
                        </div>
                    </div>`;
                break;
        }
        return content;
    }

    // Écouter tous les changements qui doivent déclencher une mise à jour
    function bindPreviewUpdates() {
        // Sélecteurs de couleur
        $('.color-select').on('change', updatePreview);
        
        // Sélecteurs de police et leurs options
        $('.font-select, .font-weight-select, .font-style-select').on('change', updatePreview);
        
        // Cases à cocher d'activation de police
        $('.font-enable-checkbox').on('change', function() {
            const $container = $(this).closest('.font-settings');
            const $additionalOptions = $container.find('.font-additional-options');
            const $fontSelect = $container.find('.font-select');
            
            if (this.checked) {
                $additionalOptions.slideDown();
                $fontSelect.prop('disabled', false);
            } else {
                $additionalOptions.slideUp();
                $fontSelect.prop('disabled', true).val('inherit');
                $additionalOptions.find('select').val('inherit');
            }
            
            // Mettre à jour la prévisualisation après le changement
            updatePreview();
        });
        
        // Type de bloc
        $('#preview-block-type').on('change', updatePreview);
        
        // Bouton de rafraîchissement
        $('#refresh-preview').on('click', updatePreview);
    }

    // Initialiser les écouteurs d'événements
    bindPreviewUpdates();

    // Initialiser la prévisualisation au chargement
    updatePreview();

    // Fonction getFontStyles mise à jour
    function getFontStyles(prefix) {
        const enabled = $(`input[name="${prefix}_font_enabled"]`).is(':checked');
        if (!enabled) {
            return {};
        }

        const styles = {};
        const fontFamily = $(`select[name="${prefix}_font_family"]`).val();
        const fontWeight = $(`select[name="${prefix}_font_weight"]`).val();
        const fontStyle = $(`select[name="${prefix}_font_style"]`).val();

        if (fontFamily !== 'inherit') styles.fontFamily = fontFamily;
        if (fontWeight !== 'inherit') styles.fontWeight = fontWeight;
        if (fontStyle !== 'inherit') styles.fontStyle = fontStyle;

        return styles;
    }

    // Fonction updatePreview mise à jour
    function updatePreview() {
        const blockType = $('#preview-block-type').val();
        const content = generateBlockContent(blockType);
        $('#block-preview-container').html(content);

        requestAnimationFrame(() => {
            const textStyles = getFontStyles('text');
            const buttonStyles = getFontStyles('button');
            const headingStyles = getFontStyles('heading');

            // Appliquer les styles de texte global
            $('.preview-section').css({
                'background-color': $('select[name="background_color"] option:selected').data('color'),
                'color': $('select[name="text_color"] option:selected').data('color'),
                ...(textStyles.fontFamily && { 'font-family': textStyles.fontFamily }),
                ...(textStyles.fontWeight && { 'font-weight': textStyles.fontWeight }),
                ...(textStyles.fontStyle && { 'font-style': textStyles.fontStyle })
            });

            // Appliquer les styles des titres
            $('.preview-heading').css({
                'color': $('select[name="heading_text"] option:selected').data('color'),
                ...(headingStyles.fontFamily && { 'font-family': headingStyles.fontFamily }),
                ...(headingStyles.fontWeight && { 'font-weight': headingStyles.fontWeight }),
                ...(headingStyles.fontStyle && { 'font-style': headingStyles.fontStyle })
            });

            // Appliquer les styles des boutons
            $('.preview-button').css({
                'background-color': $('select[name="button_background"] option:selected').data('color'),
                'color': $('select[name="button_text"] option:selected').data('color'),
                ...(buttonStyles.fontFamily && { 'font-family': buttonStyles.fontFamily }),
                ...(buttonStyles.fontWeight && { 'font-weight': buttonStyles.fontWeight }),
                ...(buttonStyles.fontStyle && { 'font-style': buttonStyles.fontStyle })
            });

            // Mettre à jour les liens
            $('.preview-section a:not(.preview-button)').css({
                'color': $('select[name="link_text"] option:selected').data('color')
            });
        });
    }

    // Gérer la modification d'un preset
    $('.edit-preset').on('click', function() {
        const presetData = $(this).data('preset-data');
        const presetName = $(this).data('preset');
        
        // Remplir le formulaire avec les données existantes
        $('#preset_name').val(presetName).attr('readonly', true);
        
        // Cocher les types de blocs
        $('input[name="block_types[]"]').prop('checked', false);
        presetData.blockTypes.forEach(function(blockType) {
            $(`input[name="block_types[]"][value="${blockType}"]`).prop('checked', true);
        });
        
        // Mettre à jour les couleurs
        if (presetData.styles && presetData.styles.color) {
            // Sélectionner les options pour les couleurs principales
            selectColorOption('background_color', presetData.styles.color.background);
            selectColorOption('text_color', presetData.styles.color.text);
            
            // Sélectionner les options pour les éléments
            if (presetData.styles.elements) {
                if (presetData.styles.elements.button && presetData.styles.elements.button.color) {
                    selectColorOption('button_background', presetData.styles.elements.button.color.background);
                    selectColorOption('button_text', presetData.styles.elements.button.color.text);
                }
                if (presetData.styles.elements.link && presetData.styles.elements.link.color) {
                    selectColorOption('link_text', presetData.styles.elements.link.color.text);
                }
                if (presetData.styles.elements.heading && presetData.styles.elements.heading.color) {
                    selectColorOption('heading_text', presetData.styles.elements.heading.color.text);
                }
            }
        }
        
        // Changer le texte du bouton submit
        $('#section-preset-form button[type="submit"]').text('Mettre à jour le preset');
        
        // Ajouter un champ caché pour indiquer que c'est une modification
        $('input[name="is_edit"]').remove();
        $('#section-preset-form').append('<input type="hidden" name="is_edit" value="1">');
        
        // Scroll vers le formulaire
        $('html, body').animate({
            scrollTop: $('#section-preset-form').offset().top - 50
        }, 500);
        
        // Mettre à jour l'aperçu
        updatePreview();
    });

    // Fonction helper pour sélectionner la bonne option de couleur
    function selectColorOption(selectName, value) {
        const select = $(`select[name="${selectName}"]`);
        const options = select.find('option');
        let found = false;

        // D'abord, essayer de trouver une correspondance exacte
        options.each(function() {
            if ($(this).val() === value) {
                select.val(value);
                found = true;
                return false; // Sortir de la boucle
            }
        });

        // Si pas trouvé, chercher par la valeur CSS var()
        if (!found) {
            const cssVarName = value.replace('var(--wp--preset--color--', '').replace(')', '');
            options.each(function() {
                const optionValue = $(this).val();
                if (optionValue.includes(cssVarName)) {
                    select.val(optionValue);
                    return false;
                }
            });
        }
    }

    // Gérer la duplication d'un preset
    $('.duplicate-preset').on('click', function() {
        const presetData = $(this).data('preset-data');
        const originalName = $(this).data('preset');
        
        // Remplir le formulaire avec les données existantes
        $('#preset_name').val(originalName + '-copy').attr('readonly', false);
        
        // Cocher les types de blocs
        $('input[name="block_types[]"]').prop('checked', false);
        if (presetData.blockTypes) {
            presetData.blockTypes.forEach(function(blockType) {
                $(`input[name="block_types[]"][value="${blockType}"]`).prop('checked', true);
            });
        }
        
        // Utiliser la même logique de sélection des couleurs que pour l'édition
        if (presetData.styles && presetData.styles.color) {
            selectColorOption('background_color', presetData.styles.color.background);
            selectColorOption('text_color', presetData.styles.color.text);
            
            if (presetData.styles.elements) {
                if (presetData.styles.elements.button && presetData.styles.elements.button.color) {
                    selectColorOption('button_background', presetData.styles.elements.button.color.background);
                    selectColorOption('button_text', presetData.styles.elements.button.color.text);
                }
                if (presetData.styles.elements.link && presetData.styles.elements.link.color) {
                    selectColorOption('link_text', presetData.styles.elements.link.color.text);
                }
                if (presetData.styles.elements.heading && presetData.styles.elements.heading.color) {
                    selectColorOption('heading_text', presetData.styles.elements.heading.color.text);
                }
            }
        }
        
        // S'assurer qu'on n'a pas le champ is_edit
        $('input[name="is_edit"]').remove();
        
        // Changer le texte du bouton submit
        $('#section-preset-form button[type="submit"]').text('Créer une copie');
        
        // Scroll vers le formulaire
        $('html, body').animate({
            scrollTop: $('#section-preset-form').offset().top - 50
        }, 500);
        
        // Mettre à jour l'aperçu
        updatePreview();
    });

    // Modifier la gestion du formulaire pour prendre en compte l'édition
    $('#section-preset-form').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('action', 'save_section_preset');
        formData.append('nonce', up_theme_generator.nonce);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(formData.get('is_edit') ? 'Preset mis à jour avec succès' : 'Preset sauvegardé avec succès');
                    location.reload();
                } else {
                    alert('Erreur: ' + response.data);
                }
            },
            error: function() {
                alert('Erreur lors de la sauvegarde');
            }
        });
    });

    // Gérer la suppression d'un preset
    $('.delete-preset').on('click', function() {
        const presetName = $(this).data('preset');
        const themeSlug = $('#theme-selector').val() || $('input[name="theme"]').val();
        
        if (!themeSlug) {
            alert('Erreur: Thème non sélectionné');
            return;
        }
        
        if (confirm(`Êtes-vous sûr de vouloir supprimer le preset "${presetName}" ?`)) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_section_preset',
                    nonce: up_theme_generator.nonce,
                    preset_name: presetName,
                    theme: themeSlug
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erreur lors de la suppression: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                    alert('Erreur lors de la suppression du preset');
                }
            });
        }
    });
}); 