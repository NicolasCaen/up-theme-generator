jQuery(document).ready(function($) {
    $('#theme-selector').on('change', function() {
        var selectedTheme = $(this).val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_theme_fonts',
                theme: selectedTheme,
                nonce: up_theme_generator.nonce
            },
            success: function(response) {
                if (response.success && response.data && response.data.fonts) {
                    updateFontSelectors(response.data.fonts);
                } else {
                    console.error('Erreur lors de la récupération des polices', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
            }
        });
    });

    $('#add-font').on('click', function() {
        var fontCount = $('.font-selector').length;
        if (fontCount >= 3) {
            alert('Maximum 3 polices autorisées');
            return;
        }

        var newSelector = $('.font-selector').first().clone();
        var fontNumber = fontCount + 1;
        
        newSelector.find('label').text('Police ' + fontNumber + (fontNumber === 2 ? ' (Secondary)' : ' (Tertiary)') + ' :');
        
        newSelector.find('select').val('');
        
        $('#font-selectors').append(newSelector);
    });

    function updateFontSelectors(fonts) {
        if (!Array.isArray(fonts)) {
            console.error('Format de polices invalide', fonts);
            return;
        }

        $('.font-family-select').each(function() {
            var $select = $(this);
            
            var $firstOption = $select.find('option:first');
            
            $select.empty();
            
            if ($firstOption.length) {
                $select.append($firstOption);
            }
            
            fonts.forEach(function(font) {
                $select.append(
                    $('<option>', {
                        value: font.name,
                        text: font.name
                    })
                );
            });
        });
    }

    // Gestionnaire de création de preset
    $('#save-preset').on('click', function() {
        var selectedTheme = $('#theme-selector').val();
        var presetName = $('#preset-name').val();
        
        if (!selectedTheme || !presetName) {
            alert('Veuillez sélectionner un thème et donner un nom au preset');
            return;
        }

        // Récupérer toutes les polices sélectionnées
        var selectedFonts = [];
        $('.font-family-select').each(function() {
            var fontValue = $(this).val();
            if (fontValue) {
                selectedFonts.push(fontValue);
            }
        });

        if (selectedFonts.length === 0) {
            alert('Veuillez sélectionner au moins une police');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_typography_preset',
                theme: selectedTheme,
                preset_name: presetName,
                fonts: selectedFonts,
                nonce: up_theme_generator.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Preset créé avec succès !');
                    // Optionnel : réinitialiser le formulaire
                    location.reload();
                } else {
                    alert('Erreur lors de la création du preset : ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('Erreur lors de la création du preset');
                console.error('Erreur AJAX:', error);
            }
        });
    });
});
